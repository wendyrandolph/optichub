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
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
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

            {{-- Optional “Need help?” action --}}
            @if (\Illuminate\Support\Facades\Route::has('portal.messages.index'))
                <a href="{{ route('portal.messages.index') }}"
                    class="hidden sm:inline-flex items-center gap-2 rounded-lg border border-border-default bg-surface-card px-3 py-2 text-sm text-text-subtle hover:text-text-base hover:bg-surface-accent transition">
                    <i class="fa-regular fa-message text-xs"></i>
                    Message team
                </a>
            @endif
        </div>

        {{-- Status strip (subtle) --}}
        <div class="bg-surface-card rounded-xl border border-border-default shadow-sm px-6 py-3.5">
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
                            @if (\Illuminate\Support\Facades\Route::has('portal.messages.index'))
                                <a href="{{ route('portal.messages.index') }}"
                                    class="oh-btn oh-btn--primary text-sm px-4 py-2"
                                    style="background: rgb(var(--brand-primary)); border-color: rgb(var(--brand-primary));">
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
            {{-- Cards grid (keep cards until at least ~1280) --}}
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    @php
                        $statusKey = strtolower((string) ($project->status ?? ''));
                        $statusLabel = match ($statusKey) {
                            'pending' => 'Scheduled',
                            'awaiting_approval', 'needs_approval', 'approval' => 'Awaiting approval',
                            'completed', 'closed' => 'Completed',
                            default => 'In progress',
                        };

                        $updated = $project->updated_at ? $project->updated_at->format('M j, Y') : null;
                        $pct = (int) ($project->progress_pct ?? 0);
                    @endphp

                    <div
                        class="rounded-xl border border-border-default bg-surface-card shadow-sm p-5 hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-text-base truncate">
                                    {{ $project->project_name ?? 'Project' }}
                                </h3>

                                <div
                                    class="mt-2 inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-muted px-2.5 py-0.5 text-[11px] font-medium text-text-subtle">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                    {{ $statusLabel }}
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
                                class="oh-btn oh-btn--primary text-xs px-3 py-1.5"
                                style="background: rgb(var(--brand-primary)); border-color: rgb(var(--brand-primary));">
                                View
                            </a>

                            @if (\Illuminate\Support\Facades\Route::has('portal.projects.messages.index'))
                                <a href="{{ route('portal.projects.messages.index', $project->id) }}"
                                    class="oh-btn text-xs px-3 py-1.5">
                                    Message
                                </a>
                            @elseif (\Illuminate\Support\Facades\Route::has('portal.messages.index'))
                                <a href="{{ route('portal.messages.index') }}" class="oh-btn text-xs px-3 py-1.5">
                                    Message
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-center pt-2">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
