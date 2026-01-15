@extends('layouts.portal')

@section('title', 'Client Portal')

@section('content')
    @php
        $activeProjectsCount = $activeProjects?->count() ?? 0;
        $filesCount = $uploads?->count() ?? 0;
    @endphp

    <div class="oh-page space-y-5 animate-fade-in-up">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
            <h1 class="text-3xl font-semibold text-text-base">Welcome back, {{ $client->firstName }}.</h1>
            @php
                $lastActivityAt = null;
                if (!empty($activities) && count($activities) > 0) {
                    $lastActivityAt = $activities[0]['at'] ?? null;
                }
                $lastUpdated = $lastActivityAt
                    ? \Carbon\Carbon::parse($lastActivityAt)->format('M j, Y')
                    : now()->format('M j, Y');
            @endphp
            <p class="text-text-subtle mt-2">
                {{ $tenant->name ?? 'Your workspace' }} · Your projects and updates are ready.
            </p>
            <div class="mt-2 flex items-start gap-2 text-sm text-text-subtle">
                <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>


                <span class="text-text-subtle">Last updated {{ $lastUpdated }}.</span>

            </div>
        </div>

        {{-- Status strip --}}
        <div class="bg-surface-card rounded-xl border border-border-default shadow-sm px-6 py-3.5">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-lightbulb text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Active projects</p>
                        <p class="text-xl font-semibold text-text-base">{{ $activeProjectsCount }}</p>
                    </div>
                </div>
                <div class="h-6 w-px bg-border-default hidden sm:block"></div>
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-solid fa-paperclip text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Files shared</p>
                        <p class="text-xl font-semibold text-text-base">{{ $filesCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Projects --}}
            <div class="bg-surface-card rounded-xl border border-border-default shadow-sm p-6 lg:col-span-2">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-medium text-text-base">Projects</h2>
                        <p class="text-text-subtle text-sm mt-1">Your active work, with clear next steps.</p>
                    </div>
                </div>

                @if ($activeProjects->isEmpty())
                    <div
                        class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle mt-4">
                        No active projects yet. When work begins, you’ll see progress and messages here.
                    </div>
                @else
                    <ul class="space-y-4 mt-4">
                        @foreach ($activeProjects as $project)
                            @php
                                $pct = (int) ($project->progress_pct ?? 0);
                                $statusKey = strtolower((string) ($project->status ?? ''));
                                $statusLabel = match ($statusKey) {
                                    'pending' => 'Scheduled',
                                    'awaiting_approval', 'approval', 'needs_approval' => 'Awaiting approval',
                                    'completed', 'closed' => 'Completed',
                                    default => 'In progress',
                                };
                                $nextStep = match ($statusKey) {
                                    'awaiting_approval',
                                    'approval',
                                    'needs_approval'
                                        => 'Next: Waiting on your approval',
                                    'pending' => 'Next: Getting started',
                                    'completed', 'closed' => 'Next: Final wrap-up',
                                    default => 'Next: Team update soon',
                                };
                            @endphp

                            <li class="border border-border-default rounded-xl px-5 py-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-base font-semibold text-text-base truncate">
                                            {{ $project->project_name }}
                                        </p>
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-muted px-2.5 py-0.5 text-[11px] font-medium text-text-subtle mt-2">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                            {{ $statusLabel }}
                                        </span>
                                        <p class="text-xs text-text-subtle mt-2">
                                            {{ $nextStep }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3 text-xs">
                                        <a href="{{ route('portal.projects.show', $project->id) }}"
                                            class="oh-btn oh-btn--primary text-xs px-3 py-1.5"
                                            style="background: rgb(var(--brand-primary)); border-color: rgb(var(--brand-primary));">
                                            View project
                                        </a>
                                        <a href="{{ route('portal.projects.messages.index', $project->id) }}"
                                            class="oh-btn text-xs px-3 py-1.5">
                                            Message team
                                        </a>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs text-text-subtle mb-2">
                                        <span>Progress</span>
                                        <span>{{ $pct }}%</span>
                                    </div>
                                    <div class="h-2.5 w-full bg-surface-muted rounded-full overflow-hidden">
                                        <div class="h-2.5 rounded-full bg-[rgb(var(--brand-primary))]"
                                            style="width: {{ $pct }}%;">
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="space-y-6">
                {{-- Files --}}
                <div class="bg-surface-card rounded-xl border border-border-default shadow-sm p-6">
                    <h2 class="text-lg font-medium text-text-base">Files</h2>
                    <p class="text-text-subtle text-sm mt-1 mb-4">
                        Shared documents stay here for easy access.
                    </p>

                    @if ($uploads->isEmpty())
                        <div class="text-sm text-text-subtle">
                            No files yet. When your team shares documents, they’ll appear here.
                        </div>
                    @else
                        <ul class="space-y-3 text-sm">
                            @foreach ($uploads as $file)
                                <li class="flex justify-between items-center gap-3">
                                    <span class="text-text-base truncate">
                                        {{ $file->original_name ?? $file->filename }}
                                    </span>
                                    <a href="{{ route('portal.files.download', $file) }}"
                                        class="text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))]">
                                        Download
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Activity --}}
                <div class="bg-surface-card rounded-xl border border-border-default shadow-sm p-6">
                    <h2 class="text-lg font-medium text-text-base">Activity</h2>
                    <p class="text-text-subtle text-sm mt-1 mb-4">
                        Recent updates from your team.
                    </p>

                    @if (empty($activities) || count($activities) === 0)
                        <div class="text-sm text-text-subtle">
                            All quiet for now. When your team shares updates, they’ll appear here.
                        </div>
                    @else
                        <ul class="space-y-4">
                            @foreach ($activities as $activity)
                                @php
                                    $label = $activity['label'] ?? '';
                                    $when = $activity['when'] ?? '';
                                @endphp

                                <li class="flex items-start gap-3">
                                    <div class="mt-1 h-2.5 w-2.5 rounded-full bg-[rgb(var(--brand-primary))]"></div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-text-base">{{ $label }}</p>
                                        <p class="text-xs text-text-subtle mt-1">{{ $when }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
