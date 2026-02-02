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
                'completed', 'closed' => 'oh-pill oh-pill--success',
                'awaiting_approval', 'needs_approval', 'approval' => 'oh-pill oh-pill--info',
                'pending' => 'oh-pill',
                default => 'oh-pill',
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

        $clientId = auth('client')->user()?->contact_id;
        $isClientTask = function ($task) use ($clientId) {
            $title = strtolower((string) ($task->title ?? ''));
            $approvalStatus = strtolower((string) ($task->approval_status ?? ''));
            $assignedByContact = $clientId && (int) ($task->contact_id ?? 0) === (int) $clientId;
            $assignedByAssign = ($task->assign_type ?? '') === 'client'
                && $clientId
                && (int) ($task->assign_id ?? 0) === (int) $clientId;
            $clientVisible = (bool) ($task->client_visible ?? false);
            $needsApproval = (bool) ($task->requires_approval ?? false)
                || in_array($approvalStatus, ['needs_approval', 'awaiting_approval', 'approval'], true);

            return ($task->assign_type ?? '') === 'client'
                || $assignedByContact
                || $assignedByAssign
                || $clientVisible
                || str_starts_with($title, 'client:')
                || $needsApproval;
        };

        $isOpenTask = function ($task) {
            $status = strtolower((string) ($task->status ?? ''));
            return ! in_array($status, ['completed', 'closed', 'archived'], true);
        };

        $clientTasks = $phaseGroups
            ->flatMap(fn($group) => $group['tasks'] ?? [])
            ->filter($isClientTask)
            ->values();

        $actionTasks = $clientTasks->filter($isOpenTask)->values();
        $completedTasks = $clientTasks->reject($isOpenTask)->values();

        $clientDueCount = $actionTasks->filter(fn($task) => !empty($task->due_date))->count();
        $progressPct = (int) ($project->progress_pct ?? 0);
        $nonEmptyPhases = $phaseGroups->filter(fn($group) => !empty($group['tasks']));
        $emptyPhases = $phaseGroups->filter(fn($group) => empty($group['tasks']));
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-text-base">{{ $project->project_name }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Updates, files, and invoices in one place.
                </p>
                @if ($updatedLabel)
                    <p class="text-xs text-text-subtle mt-2">Last updated {{ $updatedLabel }}.</p>
                @endif
            </div>

            @php
                $statusValue = strtolower((string) ($project->status ?? ''));
                $isActiveProject = !in_array(
                    $statusValue,
                    ['closed', 'completed', 'complete', 'archived', 'canceled', 'cancelled'],
                    true
                );
            @endphp
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('portal.projects.index') }}" class="oh-btn text-sm px-3 py-2">
                    Back to projects
                </a>
                @if ($isActiveProject)
                    <a href="{{ route('portal.projects.index', ['chat_project' => $project->id]) }}#project-chat-{{ $project->id }}"
                        class="oh-btn oh-btn--primary text-sm px-3 py-2">
                        Message team
                    </a>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('portal.files.index'))
                    <a href="{{ route('portal.files.index') }}" class="oh-btn text-sm px-3 py-2">
                        Files
                    </a>
                @endif
            </div>
        </div>

        {{-- Status strip --}}
        <div class="oh-card px-6 py-3.5">
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
                        <i class="fa-solid fa-list-check text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Your tasks due</p>
                        <p class="text-xl font-semibold text-text-base">{{ $clientDueCount }}</p>
                    </div>
                </div>

                <div class="h-6 w-px bg-border-default hidden sm:block"></div>

                <a href="#completed-tasks"
                    data-scroll-target="completed-tasks"
                    class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center bg-surface-muted text-text-subtle">
                        <i class="fa-regular fa-circle-check text-xs"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Completed</p>
                        <p class="text-xl font-semibold text-text-base">{{ $completedTasks->count() }}</p>
                    </div>
                </a>

            </div>
        </div>

        @php
            $formatDue = function ($date) {
                return $date ? 'Due ' . $date->format('M j, Y') : null;
            };
            $excerpt = function ($text, $limit = 120) {
                $clean = trim((string) $text);
                if ($clean === '') {
                    return null;
                }
                return strlen($clean) > $limit ? substr($clean, 0, $limit - 3) . '...' : $clean;
            };
            $formatCompleted = function ($task) {
                $date = $task->completed_at ?? $task->updated_at ?? null;
                if (! $date) {
                    return null;
                }
                if ($date instanceof \Illuminate\Support\Carbon) {
                    return $date->format('M j, Y');
                }
                if (is_string($date)) {
                    try {
                        return \Illuminate\Support\Carbon::parse($date)->format('M j, Y');
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return null;
            };
            $messageTeamUrl = route('portal.projects.index', ['chat_project' => $project->id]) . '#project-chat-' . $project->id;
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Main --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Action needed --}}
                <details
                    class="oh-card p-6 border-2 border-[rgb(var(--brand-primary))]/10 bg-[rgba(var(--brand-primary),0.03)]"
                    open>
                    <summary
                        class="flex items-start justify-between gap-3 cursor-pointer select-none">
                        <div class="flex flex-col gap-1">
                            <h2 class="text-base font-semibold text-text-base">Action needed from you</h2>
                            <p class="text-sm text-text-subtle">
                                Tasks that need your attention right now.
                            </p>
                        </div>
                        <span class="oh-pill text-[11px]">
                            {{ $actionTasks->count() }}
                        </span>
                    </summary>

                    <div class="mt-4 space-y-5">
                        @if ($actionTasks->isEmpty())
                            <div class="rounded-xl border border-border-default bg-surface-card p-5 text-sm text-text-subtle">
                                You’re all set for now. We’ll place anything that needs your input here.
                                <div class="mt-3">
                                    <a href="{{ $messageTeamUrl }}" class="oh-btn oh-btn--primary text-sm px-3 py-2">
                                        Message team
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="grid gap-3">
                                @foreach ($actionTasks as $index => $task)
                                    @php
                                        $dueLabel = $formatDue($task->due_date ?? null);
                                        $taskExcerpt = $excerpt($task->description ?? null);
                                    @endphp
                                    <div class="rounded-xl border border-border-default bg-surface-card p-4 sm:p-5 flex flex-col gap-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-text-base">
                                                    {{ ($index + 1) . '. ' . ($task->title ?? 'Task') }}
                                                </p>
                                                @if ($dueLabel)
                                                    <p class="text-xs text-text-subtle mt-1">{{ $dueLabel }}</p>
                                                @endif
                                            </div>
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusTone($task->status ?? 'open') }}">
                                                {{ $taskStatusLabel($task) }}
                                            </span>
                                        </div>
                                        @if ($taskExcerpt)
                                            <p class="text-xs text-text-subtle leading-relaxed">{{ $taskExcerpt }}</p>
                                        @endif
                                        <div class="flex items-center justify-end">
                                            <a href="{{ route('portal.projects.index', ['chat_project' => $project->id, 'task' => $task->id]) }}#project-chat-{{ $project->id }}"
                                                class="oh-btn text-xs px-3 py-1.5">
                                                Message about task
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>

                <details class="oh-card p-5" id="completed-tasks">
                    <summary
                        class="flex items-center justify-between gap-3 cursor-pointer select-none text-sm font-semibold text-text-base">
                        <span>Completed tasks</span>
                        <span class="oh-pill text-[11px]">
                            {{ $completedTasks->count() }}
                        </span>
                    </summary>
                    <div class="mt-4 grid gap-3">
                        @forelse ($completedTasks as $task)
                            @php
                                $completedLabel = $formatCompleted($task);
                            @endphp
                            <div class="rounded-lg border border-border-default bg-surface-card px-4 py-3">
                                <p class="text-sm font-medium text-text-base">{{ $task->title ?? 'Task' }}</p>
                                @if ($completedLabel)
                                    <p class="text-xs text-text-subtle mt-1">Completed {{ $completedLabel }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-border-default bg-surface-card px-4 py-3 text-xs text-text-subtle">
                                No completed tasks yet.
                            </div>
                        @endforelse
                    </div>
                </details>

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
                        <a href="{{ route('portal.projects.index', ['chat_project' => $project->id]) }}#project-chat-{{ $project->id }}" class="oh-btn text-sm px-3 py-2">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-scroll-target]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const targetId = link.getAttribute('data-scroll-target');
                    const target = targetId ? document.getElementById(targetId) : null;
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    if (target.tagName.toLowerCase() === 'details') {
                        target.setAttribute('open', 'open');
                    }
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        });
    </script>
@endsection
