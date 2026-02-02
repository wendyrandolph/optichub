<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\MarksChatRead;
use App\Models\TradeJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    use MarksChatRead;
    public function __construct()
    {
        // Trades chat should be available to authenticated web users (techs + trades admins)
        $this->middleware('auth:web');
    }

    public function index(Tenant $tenant)
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        $channels = $this->channelsForUser($tenant, $user, false);
        $teammates = $this->dmTeammates($tenant, $user);
        $defaultChannel = $this->resolveDefaultChannel($tenant, $channels);

        if (! $defaultChannel) {
            return view('trades.chat.index', [
                'tenant' => $tenant,
                'channels' => $channels,
                'defaultChannel' => null,
            ]);
        }

        return redirect()->route('tenant.trades.chat.show', [
            'tenant' => $tenant->getRouteKey(),
            'channel' => $defaultChannel->id,
        ]);
    }

    public function show(Tenant $tenant, ChatChannel $channel): View
    {
        $user = auth('web')->user();
        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless($channel->tenant_id === $tenant->id, 404);
        abort_unless($channel->archived_at === null, 404);
        $this->authorizeDmAccess($channel, $user);

        $channels = $this->channelsForUser($tenant, $user, false);

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
            'messages',
            'teammates'
        ));
    }
    public function store(Request $request, Tenant $tenant, ChatChannel $channel)
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        // Tenant isolation (critical)
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($channel->archived_at === null, 403);
        $this->authorizeDmAccess($channel, $user);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $this->markRead($channel->id);

        return redirect()
            ->route('tenant.trades.chat.show', ['tenant' => $tenant->getRouteKey(), 'channel' => $channel->id])
            ->with('success_message', 'Message sent.');
    }

    public function updateMessage(Request $request, Tenant $tenant, ChatChannel $channel, ChatMessage $message): RedirectResponse
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($message->channel_id === $channel->id, 404);
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $data['body']]);

        return redirect()
            ->route('tenant.trades.chat.show', ['tenant' => $tenant->getRouteKey(), 'channel' => $channel->id])
            ->with('success_message', 'Message updated.');
    }

    public function destroyMessage(Tenant $tenant, ChatChannel $channel, ChatMessage $message): RedirectResponse
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($message->channel_id === $channel->id, 404);
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $message->delete();

        return redirect()
            ->route('tenant.trades.chat.show', ['tenant' => $tenant->getRouteKey(), 'channel' => $channel->id])
            ->with('success_message', 'Message deleted.');
    }

    public function archive(Tenant $tenant, ChatChannel $channel): RedirectResponse
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($this->canArchive($user), 403);

        $channel->archived_at = now();
        $channel->save();

        return redirect()
            ->route('tenant.trades.chat.index', ['tenant' => $tenant->getRouteKey()])
            ->with('success_message', 'Conversation archived.');
    }

    public function job(Tenant $tenant, TradeJob $job)
    {
        // tenant isolation
        abort_unless((int) $job->tenant_id === (int) $tenant->id, 404);

        // Keep same guard rules as trades chat (team only)
        $user = auth('web')->user();
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

    public function dmIndex(Tenant $tenant): View
    {
        $user = auth('web')->user();
        abort_unless($user, 401);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403);

        $teammates = $this->dmTeammates($tenant, $user);

        return view('trades.chat.dm', [
            'tenant' => $tenant,
            'teammates' => $teammates,
            'search' => request('q'),
        ]);
    }

    public function dmStart(Tenant $tenant, User $user): RedirectResponse
    {
        $viewer = auth('web')->user();
        abort_unless($viewer, 401);
        abort_unless((int) $viewer->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $viewer->id !== (int) $user->id, 400);

        $channel = ChatChannel::findOrCreateDm($tenant->id, $viewer, $user);

        return redirect()->route('tenant.trades.chat.show', [
            'tenant' => $tenant->getRouteKey(),
            'channel' => $channel->id,
        ]);
    }

    private function channelsForUser(Tenant $tenant, $user, bool $teamFirst): Collection
    {
        $query = ChatChannel::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('archived_at')
            ->with([
                'tradeJob:id,summary,status,client_id,service_location_id',
                'tradeJob.client:id,firstName,lastName',
                'tradeJob.serviceLocation:id,address_line1,city,state,postal_code,label',
                'lastMessage.user:id,first_name,last_name,email',
            ])
            ->withCount('messages')
            ->withMax('messages as last_message_at', 'created_at')
            ->addSelect([
                'last_read_at' => ChatRead::query()
                    ->select('last_read_at')
                    ->whereColumn('chat_reads.channel_id', 'chat_channels.id')
                    ->where('chat_reads.user_id', $user->id)
                    ->limit(1),
                'last_message_body' => ChatMessage::query()
                    ->select('body')
                    ->whereColumn('chat_messages.channel_id', 'chat_channels.id')
                    ->latest('created_at')
                    ->limit(1),
                'last_message_user_id' => ChatMessage::query()
                    ->select('user_id')
                    ->whereColumn('chat_messages.channel_id', 'chat_channels.id')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->where(function ($q) use ($user) {
                $q->where('type', '!=', 'dm')
                    ->orWhere(function ($q) use ($user) {
                        $q->where('type', 'dm')
                            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
                    });
            });

        if ($teamFirst) {
            $query->orderByRaw("name = 'Team' DESC")
                ->orderByRaw('last_message_at IS NULL')
                ->orderByDesc('last_message_at')
                ->orderBy('name');
        } else {
            $query->orderByDesc('last_message_at')
                ->orderBy('name');
        }

        $channels = $query->get();

        $userIds = $channels
            ->pluck('last_message_user_id')
            ->filter()
            ->unique()
            ->values();
        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->keyBy('id');

        return $channels->map(function ($channel) use ($users) {
            $lastMsg = data_get($channel, 'last_message_at');
            $lastRead = data_get($channel, 'last_read_at');

            $channel->is_unread = $lastMsg && (! $lastRead || $lastMsg > $lastRead);
            $channel->last_message_preview = $channel->last_message_body
                ? Str::limit((string) $channel->last_message_body, 60)
                : null;
            $messageUser = $users->get($channel->last_message_user_id);
            if ($messageUser) {
                $name = trim(($messageUser->first_name ?? '') . ' ' . ($messageUser->last_name ?? ''));
                $channel->last_message_user_name = $name !== '' ? $name : ($messageUser->email ?? 'User');
            } else {
                $channel->last_message_user_name = null;
            }
            return $channel;
        });
    }

    private function resolveDefaultChannel(Tenant $tenant, Collection $channels): ?ChatChannel
    {
        if ($channels->isEmpty()) {
            return $this->ensureTeamChannel($tenant);
        }

        $unread = $channels->filter(fn ($channel) => (bool) data_get($channel, 'is_unread', false));
        if ($unread->isNotEmpty()) {
            $priority = [
                'trade_job' => 1,
                'dm' => 2,
                'tenant' => 3,
            ];
            return $unread
                ->sortBy(function ($c) use ($priority) {
                    $rank = $priority[$c->type ?? 'tenant'] ?? 4;
                    $lastAt = data_get($c, 'last_message_at');
                    $ts = $lastAt instanceof \Carbon\Carbon
                        ? $lastAt->timestamp
                        : ($lastAt ? \Carbon\Carbon::parse($lastAt)->timestamp : 0);
                    return [$rank, -$ts];
                })
                ->first();
        }

        $teamChannel = $channels->first(fn ($c) => ($c->type ?? null) === 'tenant');
        return $teamChannel ?: $channels->sortByDesc('last_message_at')->first();
    }

    private function ensureTeamChannel(Tenant $tenant): ?ChatChannel
    {
        return ChatChannel::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'type' => 'tenant',
                'project_id' => null,
            ],
            [
                'name' => 'Team',
            ]
        );
    }

    private function dmTeammates(Tenant $tenant, User $user): Collection
    {
        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', '!=', $user->id)
            ->whereNotIn('role', ['client', 'client_contact', 'client_org_client'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search = trim((string) request('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->get(['id', 'first_name', 'last_name', 'email', 'role']);
    }

    private function canArchive($user): bool
    {
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true);
    }

    private function authorizeDmAccess(ChatChannel $channel, $user): void
    {
        if (($channel->type ?? null) !== 'dm') {
            return;
        }

        $isMember = $channel->users()->where('users.id', $user->id)->exists();
        abort_unless($isMember, 403);
    }
}
