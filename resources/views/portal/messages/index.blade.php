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

        {{-- List --}}
        <div class="space-y-3">
            @forelse ($projects as $project)
                @php
                    $last = $project->conversation?->last_message_at;
                    $subtitle = $last ? 'Updated ' . $last->diffForHumans() : 'No messages yet';

                    // Optional: if you later add unread-per-project, this slot is ready.
                    $hasNew = false;
                @endphp

                <a href="{{ route('portal.projects.messages.index', $project) }}"
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
                                <a href="{{ route('portal.dashboard') }}" class="oh-btn oh-btn--primary text-sm px-4 py-2"
                                    style="background: rgb(var(--brand-primary)); border-color: rgb(var(--brand-primary));">
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
    </div>
@endsection
