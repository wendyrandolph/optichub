<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\MarksChatRead;
use App\Models\TradeJob;

class ChatController extends Controller
{
    use MarksChatRead;
    public function __construct()
    {
        // Trades chat should be available to authenticated web users (techs + trades admins)
        $this->middleware('auth:web,admin');
    }

    public function index(Tenant $tenant)
    {
        $user = auth('admin')->user() ?? auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        $channels = ChatChannel::query()
            ->where('tenant_id', $tenant->id)
            ->with('tradeJob:id,summary')
            ->withMax('messages as last_message_at', 'created_at')
            ->orderByRaw("name = 'Team' DESC")
            ->orderByRaw('last_message_at IS NULL')
            ->orderByDesc('last_message_at')
            ->orderBy('name')
            ->get();

        $defaultChannel = $channels->first();

        return view('trades.chat.index', [
            'tenant' => $tenant,
            'channels' => $channels,
            'defaultChannel' => $defaultChannel,
        ]);
    }

    public function show(Tenant $tenant, ChatChannel $channel): View
    {
        $user = auth('admin')->user() ?? auth('web')->user();
        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless($channel->tenant_id === $tenant->id, 404);

        $channels = ChatChannel::where('tenant_id', $tenant->id)
            ->withMax('messages as last_message_at', 'created_at')
            ->orderByDesc('last_message_at')
            ->get();

        $messages = ChatMessage::where('channel_id', $channel->id)
            ->with('user')
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $this->markRead($channel->id);

        return view('trades.chat.show', compact(
            'tenant',
            'channels',
            'channel',
            'messages'
        ));
    }
    public function store(Request $request, Tenant $tenant, ChatChannel $channel)
    {
        $user = auth('admin')->user() ?? auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        // Tenant isolation (critical)
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        // Update read marker for sender (so they don't see their own message as unread)
        ChatRead::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return redirect()
            ->route('tenant.trades.chat.show', ['tenant' => $tenant->getRouteKey(), 'channel' => $channel->id])
            ->with('success_message', 'Message sent.');
    }

    public function job(Tenant $tenant, TradeJob $job)
    {
        // tenant isolation
        abort_unless((int) $job->tenant_id === (int) $tenant->id, 404);

        // Keep same guard rules as trades chat (team only)
        $user = auth('admin')->user() ?? auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        // Find or create the job-linked channel
        $channel = ChatChannel::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'trade_job_id' => $job->id,
            ],
            [
                'name' => 'Job: ' . ($job->summary ?? ('#' . $job->id)),
                'type' => 'trade_job', // new type value
                'project_id' => null,
            ]
        );

        return redirect()->route('tenant.trades.chat.show', [
            'tenant' => $tenant->getRouteKey(),
            'channel' => $channel->id,
        ]);
    }
}
