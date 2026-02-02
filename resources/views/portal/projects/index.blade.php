@extends('layouts.portal')

@section('title', 'Projects')

@section('content')
    @php
        $total = method_exists($projects, 'total')
            ? $projects->total()
            : (is_countable($projects)
                ? count($projects)
                : 0);
        $shown = method_exists($projects, 'count')
            ? $projects->count()
            : (is_countable($projects)
                ? count($projects)
                : 0);

        $activeCount = 0;
        if (is_iterable($projects)) {
            foreach ($projects as $p) {
                $status = strtolower((string) ($p->status ?? ''));
                if (
                    !in_array($status, ['closed', 'completed', 'complete', 'archived', 'canceled', 'cancelled'], true)
                ) {
                    $activeCount++;
                }
            }
        }

        // If you pass these from the controller later, they’ll just override nicely.
        $filesCount = $filesCount ?? 0;
        $messagesCount = $portalUnreadCount ?? 0;

        // Trust/last updated cue (optional)
        $lastUpdated = null;
        if (is_iterable($projects) && $shown > 0) {
            $lastUpdated = $projects[0]->updated_at ?? null; // paginator supports array access
        }
        $lastUpdatedLabel = $lastUpdated ? $lastUpdated->format('M j, Y') : now()->format('M j, Y');

        $chatProjects = collect($chatProjects ?? []);
        $chatProjectIds = $chatProjects->pluck('id')->filter()->values();
        $chatMessagesByProject = $chatMessagesByProject ?? [];
        $chatChannelsByProject = $chatChannelsByProject ?? collect();
        $tenantName = $tenantName ?? ($client?->tenant?->name ?? 'your provider');
        $timezone = $client?->tenant?->timezone ?? config('app.timezone');
        $todayKey = now($timezone)->format('Y-m-d');
        $yesterdayKey = now($timezone)->subDay()->format('Y-m-d');
        $chatAnchorProjectId = $chatProjects->first()?->id
            ?? (is_iterable($projects) ? ($projects[0]->id ?? null) : null);

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
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-text-base">Your Projects</h1>
                <p class="text-sm text-text-subtle mt-1">Active and recent projects shared with you.</p>

                {{-- Trust cue --}}
                <div class="mt-2 flex items-start gap-2 text-sm text-text-subtle">
                    <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    <div class="leading-5">
                        <span class="text-text-subtle">
                            Updates and files you see here come directly from your provider.
                        </span>
                        <span class="block text-xs text-text-subtle mt-1">
                            Last updated {{ $lastUpdatedLabel }}.
                        </span>
                    </div>
                </div>
            </div>

            {{-- Optional “Need help?” action removed --}}
        </div>

        {{-- Status strip (subtle) --}}
        <div class="oh-card px-6 py-3.5">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-lightbulb text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Active</p>
                        <p class="text-xl font-semibold text-text-base">{{ $activeCount }}</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-solid fa-folder-open text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Files</p>
                        <p class="text-xl font-semibold text-text-base">{{ $filesCount }}</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-solid fa-envelope text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Unread</p>
                        <p class="text-xl font-semibold text-text-base">{{ (int) $messagesCount }}</p>
                    </div>
                </div>

                <div class="ml-auto text-xs text-text-subtle">
                    Showing <span class="font-medium text-text-base">{{ $shown }}</span>
                    @if ($total)
                        of <span class="font-medium text-text-base">{{ $total }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Content --}}
        @if ($shown === 0)
            {{-- Strong empty state --}}
            <div class="rounded-xl border border-border-default bg-surface-card shadow-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 rounded-xl bg-surface-muted flex items-center justify-center text-text-subtle">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-text-base">You’re all set.</h2>
                        <p class="text-sm text-text-subtle mt-1">
                            There aren’t any projects shared with you yet. When work begins, you’ll see progress, files, and
                            messages here.
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            @if ($chatAnchorProjectId && $activeCount > 0)
                                <a href="{{ route('portal.projects.index', ['chat_project' => $chatAnchorProjectId]) }}#project-chat-{{ $chatAnchorProjectId }}"
                                    class="oh-btn oh-btn--primary text-sm px-4 py-2">
                                    Message your team
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('portal.invoices.index'))
                                <a href="{{ route('portal.invoices.index') }}" class="oh-btn text-sm px-4 py-2">
                                    View invoices
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('portal.files.index'))
                                <a href="{{ route('portal.files.index') }}" class="oh-btn text-sm px-4 py-2">
                                    View files
                                </a>
                            @endif
                        </div>

                        <div class="mt-4 text-xs text-text-subtle">
                            Tip: if you’re expecting something, send a message and your provider can share access right
                            away.
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="space-y-4">
                {{-- Cards grid (keep cards until at least ~1280) --}}
                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-2">
                        @foreach ($projects as $project)
                            @php
                                $statusKey = strtolower((string) ($project->status ?? ''));
                                $statusLabel = match ($statusKey) {
                                    'pending' => 'Scheduled',
                                    'awaiting_approval', 'needs_approval', 'approval' => 'Awaiting approval',
                                    'completed', 'closed' => 'Completed',
                                    default => 'In progress',
                                };
                                $statusPill = match ($statusKey) {
                                    'completed', 'closed' => 'oh-pill oh-pill--success',
                                    'awaiting_approval', 'needs_approval', 'approval' => 'oh-pill oh-pill--info',
                                    'pending' => 'oh-pill',
                                    default => 'oh-pill',
                                };

                            $updated = $project->updated_at ? $project->updated_at->format('M j, Y') : null;
                            $pct = (int) ($project->progress_pct ?? 0);
                            $messageHref = route('portal.projects.index', ['chat_project' => $project->id]) . '#project-chat-' . $project->id;
                            $chatMessages = collect($chatMessagesByProject[$project->id] ?? []);
                            $hasChat = $chatChannelsByProject->has($project->id);
                            $isActiveProject = ! in_array(
                                $statusKey,
                                ['closed', 'completed', 'complete', 'archived', 'canceled', 'cancelled'],
                                true
                            );
                            $lastDateKey = null;
                        @endphp

                            <div
                                class="oh-card rounded-xl border border-border-default p-5 hover:shadow-md transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-text-base truncate">
                                        {{ $project->project_name ?? 'Project' }}
                                    </h3>

                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="{{ $statusPill }} text-[11px] inline-flex items-center gap-2">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                            {{ $statusLabel }}
                                        </span>
                                        <span class="oh-pill text-[11px]">
                                            {{ (int) ($project->action_tasks_count ?? 0) }} action items
                                        </span>
                                    </div>

                                    <p class="text-xs text-text-subtle mt-2">
                                        Updated {{ $updated ?? '—' }}
                                    </p>
                                </div>

                                <a href="{{ route('portal.projects.show', $project->id) }}"
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent transition"
                                    aria-label="View project">
                                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                                </a>
                            </div>

                            {{-- Progress (optional but adds calm structure) --}}
                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs text-text-subtle mb-2">
                                    <span>Progress</span>
                                    <span>{{ $pct }}%</span>
                                </div>
                                <div class="h-2.5 w-full bg-surface-muted rounded-full overflow-hidden">
                                    <div class="h-2.5 rounded-full bg-[rgb(var(--brand-primary))]"
                                        style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <a href="{{ route('portal.projects.show', $project->id) }}"
                                    class="oh-btn oh-btn--primary text-xs px-3 py-1.5">
                                    View
                                </a>
                            </div>

                            <div class="mt-4">
                                @if ($hasChat)
                                    <section id="project-chat-{{ $project->id }}" data-portal-chat-section
                                        class="rounded-2xl border border-border-default/70 bg-surface-card shadow-sm overflow-hidden transition-shadow"
                                        style="--client-msg: {{ $clientMsgRgb }}; --team-msg: {{ $teamMsgRgb }};">
                                        <button type="button"
                                            class="w-full px-4 py-3 border-b border-border-default/70 flex items-center justify-between gap-3 text-left"
                                            data-portal-chat-toggle aria-expanded="false"
                                            aria-controls="project-chat-body-{{ $project->id }}">
                                            <div class="min-w-0">
                                                <p class="text-[10px] uppercase tracking-wide text-text-subtle">Chat</p>
                                                <p class="text-sm font-semibold text-text-base truncate">
                                                    {{ $project->project_name ?? 'Project chat' }}
                                                </p>
                                                <p class="text-xs text-text-subtle truncate">You + {{ $tenantName }} team</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if (($project->chat_unread_count ?? 0) > 0)
                                                    <span
                                                        class="inline-flex items-center rounded-full border border-border-default bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-text-subtle">
                                                        {{ $project->chat_unread_count }}
                                                    </span>
                                                @endif
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border-default text-text-subtle">
                                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
                                                        data-portal-chat-chevron></i>
                                                </span>
                                            </div>
                                        </button>

                                        <div id="project-chat-body-{{ $project->id }}" data-portal-chat-body
                                            class="max-h-0 overflow-hidden transition-[max-height] duration-300 ease-in-out"
                                            aria-hidden="true">
                                            <div data-portal-chat-scroll
                                                class="px-4 py-4 space-y-4 bg-surface-muted/40 overflow-y-auto"
                                                style="max-height: 30vh;">
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
                                                    <div
                                                        class="rounded-xl border border-border-default/70 bg-surface-card p-4 text-sm text-text-subtle">
                                                        No messages yet. Send a note to start the conversation.
                                                    </div>
                                                @endforelse
                                            </div>

                                            <div class="border-t border-border-default/70 bg-surface-card p-4">
                                                <form method="POST"
                                                    action="{{ route('portal.projects.messages.store', $project->id) }}"
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
                                        </div>
                                    </section>
                                @else
                                    <a href="{{ $messageHref }}"
                                        class="inline-flex items-center gap-2 text-xs text-text-subtle hover:text-text-base">
                                        <i class="fa-regular fa-message text-[12px]"></i>
                                        Open project chat
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center pt-2">
                    {{ $projects->links() }}
                </div>
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resizeTextarea = (textarea) => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            };

            const openAccordion = (section, open, scrollToBottom = false) => {
                const toggle = section.querySelector('[data-portal-chat-toggle]');
                const body = section.querySelector('[data-portal-chat-body]');
                const chevron = section.querySelector('[data-portal-chat-chevron]');
                if (!toggle || !body) {
                    return;
                }
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                body.setAttribute('aria-hidden', open ? 'false' : 'true');
                body.style.maxHeight = open ? `${body.scrollHeight}px` : '0px';
                section.classList.toggle('ring-2', open);
                section.classList.toggle('ring-[rgb(var(--brand-primary))]/30', open);
                if (chevron) {
                    chevron.classList.toggle('rotate-180', open);
                }
                if (open) {
                    body.querySelectorAll('[data-portal-chat-input]').forEach((textarea) => {
                        resizeTextarea(textarea);
                    });
                    if (scrollToBottom) {
                        body.querySelectorAll('[data-portal-chat-scroll]').forEach((scrollEl) => {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
                        });
                    }
                }
            };

            document.querySelectorAll('[data-portal-chat-section]').forEach((section) => {
                const toggle = section.querySelector('[data-portal-chat-toggle]');
                if (!toggle) {
                    return;
                }
                openAccordion(section, false);
                toggle.addEventListener('click', () => {
                    const isOpen = toggle.getAttribute('aria-expanded') === 'true';
                    openAccordion(section, !isOpen, true);
                });
            });

            const hash = window.location.hash;
            if (hash) {
                const section = document.querySelector(hash);
                if (section && section.matches('[data-portal-chat-section]')) {
                    openAccordion(section, true, true);
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    </script>
@endsection
