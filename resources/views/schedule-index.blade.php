@extends('layouts.app')

@section('title', 'My Schedule')

@section('content')
    @php
        $weekRange = $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y');
        $estimatedHours = $estimatedMinutesWeek ? round($estimatedMinutesWeek / 60, 1) : 0;
        $loggedHours = round($loggedHoursWeek, 1);
        $statusPillClass = function ($status) {
            return match (strtolower((string) $status)) {
                'completed' => 'oh-pill--success',
                'in_progress', 'in progress' => 'oh-pill--info',
                'blocked' => 'oh-pill--danger',
                default => 'oh-pill--muted',
            };
        };
        $viewMode = $viewMode ?? 'today';
        $sections = $viewMode === 'week'
            ? [
                'Overdue' => $overdueTasks,
                'Today' => $todayTasks,
                'This Week' => $thisWeekTasks,
                'Later' => $laterTasks,
            ]
            : [
                'Overdue' => $overdueTasks,
                'Today' => $todayTasks,
            ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <header class="space-y-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">My Schedule</h1>
                    <p class="text-sm text-text-subtle">Today → {{ $weekRange }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.schedule.index', ['tenant' => $tenant->id, 'view' => 'today']) }}"
                        class="oh-pill {{ $viewMode === 'today' ? 'oh-pill--info' : 'oh-pill--muted' }}">
                        Today
                    </a>
                    <a href="{{ route('tenant.schedule.index', ['tenant' => $tenant->id, 'view' => 'week']) }}"
                        class="oh-pill {{ $viewMode === 'week' ? 'oh-pill--info' : 'oh-pill--muted' }}">
                        This Week
                    </a>
                </div>
            </div>
        </header>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-4">
                <p class="text-xs text-text-subtle">Assigned tasks</p>
                <div class="mt-2 text-2xl font-semibold text-text-base">{{ $assignedCount }}</div>
            </div>
            <div class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-4">
                <p class="text-xs text-text-subtle">Overdue tasks</p>
                <div class="mt-2 text-2xl font-semibold text-text-base">{{ $overdueCount }}</div>
            </div>
            <div class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-4">
                <p class="text-xs text-text-subtle">Estimated time (this week)</p>
                <div class="mt-2 text-2xl font-semibold text-text-base">{{ $estimatedHours }}h</div>
            </div>
            <div class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-4">
                <p class="text-xs text-text-subtle">Logged time (this week)</p>
                <div class="mt-2 text-2xl font-semibold text-text-base">{{ $loggedHours }}h</div>
            </div>
        </div>

        @if ($overCapacity)
            <div class="oh-card border border-amber-200 bg-amber-50 text-amber-700 p-4">
                Estimated work this week exceeds your target of {{ $workPreference->weekly_target_hours }} hours.
            </div>
        @endif

        <div class="space-y-6">
            @foreach ($sections as $label => $tasks)
                @php
                    $isCollapsible = in_array($label, ['This Week', 'Later'], true);
                    $showMobileCollapsed = $isCollapsible && $viewMode === 'week';
                @endphp
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-text-base">{{ $label }}</h2>
                        <span class="text-xs text-text-subtle">{{ $tasks->count() }} tasks</span>
                    </div>

                    @if ($tasks->isEmpty())
                        <div class="mt-4 text-sm text-text-subtle">
                            No {{ strtolower($label) }} tasks assigned.
                        </div>
                    @else
                        <div class="mt-4 hidden md:block">
                            <table class="min-w-full text-sm">
                                <thead class="text-text-subtle">
                                    <tr class="border-b border-border-default/60">
                                        <th class="py-2 text-left font-medium">Task</th>
                                        <th class="py-2 text-left font-medium">Project</th>
                                        <th class="py-2 text-left font-medium">Due</th>
                                        <th class="py-2 text-left font-medium">Status</th>
                                        <th class="py-2 text-right font-medium">Est.</th>
                                        <th class="py-2 text-right font-medium">Logged</th>
                                        <th class="py-2 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                                    @foreach ($tasks as $task)
                                        <tr>
                                            <td class="py-2 text-text-base">
                                                {{ $task->title ?? 'Untitled task' }}
                                            </td>
                                            <td class="py-2 text-text-subtle">
                                                {{ $task->project?->project_name ?? '—' }}
                                            </td>
                                            <td class="py-2 text-text-subtle">
                                                {{ $task->due_date ? $task->due_date->format('M j') : '—' }}
                                            </td>
                                            <td class="py-2">
                                                <span class="oh-pill {{ $statusPillClass($task->status) }}">
                                                    {{ ucfirst($task->status ?? 'todo') }}
                                                </span>
                                            </td>
                                            <td class="py-2 text-right text-text-base tabular-nums">
                                                {{ $task->estimated_minutes ? $task->estimated_minutes . 'm' : '—' }}
                                            </td>
                                            <td class="py-2 text-right text-text-base tabular-nums">
                                                {{ $task->logged_hours ? number_format($task->logged_hours * 60, 0) . 'm' : '—' }}
                                            </td>
                                            <td class="py-2 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <a class="oh-btn h-8 px-3 text-xs"
                                                        href="{{ route('tenant.tasks.show', ['tenant' => $tenant->id, 'task' => $task->id]) }}">
                                                        View
                                                    </a>
                                                    <a class="oh-btn h-8 px-3 text-xs"
                                                        href="{{ route('tenant.time.create', ['tenant' => $tenant->id, 'project_id' => $task->project_id, 'task_id' => $task->id]) }}">
                                                        Log time
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 space-y-3 md:hidden">
                            @if ($showMobileCollapsed)
                                <details class="rounded-xl border border-border-default/60 bg-surface-muted/40">
                                    <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-text-base">
                                        <span>{{ $label }}</span>
                                        <span class="text-xs text-text-subtle">{{ $tasks->count() }} tasks</span>
                                    </summary>
                                    <div class="space-y-3 px-4 pb-4">
                                        @foreach ($tasks as $task)
                                            <div class="rounded-xl border border-border-default/60 bg-surface-card/95 p-4 space-y-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <h3 class="text-sm font-semibold text-text-base">
                                                            {{ $task->title ?? 'Untitled task' }}
                                                        </h3>
                                                        <p class="text-xs text-text-subtle">
                                                            {{ $task->project?->project_name ?? '—' }}
                                                        </p>
                                                    </div>
                                                    <span class="oh-pill {{ $statusPillClass($task->status) }}">
                                                        {{ ucfirst($task->status ?? 'todo') }}
                                                    </span>
                                                </div>
                                                <div class="text-xs text-text-subtle">
                                                    Due {{ $task->due_date ? $task->due_date->format('M j') : '—' }}
                                                </div>
                                                <div class="flex items-center justify-between text-xs text-text-subtle">
                                                    <span>Est. {{ $task->estimated_minutes ? $task->estimated_minutes . 'm' : '—' }}</span>
                                                    <span>Logged {{ $task->logged_hours ? number_format($task->logged_hours * 60, 0) . 'm' : '—' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <a class="oh-btn h-8 px-3 text-xs"
                                                        href="{{ route('tenant.tasks.show', ['tenant' => $tenant->id, 'task' => $task->id]) }}">
                                                        View task
                                                    </a>
                                                    <a class="oh-btn h-8 px-3 text-xs"
                                                        href="{{ route('tenant.time.create', ['tenant' => $tenant->id, 'project_id' => $task->project_id, 'task_id' => $task->id]) }}">
                                                        Log time
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                                @continue
                            @endif
                            @foreach ($tasks as $task)
                                <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-text-base">
                                                {{ $task->title ?? 'Untitled task' }}
                                            </h3>
                                            <p class="text-xs text-text-subtle">
                                                {{ $task->project?->project_name ?? '—' }}
                                            </p>
                                        </div>
                                        <span class="oh-pill {{ $statusPillClass($task->status) }}">
                                            {{ ucfirst($task->status ?? 'todo') }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-text-subtle">
                                        Due {{ $task->due_date ? $task->due_date->format('M j') : '—' }}
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-text-subtle">
                                        <span>Est. {{ $task->estimated_minutes ? $task->estimated_minutes . 'm' : '—' }}</span>
                                        <span>Logged {{ $task->logged_hours ? number_format($task->logged_hours * 60, 0) . 'm' : '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a class="oh-btn h-8 px-3 text-xs"
                                            href="{{ route('tenant.tasks.show', ['tenant' => $tenant->id, 'task' => $task->id]) }}">
                                            View task
                                        </a>
                                        <a class="oh-btn h-8 px-3 text-xs"
                                            href="{{ route('tenant.time.create', ['tenant' => $tenant->id, 'project_id' => $task->project_id, 'task_id' => $task->id]) }}">
                                            Log time
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection
