@extends('layouts.portal')

@section('content')
    @php
        $hexToRgb = function (?string $hex, string $fallback) {
            $h = ltrim($hex ?: $fallback, '#');
            if (strlen($h) === 3) {
                $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            }
            $int = hexdec($h);
            $r = ($int >> 16) & 255;
            $g = ($int >> 8) & 255;
            $b = $int & 255;
            return "{$r} {$g} {$b}";
        };
        $clientMsgHex = $client?->portal_client_message_color ?? '#1C2E70';
        $teamMsgHex = $client?->portal_team_message_color ?? '#E6EAF2';
        $clientMsgRgb = $hexToRgb($clientMsgHex, '#1C2E70');
        $teamMsgRgb = $hexToRgb($teamMsgHex, '#E6EAF2');
        $timezone = $tenant?->timezone ?? config('app.timezone');
        $todayKey = now($timezone)->format('Y-m-d');
        $yesterdayKey = now($timezone)->subDay()->format('Y-m-d');
    @endphp

    <div class="max-w-5xl mx-auto space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-text-base">Messages</h1>
                <p class="text-sm text-text-subtle">{{ $project->project_name ?? 'Project conversation' }}</p>
            </div>
            <a href="{{ route('portal.messages.index') }}" class="oh-btn text-sm px-3 py-2 hover:bg-surface-accent">
                <i class="fa-solid fa-arrow-left text-[10px] mr-1"></i>
                Back to messages
            </a>
        </div>

        <section class="bg-surface-card rounded-2xl border border-border-default/70 shadow-sm flex flex-col"
            style="--client-msg: {{ $clientMsgRgb }}; --team-msg: {{ $teamMsgRgb }};">
            <div class="px-4 py-3 border-b border-border-default/70 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-text-base truncate">Project chat</p>
                    <p class="text-xs text-text-subtle truncate">You + {{ $tenantName ?? 'your provider' }}</p>
                </div>
                <a href="{{ route('portal.projects.show', $project) }}"
                    class="text-xs text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))]">
                    View project
                </a>
            </div>

            <div id="portal-chat-scroll"
                class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-[rgba(var(--brand-primary),0.04)]"
                style="max-height: 65vh;">
                @php $lastDateKey = null; @endphp
                @forelse ($messages as $m)
                    @php
                        $senderRole = strtolower((string) ($m->user?->role ?? ''));
                        $isClient = $senderRole === 'client';
                        $label = $isClient ? 'You' : (trim(($m->user?->first_name ?? '') . ' ' . ($m->user?->last_name ?? '')) ?: 'Team');
                        $createdAt = $m->created_at ? $m->created_at->timezone($timezone) : null;
                        $dayKey = $createdAt ? $createdAt->format('Y-m-d') : null;
                        if ($dayKey && $dayKey !== $lastDateKey) {
                            $labelDate = $dayKey === $todayKey
                                ? 'Today'
                                : ($dayKey === $yesterdayKey ? 'Yesterday' : $createdAt->format('M d, Y'));
                            $lastDateKey = $dayKey;
                        } else {
                            $labelDate = null;
                        }
                    @endphp

                    @if ($labelDate)
                        <div class="flex items-center gap-3 text-xs text-text-subtle">
                            <div class="h-px flex-1 bg-border-default/70"></div>
                            <span class="px-2 py-0.5 rounded-full bg-surface-muted">{{ $labelDate }}</span>
                            <div class="h-px flex-1 bg-border-default/70"></div>
                        </div>
                    @endif

                    <div class="flex {{ $isClient ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[85%] rounded-2xl border px-3 py-2 {{ $isClient ? 'bg-[rgba(var(--client-msg),0.16)] border-[rgba(var(--client-msg),0.35)]' : 'bg-[rgb(var(--surface))] border-border-default/70' }}">
                            <div class="flex items-center justify-between gap-3 text-[11px] text-text-subtle">
                                <span class="font-semibold text-text-base">{{ $label }}</span>
                                <span>{{ $createdAt?->format('g:i A') ?? '—' }}</span>
                            </div>
                            <div class="mt-1 text-sm text-text-base whitespace-pre-wrap">{{ $m->body ?? '' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-border-default/70 bg-surface-card p-4 text-sm text-text-subtle">
                        No messages yet. Send a note to start the conversation.
                    </div>
                @endforelse
            </div>

            <div class="border-t border-border-default/70 bg-surface-card p-4 sticky bottom-0">
                <form method="POST" action="{{ route('portal.projects.messages.store', $project) }}"
                    class="flex flex-col gap-3">
                    @csrf
                    <textarea name="body" rows="1" required
                        placeholder="Write a message..."
                        class="w-full rounded-xl border border-border-default focus:border-[rgb(var(--brand-primary))] focus:ring-[rgba(var(--brand-primary),0.35)] text-sm px-3 py-2 resize-none bg-white/90">{{ old('body') }}</textarea>
                    <div class="flex justify-end">
                        <button class="oh-btn oh-btn--primary">Send</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scrollEl = document.getElementById('portal-chat-scroll');
            if (scrollEl) {
                scrollEl.scrollTop = scrollEl.scrollHeight;
            }
            const textarea = document.querySelector('textarea[name="body"]');
            if (textarea) {
                const resize = () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = `${textarea.scrollHeight}px`;
                };
                resize();
                textarea.addEventListener('input', resize);
            }
        });
    </script>
@endsection
