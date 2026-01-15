<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\MarksChatRead;

class ChatController extends Controller
{
    use MarksChatRead;
    public function __construct()
    {
        $this->middleware('auth:web,admin');
    }

    public function index(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);
        $user = auth('admin')->user() ?? auth()->user();

        $teamChannel = ChatChannel::firstOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'tenant', 'project_id' => null],
            ['name' => 'Team Chat']
        );

        $projectChannels = ChatChannel::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'project')
            ->orderBy('name')
            ->get();

        $channels = $projectChannels->prepend($teamChannel);

        $unread = $this->unreadCounts($channels->pluck('id')->all(), $user->id);

        return view('chat.index', [
            'tenant' => $tenant,
            'channels' => $channels,
            'unread' => $unread,
        ]);
    }

    public function show(Tenant $tenant, ChatChannel $channel): View
    {
        $this->authorize('view', $tenant);
        abort_unless($channel->tenant_id === $tenant->id, 404);

        $messages = ChatMessage::query()
            ->where('channel_id', $channel->id)
            ->with('user:id,first_name,last_name,username')
            ->orderBy('created_at')
            ->get();

        $this->markRead($channel->id);

        return view('chat.show', [
            'tenant' => $tenant,
            'channel' => $channel,
            'messages' => $messages,
        ]);
    }

    public function project(Tenant $tenant, Project $project): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless($project->tenant_id === $tenant->id, 404);

        $channel = ChatChannel::firstOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'project', 'project_id' => $project->id],
            ['name' => $project->project_name]
        );

        return redirect()->route('tenant.chat.show', ['tenant' => $tenant->id, 'channel' => $channel->id]);
    }

    public function store(Tenant $tenant, ChatChannel $channel, Request $request): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless($channel->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth('admin')->user() ?? auth()->user();

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $this->markRead($channel->id);

        return back()->with('status', 'Message sent.');
    }



    private function unreadCounts(array $channelIds, int $userId): array
    {
        if (empty($channelIds)) {
            return [];
        }

        $rows = DB::table('chat_messages as m')
            ->leftJoin('chat_reads as r', function ($join) use ($userId) {
                $join->on('r.channel_id', '=', 'm.channel_id')
                    ->where('r.user_id', '=', $userId);
            })
            ->whereIn('m.channel_id', $channelIds)
            ->where('m.user_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('r.last_read_at')
                    ->orWhereColumn('m.created_at', '>', 'r.last_read_at');
            })
            ->select('m.channel_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('m.channel_id')
            ->get();

        $out = array_fill_keys($channelIds, 0);
        foreach ($rows as $row) {
            $out[$row->channel_id] = (int) $row->cnt;
        }

        return $out;
    }
}
