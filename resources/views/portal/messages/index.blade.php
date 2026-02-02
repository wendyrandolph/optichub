@extends('layouts.portal')

@section('title', 'Messages')

@section('content')
    @php
        $count = is_countable($projects) ? count($projects) : 0;

        $statusLabel = function ($status) {
            $key = strtolower((string) $status);
            return match ($key) {
                'pending' => 'Scheduled',
                'awaiting_approval', 'needs_approval', 'approval' => 'Awaiting approval',
                'completed', 'closed' => 'Completed',
                default => 'Open',
            };
        };

        $statusTone = function ($status) {
            $key = strtolower((string) $status);
            return match ($key) {
                'completed', 'closed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'awaiting_approval', 'needs_approval', 'approval' => 'bg-amber-50 text-amber-800 border-amber-200',
                'pending' => 'bg-sky-50 text-sky-700 border-sky-200',
                default => 'bg-surface-muted text-text-subtle border-border-default',
            };
        };

        $chatProjects = collect($chatProjects ?? []);
        $chatMessagesByProject = $chatMessagesByProject ?? [];
        $chatChannelsByProject = $chatChannelsByProject ?? collect();
        $tenantName = $tenantName ?? ($client?->tenant?->name ?? 'your provider');
        $timezone = $client?->tenant?->timezone ?? config('app.timezone');
        $todayKey = now($timezone)->format('Y-m-d');
        $yesterdayKey = now($timezone)->subDay()->format('Y-m-d');

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
        $clientRecord = $client ?? null;
        $clientMsgHex = $clientRecord?->portal_client_message_color ?? '#1C2E70';
        $teamMsgHex = $clientRecord?->portal_team_message_color ?? '#E6EAF2';
        $clientMsgRgb = $hexToRgb($clientMsgHex, '#1C2E70');
        $teamMsgRgb = $hexToRgb($teamMsgHex, '#E6EAF2');
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
                <h1 class="text-2xl font-semibold text-text-base">Messages</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Select a project to view updates and send messages.
                </p>

                {{-- Trust cue --}}
                <div class="mt-2 flex items-start gap-2 text-sm text-text-subtle">
                    <span class="mt-1.5 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    <span>
                        Messages are private between you and your provider’s team.
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('portal.dashboard') }}" class="oh-btn text-sm px-3 py-2">Dashboard</a>
            </div>
        </div>

        {{-- Status strip --}}
        <div class="bg-surface-card rounded-xl border border-border-default shadow-sm px-6 py-3.5">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-comments text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Projects with access</p>
                        <p class="text-xl font-semibold text-text-base">{{ $count }}</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-envelope text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Unread</p>
                        <p class="text-xl font-semibold text-text-base">{{ (int) ($portalUnreadCount ?? 0) }}</p>
                    </div>
                </div>

                <div class="ml-auto text-xs text-text-subtle hidden sm:block">
                    Tip: Use “Message team” inside a project to keep updates in one place.
                </div>
            </div>
        </div>

        {{-- List + Chat --}}
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr),320px] items-start">
            <div class="space-y-3">
                @forelse ($projects as $project)
                    @php
                        $last = $project->chat_last_message_at ?? null;
                        $subtitle = $last ? 'Updated ' . $last->diffForHumans() : 'No messages yet';
                        $hasNew = (bool) ($project->chat_is_unread ?? false);
                        $messageHref = route('portal.messages.index', ['chat_project' => $project->id]) . '#project-chat-' . $project->id;
                    @endphp

                    <a href="{{ $messageHref }}"
                        class="group block rounded-xl border border-border-default bg-surface-card p-4 sm:p-5
                               hover:border-[rgb(var(--brand-primary))] hover:shadow-sm transition">

                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm sm:text-base font-semibold text-text-base truncate">
                                        {{ $project->project_name }}
                                    </h2>

                                    @if ($hasNew)
                                        <span
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-[rgb(var(--brand-primary))]">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                            New
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-text-subtle mt-1">
                                    {{ $subtitle }}
                                </p>
                            </div>

                            <div class="flex items-start gap-3 shrink-0">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusTone($project->status ?? 'open') }}">
                                    {{ $statusLabel($project->status ?? 'open') }}
                                </span>

                                <span
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default
                                           bg-surface-card text-text-subtle group-hover:bg-surface-accent group-hover:text-text-base transition"
                                    aria-hidden="true">
                                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Secondary row (subtle meta) --}}
                        <div class="mt-3 flex items-center justify-between text-[11px] text-text-subtle">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))] opacity-60"></span>
                                Project thread
                            </span>
                            <span class="hidden sm:inline">Tap to open conversation</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-border-default bg-surface-card p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="h-10 w-10 rounded-xl bg-surface-muted flex items-center justify-center text-text-subtle">
                                <i class="fa-regular fa-circle-check"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base font-semibold text-text-base">No message threads yet.</h2>
                                <p class="text-sm text-text-subtle mt-1">
                                    When your provider shares projects with you, they’ll appear here and you can message the
                                    team.
                                </p>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <a href="{{ route('portal.dashboard') }}" class="oh-btn oh-btn--primary text-sm px-4 py-2">
                                        Back to dashboard
                                    </a>

                                    @if (\Illuminate\Support\Facades\Route::has('portal.projects.index'))
                                        <a href="{{ route('portal.projects.index') }}" class="oh-btn text-sm px-4 py-2">
                                            View projects
                                        </a>
                                    @endif
                                </div>

                                <p class="mt-4 text-xs text-text-subtle">
                                    Tip: If you’re expecting an update, message your provider from the project page once it
                                    appears.
                                </p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($chatProjects->isNotEmpty())
                <aside class="space-y-4 lg:sticky lg:top-6">
                    @foreach ($chatProjects as $chatProject)
                        @php
                            $chatMessages = collect($chatMessagesByProject[$chatProject->id] ?? []);
                            $statusKey = strtolower((string) ($chatProject->status ?? ''));
                            $isActiveProject = ! in_array(
                                $statusKey,
                                ['closed', 'completed', 'complete', 'archived', 'canceled', 'cancelled'],
                                true
                            );
                            $lastDateKey = null;
                        @endphp

                        <section id="project-chat-{{ $chatProject->id }}"
                            class="bg-surface-card rounded-2xl border border-border-default/70 shadow-sm overflow-hidden"
                            style="--client-msg: {{ $clientMsgRgb }}; --team-msg: {{ $teamMsgRgb }};">
                            <div class="px-4 py-3 border-b border-border-default/70 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-text-base truncate">
                                        {{ $chatProject->project_name ?? 'Project chat' }}
                                    </p>
                                    <p class="text-xs text-text-subtle truncate">You + {{ $tenantName }} team</p>
                                </div>
                                <a href="{{ route('portal.projects.show', $chatProject->id) }}"
                                    class="text-xs text-text-subtle hover:text-text-base">
                                    View
                                </a>
                            </div>

                            <div data-portal-chat-scroll
                                class="px-4 py-4 space-y-4 bg-[rgba(var(--brand-primary),0.04)] overflow-y-auto"
                                style="max-height: 42vh;">
                                @forelse ($chatMessages as $message)
                                    @php
                                        $role = strtolower((string) ($message->user?->role ?? ''));
                                        $isClient = $role === 'client';
                                        $authorName = $isClient
                                            ? 'You'
                                            : trim(($message->user?->first_name ?? '') . ' ' . ($message->user?->last_name ?? ''));
                                        $authorName = $authorName !== '' ? $authorName : ($message->user?->email ?? 'Team');
                                        $createdAt = $message->created_at ? $message->created_at->timezone($timezone) : null;
                                        $dayKey = $createdAt ? $createdAt->format('Y-m-d') : null;
                                        if ($dayKey && $dayKey !== $lastDateKey) {
                                            $label = $dayKey === $todayKey
                                                ? 'Today'
                                                : ($dayKey === $yesterdayKey ? 'Yesterday' : $createdAt->format('M d, Y'));
                                            $lastDateKey = $dayKey;
                                        } else {
                                            $label = null;
                                        }
                                    @endphp

                                    @if ($label)
                                        <div class="flex items-center gap-3 text-xs text-text-subtle">
                                            <div class="h-px flex-1 bg-border-default/70"></div>
                                            <span class="px-2 py-0.5 rounded-full bg-surface-muted">{{ $label }}</span>
                                            <div class="h-px flex-1 bg-border-default/70"></div>
                                        </div>
                                    @endif

                                    <div class="flex {{ $isClient ? 'justify-end' : 'justify-start' }}">
                                        <div
                                            class="max-w-[85%] rounded-2xl border px-3 py-2 {{ $isClient ? 'bg-[rgba(var(--client-msg),0.16)] border-[rgba(var(--client-msg),0.35)]' : 'bg-[rgb(var(--surface))] border-border-default/70' }}">
                                            <div class="flex items-center justify-between gap-3 text-[11px] text-text-subtle">
                                                <span class="font-semibold text-text-base">{{ $authorName }}</span>
                                                <span>{{ $createdAt?->format('g:i A') ?? '—' }}</span>
                                            </div>
                                            <div class="mt-1 text-sm text-text-base whitespace-pre-wrap">
                                                {{ $message->body ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-border-default/70 bg-surface-card p-4 text-sm text-text-subtle">
                                        No messages yet. Send a note to start the conversation.
                                    </div>
                                @endforelse
                            </div>

                            <div class="border-t border-border-default/70 bg-surface-card p-4">
                                <form method="POST"
                                    action="{{ route('portal.projects.messages.store', $chatProject->id) }}"
                                    class="flex flex-col gap-3">
                                    @csrf
                                    <textarea data-portal-chat-input name="body" rows="1" required
                                        placeholder="{{ $isActiveProject ? 'Write a message...' : 'Messaging is closed for completed projects.' }}"
                                        class="w-full rounded-xl border border-border-default focus:border-[rgb(var(--brand-primary))] focus:ring-[rgba(var(--brand-primary),0.35)] text-sm px-3 py-2 resize-none bg-white/90"
                                        @disabled(!$isActiveProject)></textarea>
                                    <div class="flex items-center justify-end gap-3">
                                        @unless ($isActiveProject)
                                            <span class="text-xs text-text-subtle">Messaging is disabled for this project.</span>
                                        @endunless
                                        <button type="submit" class="oh-btn oh-btn--primary" @disabled(!$isActiveProject)>
                                            Send
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </section>
                    @endforeach
                </aside>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-portal-chat-scroll]').forEach((scrollEl) => {
                scrollEl.scrollTop = scrollEl.scrollHeight;
            });
            document.querySelectorAll('[data-portal-chat-input]').forEach((textarea) => {
                const resize = () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = `${textarea.scrollHeight}px`;
                };
                resize();
                textarea.addEventListener('input', resize);
            });
        });
    </script>
@endsection
