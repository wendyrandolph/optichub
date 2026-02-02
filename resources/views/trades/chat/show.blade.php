@extends('layouts.trades')

@section('title', 'Team Chat')

@section('trades-content')
    @php
        use App\Support\TenantTime;

        $tz = $tenant->timezone ?? config('app.timezone');

        $tenantKey = $tenant->getRouteKey();

        $me = auth('web')->user();
        $displayName = function ($u) {
            if (!$u) {
                return 'User';
            }
            return $u->display_name ?? $u->name ?? 'User';
        };
        $canArchive = in_array(strtolower((string) ($me?->role ?? '')), ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true);
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

    @endphp

    @php
        $teamChannels = $channels->filter(fn ($c) => ($c->type ?? null) === 'tenant');
        $jobChannels = $channels
            ->filter(fn ($c) => ($c->type ?? null) === 'trade_job')
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
            return !in_array($type, ['tenant', 'trade_job', 'direct', 'dm'], true);
        });
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Team</p>
                <h1 class="text-2xl font-semibold text-text-base">Team Chat</h1>
                <p class="text-sm text-text-subtle mt-1">Channel: <span
                        class="font-semibold text-text-base">{{ $channel->name }}</span></p>
            </div>
            <div class="flex items-center gap-2">
                <a class="oh-btn" href="{{ route('tenant.trades.dashboard', ['tenant' => $tenantKey]) }}">Back to overview</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {{-- Channels (left rail) --}}
            <aside class="lg:col-span-4">
                <div class="oh-card p-4">
                    <div class="flex flex-col gap-2 mb-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm font-semibold text-text-base">Channels</div>
                        <form id="dm-start-form" class="flex items-center gap-2"
                            data-dm-base-url="{{ route('tenant.trades.chat.dm.start', ['tenant' => $tenantKey, 'user' => 'USER_ID']) }}">
                            <label class="text-xs font-semibold text-text-subtle sr-only" for="dm_user_id">Teammate</label>
                            <select id="dm_user_id" name="user_id" class="oh-input w-48">
                                <option value="">Message a teammate</option>
                                @foreach ($teammates as $teammate)
                                <option value="{{ $teammate->id }}">{{ $teammate->display_name ?? $teammate->email ?? 'Teammate' }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="oh-btn oh-btn--sm oh-btn--primary">Start DM</button>
                        </form>
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
                                            <a href="{{ route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $c->id]) }}"
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

                            @if ($jobChannels->isNotEmpty())
                                <div>
                                    <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-2">
                                        Jobs
                                    </div>
                                    <div class="space-y-1">
                                        @foreach ($jobChannels as $c)
                                            @php
                                                $active = (int) $c->id === (int) $channel->id;
                                                $isUnread = (bool) data_get($c, 'is_unread', false);
                                                $job = $c->tradeJob;
                                                $clientName = $job && $job->client
                                                    ? trim(($job->client->firstName ?? '') . ' ' . ($job->client->lastName ?? ''))
                                                    : null;
                                                $location = $job?->serviceLocation;
                                                $addressParts = array_filter([
                                                    $location->address_line1 ?? null,
                                                    $location->city ?? null,
                                                    $location->state ?? null,
                                                ]);
                                                $addressShort = $addressParts ? implode(', ', $addressParts) : null;
                                                if ($clientName && $addressShort) {
                                                    $subline = $clientName . ' · ' . $addressShort;
                                                } elseif ($clientName) {
                                                    $subline = $clientName;
                                                } elseif ($addressShort) {
                                                    $subline = $addressShort;
                                                } else {
                                                    $subline = 'Job channel';
                                                }
                                                $statusRaw = strtolower((string) ($job->status ?? ''));
                                                $statusLabel = match (true) {
                                                    in_array($statusRaw, ['completed', 'closed'], true) => 'Completed',
                                                    in_array($statusRaw, ['scheduled'], true) => 'Scheduled',
                                                    in_array($statusRaw, ['active', 'in_progress'], true) => 'Active',
                                                    default => null,
                                                };
                                                $statusPill = match ($statusLabel) {
                                                    'Active' => 'oh-pill oh-pill--success',
                                                    'Scheduled' => 'oh-pill oh-pill--info',
                                                    'Completed' => 'oh-pill oh-pill--muted',
                                                    default => 'oh-pill oh-pill--muted',
                                                };
                                                $timeLabel = $formatTime($c->last_message_at);
                                                $previewBase = $c->last_message_preview ?? null;
                                                $previewText = $previewBase
                                                    ? (($c->last_message_user_name ?? null)
                                                        ? $c->last_message_user_name . ': ' . $previewBase
                                                        : $previewBase)
                                                    : 'No messages yet.';
                                            @endphp
                                            <a href="{{ route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $c->id]) }}"
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default
                                                  {{ $active ? 'bg-surface-accent/50' : 'hover:bg-surface-accent/30' }} {{ $isUnread ? 'bg-surface-accent/40' : '' }}">
                                                <div class="min-w-0">
                                                    <div class="text-sm {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                        {{ $job?->summary ? 'Job: ' . $job->summary : $c->name }}
                                                    </div>
                                                    <div class="text-xs text-text-subtle truncate">
                                                        {{ $subline }}
                                                    </div>
                                                    <div class="text-xs text-text-subtle truncate">{{ $previewText }}</div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if ($statusLabel)
                                                        <span class="{{ $statusPill }} text-[11px]">{{ $statusLabel }}</span>
                                                    @endif
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
                                <div>
                                    <details>
                                        <summary class="text-[11px] uppercase tracking-wide text-text-subtle mb-2 cursor-pointer">
                                            Direct messages
                                        </summary>
                                        <div class="space-y-1">
                                            @foreach ($dmChannels as $c)
                                                @php
                                                    $active = (int) $c->id === (int) $channel->id;
                                                    $isUnread = (bool) data_get($c, 'is_unread', false);
                                                    $timeLabel = $formatTime($c->last_message_at);
                                                @endphp
                                                <a href="{{ route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $c->id]) }}"
                                                    class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default bg-surface-muted/30
                                                      {{ $active ? 'bg-surface-accent/30' : 'hover:bg-surface-accent/20' }} {{ $isUnread ? 'bg-surface-accent/35' : '' }}">
                                                    <div class="min-w-0">
                                                        <div class="text-[13px] {{ $isUnread ? 'font-semibold text-text-base' : 'font-medium text-text-base' }} truncate">
                                                            {{ $c->name }}
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
                                </div>
                            @endif

                            @if ($otherChannels->isNotEmpty())
                                <div>
                                    <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-2">Other</div>
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
                                            <a href="{{ route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $c->id]) }}"
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

            {{-- Messages (main) --}}
            <section class="lg:col-span-8">
                <div class="oh-card p-0 overflow-hidden flex flex-col min-h-[60vh]">
                    {{-- Header --}}
                    <div class="px-4 py-3 border-b border-border-default bg-surface-muted/40">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-text-base truncate">{{ $channel->name }}</div>
                                @if (($channel->type ?? null) === 'trade_job')
                                    <div class="text-xs text-text-subtle">Messages related to this job’s work and updates.</div>
                                @elseif (($channel->type ?? null) === 'tenant')
                                    <div class="text-xs text-text-subtle">Internal team discussion</div>
                                @elseif (($channel->type ?? null) === 'dm')
                                    <div class="text-xs text-text-subtle">Direct message</div>
                                @else
                                    <div class="text-xs text-text-subtle">Contextual communication for this channel.</div>
                                @endif
                            </div>
                            @if ($canArchive && in_array($channel->type ?? null, ['trade_job', 'dm'], true))
                                <form method="POST"
                                    action="{{ route('tenant.trades.chat.archive', ['tenant' => $tenantKey, 'channel' => $channel->id]) }}"
                                    onsubmit="return confirm('Archive this conversation? It will be hidden from the list.');">
                                    @csrf
                                    <button type="submit" class="oh-btn oh-btn--danger oh-btn--sm">Archive</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Messages list --}}
                    <div id="chat-scroll" class="flex-1 px-4 py-4 space-y-3 overflow-y-auto">
                        @php $lastDay = null; @endphp

                        @forelse($messages as $m)
                            @php
                                $mine = $me && (int) $m->user_id === (int) $me->id;
                                $day = TenantTime::dayLabel($m->created_at, $tz);
                                $showDay = $day !== $lastDay;
                                $lastDay = $day;
                            @endphp

                            @if ($showDay)
                                <div class="flex justify-center py-2">
                                    <span
                                        class="text-[11px] uppercase tracking-wide text-text-subtle px-3 py-1 rounded-full bg-surface-muted/50 border border-border-default">
                                        {{ $day }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div
                                    class="max-w-[85%] sm:max-w-[70%] rounded-2xl px-4 py-3 border border-border-default
                                        {{ $mine ? 'bg-[rgba(var(--brand-primary)/.10)]' : 'bg-surface-card' }}">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <div class="text-xs font-semibold text-text-base truncate">
                                            {{ $mine ? 'You' : $displayName($m->user) }}
                                        </div>
                                        <div class="text-[11px] text-text-subtle shrink-0">
                                            {{ TenantTime::format($m->created_at, $tz) }}
                                        </div>
                                    </div>

                                    <div class="text-sm text-text-base mt-1 whitespace-pre-line break-words">
                                        {{ $m->body }}
                                    </div>
                                    @if ($mine)
                                        <div class="mt-2 flex items-center justify-end gap-2 text-[11px] text-text-subtle">
                                            <details class="group">
                                                <summary class="cursor-pointer hover:text-text-base">Edit</summary>
                                                <form method="POST"
                                                    action="{{ route('tenant.trades.chat.messages.update', ['tenant' => $tenantKey, 'channel' => $channel->id, 'message' => $m->id]) }}"
                                                    class="mt-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <textarea name="body" rows="2" class="oh-input w-full resize-none" required>{{ $m->body }}</textarea>
                                                    <div class="mt-2 flex justify-end gap-2">
                                                        <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                                                    </div>
                                                </form>
                                            </details>
                                            <form method="POST"
                                                action="{{ route('tenant.trades.chat.messages.destroy', ['tenant' => $tenantKey, 'channel' => $channel->id, 'message' => $m->id]) }}"
                                                onsubmit="return confirm('Delete this message?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="hover:text-text-base">Delete</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            @if (($channel->type ?? null) === 'tenant')
                                <p class="text-sm text-text-subtle">Use this space for internal team updates and coordination.</p>
                            @elseif (($channel->type ?? null) === 'trade_job')
                                <p class="text-sm text-text-subtle">Use this thread for communication related to this job.</p>
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

                    {{-- Composer --}}
                    <div class="px-4 py-3 border-t border-border-default bg-surface-card">
                        <form method="POST"
                            action="{{ route('tenant.trades.chat.messages.store', ['tenant' => $tenantKey, 'channel' => $channel->id]) }}"
                            class="flex items-end gap-2">
                            @csrf
                            <div class="flex-1">
                                <label class="sr-only" for="body">Message</label>
                                <textarea id="body" name="body" rows="2" class="oh-input w-full resize-none"
                                    placeholder="{{ ($channel->type ?? null) === 'tenant' ? 'Share an update with the team…' : (($channel->type ?? null) === 'trade_job' ? 'Message about this job…' : (($channel->type ?? null) === 'dm' ? 'Share a quick clarification…' : 'Write a message…')) }}">{{ old('body') }}</textarea>
                                @error('body')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="oh-btn oh-btn--primary">
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Scroll to bottom on load
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('chat-scroll');
            if (el) el.scrollTop = el.scrollHeight;

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
        });
    </script>
@endsection
