@extends('layouts.portal')

@section('title', 'Project Details')

@section('content')
    @php
        $statusLabel = function ($status) {
            $key = strtolower((string) $status);
            return match ($key) {
                'pending' => 'Scheduled',
                'awaiting_approval', 'needs_approval', 'approval' => 'Awaiting approval',
                'completed', 'closed' => 'Completed',
                default => 'In progress',
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

        $phaseGroups = collect($phaseGroups ?? []);
        $tasksVisible = $phaseGroups->sum(fn($group) => count($group['tasks'] ?? []));
        $currentPhaseName = data_get($currentPhase, 'name') ?? '—';
        $nextTask = data_get($currentPhase, 'tasks.0');
        $updatedLabel = $project->updated_at ? $project->updated_at->format('M j, Y') : null;
        $tenantName = $project->tenant?->name ?? 'your provider';

        $taskStatusLabel = function ($task) {
            $key = strtolower((string) ($task->status ?? 'open'));
            return match ($key) {
                'completed', 'closed' => 'Completed',
                'pending' => 'Scheduled',
                'awaiting_approval', 'needs_approval', 'approval' => 'Awaiting approval',
                default => 'In progress',
            };
        };

        $isActionTask = function ($task) {
            $title = strtolower((string) ($task->title ?? ''));
            $status = strtolower((string) ($task->status ?? ''));
            return ($task->assign_type ?? '') === 'client'
                || str_starts_with($title, 'client:')
                || in_array($status, ['needs_approval', 'awaiting_approval', 'approval'], true);
        };

        $actionTasks = $phaseGroups
            ->flatMap(fn($group) => $group['tasks'] ?? [])
            ->filter($isActionTask)
            ->values();

        $clientDueCount = $actionTasks->filter(fn($task) => !empty($task->due_date))->count();
        $progressPct = (int) ($project->progress_pct ?? 0);
        $nonEmptyPhases = $phaseGroups->filter(fn($group) => !empty($group['tasks']));
        $emptyPhases = $phaseGroups->filter(fn($group) => empty($group['tasks']));
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $project->project_name }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    A calm view of progress, tasks, and updates.
                </p>
                <div class="mt-2 flex items-start gap-2 text-sm text-text-subtle">
                    <span class="mt-1.5 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    <div class="leading-5">
                        <span class="text-text-subtle">Live status shared by {{ $tenantName }}.</span>
                        @if ($updatedLabel)
                            <span class="block text-xs text-text-subtle mt-1">Last updated {{ $updatedLabel }}.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('portal.projects.index') }}" class="oh-btn text-sm px-3 py-2">
                    Back to projects
                </a>
                <a href="{{ route('portal.projects.messages.index', $project->id) }}"
                    class="oh-btn oh-btn--primary text-sm px-3 py-2"
                    style="background: rgb(var(--brand-primary)); border-color: rgb(var(--brand-primary));">
                    Message team
                </a>
                @if (\Illuminate\Support\Facades\Route::has('portal.files.index'))
                    <a href="{{ route('portal.files.index') }}" class="oh-btn text-sm px-3 py-2">
                        Files
                    </a>
                @endif
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
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Status</p>
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusTone($project->status ?? 'open') }}">
                            {{ $statusLabel($project->status ?? 'open') }}
                        </span>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-solid fa-chart-simple text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Progress</p>
                        <p class="text-sm font-semibold text-text-base">{{ $progressPct }}%</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-solid fa-layer-group text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Current phase</p>
                        <p class="text-sm font-semibold text-text-base">{{ $currentPhaseName }}</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-list-check text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Your tasks due</p>
                        <p class="text-xl font-semibold text-text-base">{{ $clientDueCount }}</p>
                    </div>
                </div>

                @if ($nextTask)
                    <div class="ml-auto text-xs text-text-subtle hidden lg:block">
                        Next up: <span class="text-text-base">{{ $nextTask->title ?? 'Task' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Main --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Action needed --}}
                <div class="oh-card p-6 space-y-4">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-base font-semibold text-text-base">Action needed from you</h2>
                        <p class="text-sm text-text-subtle">
                            Tasks that need your attention right now.
                        </p>
                    </div>

                    @if ($actionTasks->isEmpty())
                        <div class="rounded-xl border border-border-default bg-surface-card p-5 text-sm text-text-subtle">
                            You’re all set for now. We’ll place anything that needs your input here.
                        </div>
                    @else
                        <div class="divide-y divide-border-default rounded-xl border border-border-default">
                            @foreach ($actionTasks as $task)
                                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-text-base">{{ $task->title ?? 'Task' }}</p>
                                        <p class="text-xs text-text-subtle">
                                            {{ $task->due_date ? 'Due ' . $task->due_date->format('M j, Y') : 'No due date set' }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusTone($task->status ?? 'open') }}">
                                        {{ $taskStatusLabel($task) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right rail --}}
            <aside class="space-y-4">
                <div class="oh-card p-5 space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-text-base">Project status</h3>
                        <p class="text-xs text-text-subtle mt-1">
                            {{ $statusLabel($project->status ?? 'open') }}
                        </p>
                    </div>
                    <div class="h-2.5 w-full bg-surface-muted rounded-full overflow-hidden">
                        <div class="h-2.5 rounded-full bg-[rgb(var(--brand-primary))]"
                            style="width: {{ $progressPct }}%;"></div>
                    </div>
                    <p class="text-xs text-text-subtle">Progress {{ $progressPct }}%</p>
                </div>

                <div class="oh-card p-5 space-y-2">
                    <h3 class="text-sm font-semibold text-text-base">Next step</h3>
                    <p class="text-sm text-text-subtle">
                        {{ $nextTask?->title ?? 'Your provider will share the next update here.' }}
                    </p>
                </div>

                <div class="oh-card p-5 space-y-2">
                    <h3 class="text-sm font-semibold text-text-base">Quick links</h3>
                    <div class="flex flex-col gap-2 text-sm">
                        <a href="{{ route('portal.projects.messages.index', $project->id) }}" class="oh-btn text-sm px-3 py-2">
                            Messages
                        </a>
                        @if (\Illuminate\Support\Facades\Route::has('portal.files.index'))
                            <a href="{{ route('portal.files.index') }}" class="oh-btn text-sm px-3 py-2">
                                Files
                            </a>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('portal.invoices.index'))
                            <a href="{{ route('portal.invoices.index') }}" class="oh-btn text-sm px-3 py-2">
                                Invoices
                            </a>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script></script>
@endsection
