<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\TeamMember;
use App\Models\User;
use App\Http\Controllers\Concerns\MarksChatRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    use MarksChatRead;
    public function __construct()
    {
        $this->middleware('auth:web,admin');
    }

    public function index(Tenant $tenant): View|RedirectResponse
    {
        $this->authorize('view', $tenant);
        $user = auth('admin')->user() ?? auth()->user();

        $channels = $this->channelsForUser($tenant, $user, false);
        $defaultChannel = $this->resolveDefaultChannel($tenant, $channels);

        if (! $defaultChannel) {
            return view('chat.index', [
                'tenant' => $tenant,
                'channels' => $channels,
            ]);
        }

        return redirect()->route('tenant.chat.show', [
            'tenant' => $tenant->id,
            'channel' => $defaultChannel->id,
        ]);
    }

    public function show(Tenant $tenant, ChatChannel $channel): View
    {
        $this->authorize('view', $tenant);
        abort_unless($channel->tenant_id === $tenant->id, 404);
        abort_unless($channel->archived_at === null, 404);

        $user = auth('admin')->user() ?? auth()->user();
        $this->authorizeDmAccess($channel, $user);

        $channels = $this->channelsForUser($tenant, $user, false);
        $teammates = $this->dmTeammates($tenant, $user);

        $messages = ChatMessage::query()
            ->where('channel_id', $channel->id)
            ->with('user:id,first_name,last_name,email')
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $this->markRead($channel->id);

        return view('chat.show', [
            'tenant' => $tenant,
            'channels' => $channels,
            'channel' => $channel,
            'messages' => $messages,
            'teammates' => $teammates,
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
        abort_unless($channel->archived_at === null, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth('admin')->user() ?? auth()->user();
        $this->authorizeDmAccess($channel, $user);

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $this->markRead($channel->id);

        return back()->with('status', 'Message sent.');
    }

    public function updateMessage(Request $request, Tenant $tenant, ChatChannel $channel, ChatMessage $message): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($message->channel_id === $channel->id, 404);

        $user = auth('admin')->user() ?? auth()->user();
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message->update(['body' => $data['body']]);

        return redirect()->route('tenant.chat.show', [
            'tenant' => $tenant->id,
            'channel' => $channel->id,
        ])->with('status', 'Message updated.');
    }

    public function destroyMessage(Tenant $tenant, ChatChannel $channel, ChatMessage $message): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);
        abort_unless($message->channel_id === $channel->id, 404);

        $user = auth('admin')->user() ?? auth()->user();
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $message->delete();

        return redirect()->route('tenant.chat.show', [
            'tenant' => $tenant->id,
            'channel' => $channel->id,
        ])->with('status', 'Message deleted.');
    }

    public function archive(Tenant $tenant, ChatChannel $channel): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless((int) $channel->tenant_id === (int) $tenant->id, 404);

        $user = auth('admin')->user() ?? auth()->user();
        abort_unless($this->canArchive($user), 403);

        $channel->archived_at = now();
        $channel->save();

        return redirect()->route('tenant.chat.index', ['tenant' => $tenant->id])
            ->with('status', 'Conversation archived.');
    }

    public function dmIndex(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);
        $user = auth('admin')->user() ?? auth()->user();

        $teammates = $this->dmTeammates($tenant, $user);

        return view('chat.dm', [
            'tenant' => $tenant,
            'teammates' => $teammates,
            'search' => request('q'),
        ]);
    }

    public function dmStart(Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        $viewer = auth('admin')->user() ?? auth()->user();
        abort_unless((int) $viewer->id !== (int) $user->id, 400);

        $channel = ChatChannel::findOrCreateDm($tenant->id, $viewer, $user);

        return redirect()->route('tenant.chat.show', ['tenant' => $tenant->id, 'channel' => $channel->id]);
    }


    private function channelsForUser(Tenant $tenant, $user, bool $teamFirst): Collection
    {
        $query = ChatChannel::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('archived_at')
            ->with([
                'project:id,project_name',
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
                'project' => 1,
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

    private function dmTeammates(Tenant $tenant, User $user): \Illuminate\Support\Collection
    {
        $query = TeamMember::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->active()
            ->with('user:id,first_name,last_name,email')
            ->orderBy('lastName')
            ->orderBy('firstName');

        if ($search = trim((string) request('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                    ->orWhere('lastName', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get()->pluck('user')->filter();
    }

    private function authorizeDmAccess(ChatChannel $channel, $user): void
    {
        if (($channel->type ?? null) !== 'dm') {
            return;
        }

        $isMember = $channel->users()->where('users.id', $user->id)->exists();
        abort_unless($isMember, 403);
    }

    private function canArchive($user): bool
    {
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true);
    }
}
