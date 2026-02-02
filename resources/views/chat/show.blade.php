@extends('layouts.app')

@section('title', $channel->name ?? 'Chat')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
        $currentUser = auth('admin')->user() ?? auth()->user();
        $tz = $tenant->timezone ?? config('app.timezone');
        $teammates = $teammates ?? collect();

        $canArchive = in_array(strtolower((string) ($currentUser?->role ?? '')), ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true);
        $formatTime = function ($dt) use ($tz) {
            if (!$dt) {
                return null;
            }
            $local = $dt instanceof \Carbon\Carbon
                ? $dt->timezone($tz)
                : \Carbon\Carbon::parse($dt)->timezone($tz);
            return $local->isToday() ? $local->format('g:i A') : $local->format('M j');
        };
        $toTimestamp = function ($dt): int {
            if (!$dt) {
                return 0;
            }
            if ($dt instanceof \Carbon\Carbon) {
                return $dt->timestamp;
            }
            return \Carbon\Carbon::parse($dt)->timestamp;
        };

        $teamChannels = $channels->filter(fn ($c) => ($c->type ?? null) === 'tenant');
        $projectChannels = $channels
            ->filter(fn ($c) => ($c->type ?? null) === 'project')
            ->sortBy(function ($c) use ($toTimestamp) {
                $ts = $toTimestamp(data_get($c, 'last_message_at'));
                return [-$ts];
            });
        $dmChannels = $channels
            ->filter(fn ($c) => in_array($c->type ?? null, ['direct', 'dm'], true))
            ->sortBy(function ($c) use ($toTimestamp) {
                $ts = $toTimestamp(data_get($c, 'last_message_at'));
                return [-$ts];
            });
        $otherChannels = $channels->filter(function ($c) {
            $type = $c->type ?? null;
            return !in_array($type, ['tenant', 'project', 'direct', 'dm'], true);
        });
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Communication</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $channel->name }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.chat.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <aside class="lg:col-span-4">
                <div class="oh-card border border-border-default/70 shadow-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-text-base">Channels</div>
                    </div>

                    <div class="space-y-4">
                        @if ($channels->isEmpty())
                            <div class="text-sm text-text-subtle">No conversations yet.</div>
                        @else
                            @if ($teamChannels->isNotEmpty())
                                <div>
                                    <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-2">
                                        Team
                                    </div>
                                    <div class="space-y-1">
                                        @foreach ($teamChannels as $c)
                                            @php
                                                $active = (int) $c->id === (int) $channel->id;
                                                $isUnread = (bool) data_get($c, 'is_unread', false);
                                                $timeLabel = $formatTime($c->last_message_at);
                                            @endphp
                                            <a href="{{ route('tenant.chat.show', ['tenant' => $tenantId, 'channel' => $c->id]) }}"
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default
                                                  {{ $active ? 'bg-surface-accent/50' : 'hover:bg-surface-accent/30' }} {{ $isUnread ? 'bg-surface-accent/40' : '' }}">
                                                <div class="min-w-0">
                                                    <div class="text-sm {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                        {{ $c->name }}
                                                    </div>
                                                    <div class="text-xs text-text-subtle truncate">Internal team discussion</div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($timeLabel)
                                                        <span class="text-[11px] text-text-subtle">{{ $timeLabel }}</span>
                                                    @endif
                                                    @if ($isUnread)
                                                        <span class="oh-pill oh-pill--muted text-[10px]">New</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($projectChannels->isNotEmpty())
                                <div>
                                    <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-2">
                                        Projects
                                    </div>
                                    <div class="space-y-1">
                                        @foreach ($projectChannels as $c)
                                            @php
                                                $active = (int) $c->id === (int) $channel->id;
                                                $isUnread = (bool) data_get($c, 'is_unread', false);
                                                $timeLabel = $formatTime($c->last_message_at);
                                                $previewBase = $c->last_message_preview ?? null;
                                                $previewText = $previewBase
                                                    ? (($c->last_message_user_name ?? null)
                                                        ? $c->last_message_user_name . ': ' . $previewBase
                                                        : $previewBase)
                                                    : null;
                                                $projectName = $c->project?->project_name ?? $c->name;
                                            @endphp
                                            <a href="{{ route('tenant.chat.show', ['tenant' => $tenantId, 'channel' => $c->id]) }}"
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default
                                                  {{ $active ? 'bg-surface-accent/50' : 'hover:bg-surface-accent/30' }} {{ $isUnread ? 'bg-surface-accent/40' : '' }}">
                                                <div class="min-w-0">
                                                    <div class="text-sm {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                        {{ $projectName }}
                                                    </div>
                                                    @if ($previewText)
                                                        <div class="text-xs text-text-subtle truncate">{{ $previewText }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($timeLabel)
                                                        <span class="text-[11px] text-text-subtle">{{ $timeLabel }}</span>
                                                    @endif
                                                    @if ($isUnread)
                                                        <span class="oh-pill oh-pill--muted text-[10px]">New</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($dmChannels->isNotEmpty())
                                <details>
                                    <summary class="text-[11px] uppercase tracking-wide text-text-subtle mb-2 cursor-pointer">
                                        Direct messages
                                    </summary>
                                    <form id="dm-start-form" class="flex items-center gap-2 mb-2"
                                        data-dm-base-url="{{ route('tenant.chat.dm.start', ['tenant' => $tenantId, 'user' => 'USER_ID']) }}">
                                        <label class="text-xs font-semibold text-text-subtle sr-only" for="dm_user_id">Teammate</label>
                                        <select id="dm_user_id" name="user_id" class="oh-input w-3/5">
                                            <option value="">Message a teammate</option>
                                            @foreach ($teammates as $teammate)
                                                @php
                                                    $name = trim(($teammate->first_name ?? '') . ' ' . ($teammate->last_name ?? ''));
                                                    $label = $name !== '' ? $name : ($teammate->email ?? 'Teammate');
                                                @endphp
                                                <option value="{{ $teammate->id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="oh-btn oh-btn--sm oh-btn--primary flex-1">Start DM</button>
                                    </form>
                                    <div class="space-y-1">
                                        @foreach ($dmChannels as $c)
                                            @php
                                                $active = (int) $c->id === (int) $channel->id;
                                                $isUnread = (bool) data_get($c, 'is_unread', false);
                                                $timeLabel = $formatTime($c->last_message_at);
                                                $dmMember = $c->users?->firstWhere('id', '!=', $currentUser?->id);
                                                $dmMemberName = $dmMember
                                                    ? trim(($dmMember->first_name ?? '') . ' ' . ($dmMember->last_name ?? ''))
                                                    : null;
                                                $dmName = $dmMemberName !== '' ? $dmMemberName : ($dmMember->email ?? $c->name);
                                            @endphp
                                            <a href="{{ route('tenant.chat.show', ['tenant' => $tenantId, 'channel' => $c->id]) }}"
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default bg-surface-muted/30
                                                  {{ $active ? 'bg-surface-accent/30' : 'hover:bg-surface-accent/20' }} {{ $isUnread ? 'bg-surface-accent/35' : '' }}">
                                                <div class="min-w-0">
                                                    <div class="text-[13px] {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                        {{ $dmName }}
                                                    </div>
                                                    <div class="text-[11px] text-text-subtle">Direct message</div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($timeLabel)
                                                        <span class="text-[11px] text-text-subtle">{{ $timeLabel }}</span>
                                                    @endif
                                                    @if ($isUnread)
                                                        <span class="oh-pill oh-pill--muted text-[10px]">New</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @endif

                            @if ($otherChannels->isNotEmpty())
                                <div>
                                    <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-2">OTHER</div>
                                    <div class="space-y-1">
                                        @foreach ($otherChannels as $c)
                                            @php
                                                $active = (int) $c->id === (int) $channel->id;
                                                $isUnread = (bool) data_get($c, 'is_unread', false);
                                                $timeLabel = $formatTime($c->last_message_at);
                                                $previewBase = $c->last_message_preview ?? null;
                                                $previewText = $previewBase
                                                    ? (($c->last_message_user_name ?? null)
                                                        ? $c->last_message_user_name . ': ' . $previewBase
                                                        : $previewBase)
                                                    : 'No messages yet.';
                                            @endphp
                                            <a href="{{ route('tenant.chat.show', ['tenant' => $tenantId, 'channel' => $c->id]) }}"
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default
                                                  {{ $active ? 'bg-surface-accent/50' : 'hover:bg-surface-accent/30' }} {{ $isUnread ? 'bg-surface-accent/40' : '' }}">
                                                <div class="min-w-0">
                                                    <div class="text-sm {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                        {{ $c->name }}
                                                    </div>
                                                    <div class="text-xs text-text-subtle truncate">{{ $previewText }}</div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($timeLabel)
                                                        <span class="text-[11px] text-text-subtle">{{ $timeLabel }}</span>
                                                    @endif
                                                    @if ($isUnread)
                                                        <span class="oh-pill oh-pill--muted text-[10px]">New</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </aside>

            <section class="lg:col-span-8">
                <div class="oh-card border border-border-default/70 shadow-card p-0 overflow-hidden flex flex-col min-h-[60vh]">
                    <div class="px-4 py-3 border-b border-border-default bg-surface-muted/40">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-text-base truncate">{{ $channel->name }}</div>
                            @if (($channel->type ?? null) === 'project')
                                <div class="text-xs text-text-subtle">Messages related to this project’s work and updates.</div>
                            @elseif (($channel->type ?? null) === 'tenant')
                                <div class="text-xs text-text-subtle">Internal team discussion</div>
                            @elseif (($channel->type ?? null) === 'dm')
                                <div class="text-xs text-text-subtle">Direct message</div>
                            @else
                                <div class="text-xs text-text-subtle">Contextual communication for this channel.</div>
                            @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if (($channel->type ?? null) === 'project' && $channel->project)
                                    <a href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $channel->project->id]) }}"
                                        class="oh-btn oh-btn--sm">
                                        View project
                                    </a>
                                @endif
                                @if ($canArchive && in_array($channel->type ?? null, ['project', 'dm'], true))
                                    <form method="POST" action="{{ route('tenant.chat.archive', ['tenant' => $tenantId, 'channel' => $channel->id]) }}"
                                        onsubmit="return confirm('Archive this conversation? It will be hidden from the list.');">
                                        @csrf
                                        <button type="submit" class="oh-btn oh-btn--danger oh-btn--sm">Archive</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="chat-scroll" class="flex-1 px-4 py-4 space-y-3 overflow-y-auto">
                        @forelse ($messages as $message)
                            @php
                                $author = $message->user;
                                $authorName =
                                    trim(($author->first_name ?? '') . ' ' . ($author->last_name ?? '')) ?:
                                    $author->email ?? 'User';
                                $isMe = $currentUser && $author && (int) $author->id === (int) $currentUser->id;
                            @endphp
                            <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] rounded-xl border border-border-default/60 px-3 py-2 bg-surface-card">
                                    <div class="text-[11px] text-text-subtle">{{ $isMe ? 'You' : $authorName }}</div>
                                    <div class="text-sm text-text-base whitespace-pre-wrap">{{ $message->body }}</div>
                                    <div class="text-[11px] text-text-subtle mt-1 flex items-center justify-between gap-2">
                                        <span>{{ $message->created_at?->format('M j, g:ia') }}</span>
                                        @if ($isMe)
                                            <span class="flex items-center gap-2">
                                                <details class="group">
                                                    <summary class="cursor-pointer hover:text-text-base">Edit</summary>
                                                    <form method="POST"
                                                        action="{{ route('tenant.chat.messages.update', ['tenant' => $tenantId, 'channel' => $channel->id, 'message' => $message->id]) }}"
                                                        class="mt-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <textarea name="body" rows="2" class="oh-input w-full resize-none" required>{{ $message->body }}</textarea>
                                                        <div class="mt-2 flex justify-end">
                                                            <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                                                        </div>
                                                    </form>
                                                </details>
                                                <form method="POST"
                                                    action="{{ route('tenant.chat.messages.destroy', ['tenant' => $tenantId, 'channel' => $channel->id, 'message' => $message->id]) }}"
                                                    onsubmit="return confirm('Delete this message?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="hover:text-text-base">Delete</button>
                                                </form>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            @if (($channel->type ?? null) === 'tenant')
                                <p class="text-sm text-text-subtle">Use this space for internal team updates and coordination.</p>
                            @elseif (($channel->type ?? null) === 'project')
                                <p class="text-sm text-text-subtle">Use this thread for communication related to this project.</p>
                            @elseif (($channel->type ?? null) === 'dm')
                                <p class="text-sm text-text-subtle">Use this space for quick clarification.</p>
                            @else
                                <p class="text-sm text-text-subtle">Use this thread for contextual updates.</p>
                            @endif
                        @endforelse

                        @if (($channel->type ?? null) === 'dm' && $messages->count() > 10)
                            <div class="text-xs text-text-subtle border border-border-default/70 rounded-xl px-3 py-2 bg-surface-muted/30">
                                This conversation may belong in a project.
                            </div>
                        @endif
                    </div>

                    <form method="POST"
                        action="{{ route('tenant.chat.messages.store', ['tenant' => $tenantId, 'channel' => $channel->id]) }}"
                        class="px-4 py-3 border-t border-border-default bg-surface-card">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Message</span>
                            <textarea name="body" rows="3" class="oh-textarea bg-surface-card/60" required
                                placeholder="{{ ($channel->type ?? null) === 'tenant' ? 'Share an update with the team…' : (($channel->type ?? null) === 'project' ? 'Message about this project…' : (($channel->type ?? null) === 'dm' ? 'Share a quick clarification…' : 'Write a message…')) }}">{{ old('body') }}</textarea>
                        </label>
                        <div class="flex justify-end mt-2">
                            <button type="submit" class="oh-btn oh-btn--primary">Send</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function() {
            const box = document.getElementById('chat-scroll');
            if (box) {
                box.scrollTop = box.scrollHeight;
            }

            const dmForm = document.getElementById('dm-start-form');
            const dmSelect = document.getElementById('dm_user_id');
            if (!dmForm || !dmSelect) return;

            const baseUrl = dmForm.dataset.dmBaseUrl || '';
            dmForm.addEventListener('submit', (event) => {
                if (!dmSelect.value) {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                window.location = baseUrl.replace('USER_ID', dmSelect.value);
            });
        })();
    </script>
@endsection
