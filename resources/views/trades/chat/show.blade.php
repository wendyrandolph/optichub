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
            return $u->username ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: $u->name ?? 'User';
        };

    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Team</p>
                <h1 class="text-2xl font-semibold text-text-base">Team Chat</h1>
                <p class="text-sm text-text-subtle mt-1">Channel: <span
                        class="font-semibold text-text-base">{{ $channel->name }}</span></p>
            </div>
            <a class="oh-btn" href="{{ route('tenant.trades.dashboard', ['tenant' => $tenantKey]) }}">Back to overview</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {{-- Channels (left rail) --}}
            <aside class="lg:col-span-4">
                <div class="oh-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-text-base">Channels</div>
                        {{-- Placeholder: add "New channel" later if you want --}}
                    </div>

                    <div class="space-y-1">
                        @forelse($channels as $c)
                            @php
                                $active = (int) $c->id === (int) $channel->id;
                                $isUnread = (bool) data_get($c, 'is_unread', false);
                            @endphp
                            <a href="{{ route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $c->id]) }}"
                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 border border-border-default
                                  {{ $active ? 'bg-surface-accent/50' : 'hover:bg-surface-accent/30' }}">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-text-base truncate">
                                        {{ $c->name }}
                                    </div>
                                    <div class="text-xs text-text-subtle truncate">
                                        @if (($c->type ?? null) === 'trade_job')
                                            {{ $c->tradeJob?->summary ? 'Job: ' . $c->tradeJob->summary : 'Job channel' }}
                                        @else
                                            Team channel
                                        @endif
                                    </div>
                                </div>
                                @if ($isUnread)
                                    <span class="oh-pill oh-pill--info text-[11px]">New</span>
                                @endif
                            </a>
                        @empty
                            <div class="text-sm text-text-subtle">No channels.</div>
                        @endforelse
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
                                <div class="text-xs text-text-subtle">Internal team chat</div>
                            </div>
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
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-xl border border-border-default bg-surface-muted/50 px-4 py-3 text-sm text-text-subtle">
                                No messages yet. Start the conversation below.
                            </div>
                        @endforelse
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
                                    placeholder="Write a message…">{{ old('body') }}</textarea>
                                @error('body')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="oh-btn oh-btn--primary">
                                Send
                            </button>
                        </form>
                        <div class="text-[11px] text-text-subtle mt-2">
                            Tip: Press <span class="font-semibold">Shift + Enter</span> for a new line.
                        </div>
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
        });
    </script>
@endsection
