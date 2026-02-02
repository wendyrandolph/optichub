@extends('layouts.app')

@section('content')
    @php
        $tenantId = $tenant->id ?? (auth()->user()?->tenant_id ?? tenant('id'));
        $projectId = data_get($project, 'id', 0);
        $projectName = data_get($project, 'project_name', data_get($project, 'name', ''));
        $description = data_get($project, 'description', '');

        $formatDate = static function ($value, string $format = 'M j, Y') {
            if (empty($value)) {
                return null;
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->format($format);
            } catch (\Exception $e) {
                return null;
            }
        };

        $startLabel = $formatDate(data_get($project, 'start_date', data_get($project, 'startDate'))) ?? '—';

        $phasesList = $phases ?? [];

        // Status numbers (use your actual variables)
        $progress = $progress ?? (int) data_get($project, 'percent_complete', 0);
        $openTasks = $openTasks ?? (int) data_get($counts ?? [], 'open', 0);
        $overdueTasks = $overdueTasks ?? (int) data_get($counts ?? [], 'overdue', 0);
        $blockedTasks = $blockedTasks ?? (int) data_get($counts ?? [], 'blocked', 0);

        $companyLabel = $project->company->company_name ?? ($project->contact->company_name ?? 'N/A');
        $contactLabel = $project->contact?->firstName ?? 'N/A';
    @endphp

    <div class="oh-page">
        @if (session('success_message'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        {{-- Header card (unified) --}}
        <header class="oh-card oh-card--soft">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Project</p>
                    <h1 class="mt-1 text-2xl md:text-3xl font-semibold text-text-base leading-tight truncate">
                        {{ $projectName }}
                    </h1>

                    {{-- Meta pills (tokenized) --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="oh-pill">
                            <i class="fa-regular fa-calendar"></i>
                            Start: {{ $startLabel }}
                        </span>

                        <span class="oh-pill">
                            <i class="fa-regular fa-user"></i>
                            Contact: {{ $contactLabel }}
                        </span>

                        <span class="oh-pill">
                            <i class="fa-regular fa-building"></i>
                            Company: {{ $companyLabel }}
                        </span>
                    </div>

                    @if (!empty($description))
                        <p class="mt-3 text-sm text-text-subtle max-w-3xl">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                {{-- Header actions --}}
                <div class="flex flex-col gap-2 md:items-end">
                    <a href="{{ route('tenant.tasks.create', ['tenant' => $tenant, 'project_id' => $project->id]) }}"
                        class="oh-btn oh-btn--primary">
                        <i class="fa-solid fa-plus text-[12px]"></i>
                        Add Task
                    </a>

                    <a href="{{ route('tenant.projects.edit', ['tenant' => $tenant, 'project' => $project->id]) }}"
                        class="oh-btn oh-btn--ghost" title="Edit project" aria-label="Edit project">
                        <i class="fa-solid fa-pen text-[12px]"></i>
                        Edit Project
                    </a>

                    <a href="{{ route('tenant.chat.project', ['tenant' => $tenant, 'project' => $project->id]) }}"
                        class="oh-btn oh-btn--ghost">
                        <i class="fa-solid fa-comments text-[12px]"></i>
                        Project Chat
                    </a>

                    @if (($project->status ?? '') !== 'closed')
                        <form method="POST"
                            action="{{ route('tenant.projects.complete', ['tenant' => $tenant, 'project' => $project->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--success">
                                <i class="fa-solid fa-flag-checkered text-[12px]"></i>
                                Mark Completed
                            </button>
                        </form>
                    @else
                        <form method="POST"
                            action="{{ route('tenant.projects.reopen', ['tenant' => $tenant, 'project' => $project->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn">
                                <i class="fa-solid fa-rotate-left text-[12px]"></i>
                                Reopen
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </header>

        {{-- Main layout --}}
        <div class="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

            {{-- Left: Status + Assigned Tasks --}}
            <section class="xl:col-span-2 space-y-6">
                <div class="oh-card">
                    <h2 class="text-sm font-semibold text-text-base">Status</h2>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="oh-stat">
                            <div class="oh-stat__label">Progress</div>
                            <div class="oh-stat__value tabular-nums">{{ $progress ?? 0 }}%</div>
                        </div>
                        <div class="oh-stat">
                            <div class="oh-stat__label">Open Tasks</div>
                            <div class="oh-stat__value tabular-nums">{{ $openTasks ?? 0 }}</div>
                        </div>
                        <div class="oh-stat">
                            <div class="oh-stat__label">Overdue</div>
                            <div class="oh-stat__value tabular-nums">{{ $overdueTasks ?? 0 }}</div>
                        </div>
                        <div class="oh-stat">
                            <div class="oh-stat__label">Blocked</div>
                            <div class="oh-stat__value tabular-nums">{{ $blockedTasks ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="oh-card">
                    <h2 class="text-sm font-semibold text-text-base">Time summary</h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-border-default/70 bg-surface-card px-3 py-3">
                            <div class="text-xs text-text-subtle">Total</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($totalHours ?? 0, 2) }}h
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card px-3 py-3">
                            <div class="text-xs text-text-subtle">Billable</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($projectBillableHours ?? 0, 2) }}h
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card px-3 py-3">
                            <div class="text-xs text-text-subtle">Non-billable</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($projectNonBillableHours ?? 0, 2) }}h
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $signal = $profitability['signal'] ?? 'neutral';
                    $signalLabel = match ($signal) {
                        'healthy' => 'Healthy',
                        'drifting' => 'Drifting',
                        'time-heavy' => 'Time-heavy',
                        default => 'Tracking',
                    };
                    $signalCopy = match ($signal) {
                        'healthy' => 'Projected rate meets or exceeds your target.',
                        'drifting' => 'Projected rate sits near your goal—keep an eye on scope.',
                        'time-heavy' => 'Projected rate trails the target; extra hours are stacking up.',
                        default => 'Earning rate will update as more time logs come in.',
                    };
                    $gap = $profitability['target_gap'] ?? null;
                @endphp

                <div class="oh-card space-y-4">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Profitability snapshot</p>
                            <h3 class="text-lg font-semibold text-text-base">Project billing + hours</h3>
                        </div>
                        <span class="oh-pill {{ $signal === 'healthy' ? 'oh-pill--success' : ($signal === 'drifting' ? 'oh-pill--warning' : 'oh-pill--muted') }}">
                            {{ $signalLabel }}
                        </span>
                    </div>

                    <div class="grid gap-3 grid-cols-2 md:grid-cols-3 xl:grid-cols-4 text-sm">
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Project fee</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['project_fee'] ? '$' . number_format($profitability['project_fee'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">External costs</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['external_costs'] ? '$' . number_format($profitability['external_costs'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Budgeted hours</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($profitability['budgeted_hours'] ?? 0, 2) }}h
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Invoiced total</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['invoiced_total'] ? '$' . number_format($profitability['invoiced_total'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Paid total</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['paid_total'] ? '$' . number_format($profitability['paid_total'], 2) : '—' }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Actual hours</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($profitability['actual_hours'] ?? 0, 2) }}h
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Billable hours</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ number_format($profitability['billable_hours'] ?? 0, 2) }}h
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Current earning rate</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['ehr'] ? '$' . number_format($profitability['ehr'], 2) : '—' }}/hr
                            </div>
                        </div>
                        <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                            <div class="text-xs text-text-subtle">Projected rate</div>
                            <div class="text-base font-semibold text-text-base tabular-nums">
                                {{ $profitability['forecast_ehr'] ? '$' . number_format($profitability['forecast_ehr'], 2) : '—' }}/hr
                            </div>
                            @if ($profitability['target_rate'] ?? 0)
                                <div class="text-xs text-text-subtle mt-1">
                                    {{ $gap !== null && $gap > 0 ? 'Below target by $' . number_format($gap, 2) : 'At or above target' }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <p class="text-sm text-text-subtle">{{ $signalCopy }}</p>
                </div>

                @if (($project->billing_model ?? 'fixed') === 'fixed')
                    <div class="oh-card space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Payment plan</p>
                                <h3 class="text-lg font-semibold text-text-base">Milestone invoices</h3>
                            </div>
                            <a href="{{ route('tenant.projects.edit', ['tenant' => $tenant->id ?? $tenant, 'project' => $project->id]) }}#billing"
                                class="oh-btn oh-btn--ghost">Manage</a>
                        </div>
                        @if (($milestoneInvoices ?? collect())->isEmpty())
                            <p class="text-sm text-text-subtle">No milestone invoices yet.</p>
                        @else
                            <div class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                                @foreach ($milestoneInvoices as $milestone)
                                    <div class="flex flex-wrap items-center justify-between gap-3 py-2">
                                        <div>
                                            <div class="text-sm font-semibold text-text-base">
                                                {{ $milestone->milestone_label ?: 'Milestone' }}
                                            </div>
                                            <div class="text-xs text-text-subtle">
                                                Due {{ $milestone->due_date ? $milestone->due_date->format('M j, Y') : '—' }}
                                                · {{ ucfirst($milestone->status ?? 'draft') }}
                                            </div>
                                        </div>
                                        <div class="text-sm font-semibold text-text-base tabular-nums">
                                            {{ $milestone->total_amount ? '$' . number_format($milestone->total_amount, 2) : '—' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                <section class="xl:col-span-3">
                    <div class="oh-card" id="assigned-tasks">

                        <div
                            class="rounded-xl border border-border-default/70 bg-surface-muted/60 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-text-base">Assigned Tasks</h2>
                                @php
                                    $firstPending = collect($tasksForView ?? [])->first(function ($task) {
                                        $status = strtolower($task['approval_status'] ?? '');
                                        return ($task['requires_approval'] ?? false) &&
                                            (empty($status) || $status === 'pending');
                                    });
                                    $firstPendingId = $firstPending ? 'task-row-' . $firstPending['id'] : null;
                                @endphp
                                @if (($pendingApprovals ?? 0) > 0)
                                    <a href="{{ $firstPendingId ? '#' . $firstPendingId : '#assigned-tasks' }}"
                                        class="oh-pill oh-pill--warning inline-flex items-center gap-1">
                                        <i class="fa-regular fa-flag text-xs"></i>
                                        Approvals pending ({{ $pendingApprovals }})
                                    </a>
                                @endif
                            </div>

                            <div class="text-sm text-text-subtle">
                                Total hours:
                                <span
                                    class="font-semibold text-text-base tabular-nums">{{ number_format($totalHours ?? 0, 2) }}</span>
                            </div>
                        </div>

                        @php
                            $palette = [
                                '#1F3C66',
                                '#2E5D95',
                                '#5C89B5',
                                '#A3C1DD',
                                '#EA7D51',
                                '#F28B7D',
                                '#68A7A1',
                                '#9EB5A6',
                            ];
                            $assigneeColors = [];
                            $assigneeInitials = function (?string $name): string {
                                $parts = collect(explode(' ', trim($name ?? '')))
                                    ->filter()
                                    ->map(fn($p) => mb_substr($p, 0, 1));
                                return strtoupper($parts->take(2)->implode('') ?: '–');
                            };

                            foreach ($tasksForView ?? [] as $t) {
                                $key = $t['assign_id'] ?? ($t['assign_name'] ?? null);
                                if ($key === null) {
                                    continue;
                                }
                                if (!isset($assigneeColors[$key])) {
                                    $idx = abs(crc32((string) $key)) % count($palette);
                                    $assigneeColors[$key] = $palette[$idx];
                                }
                            }

                            $backUrl = route('tenant.projects.show', [
                                'tenant' => $tenant->id ?? $tenant,
                                'project' => $project->id,
                            ]);
                        @endphp

                        {{-- Assignee key --}}
                        @if (!empty($assigneeColors))
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-text-subtle">
                                <span class="font-semibold text-text-base mr-2">Assignee key:</span>
                                @foreach ($assigneeColors as $key => $color)
                                    @php
                                        $name =
                                            collect($tasksForView)->firstWhere('assign_id', $key)['assign_name'] ??
                                            (collect($tasksForView)->firstWhere('assign_name', $key)['assign_name'] ??
                                                'Unknown');
                                    @endphp
                                    <span class="oh-pill inline-flex items-center gap-2">
                                        <span
                                            class="h-6 w-6 rounded-full grid place-items-center text-xs font-semibold text-white"
                                            style="background: {{ $color }};">
                                            {{ $assigneeInitials($name) }}
                                        </span>
                                        <span class="text-text-subtle text-xs">{{ $name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($tasksForView) && count($tasksForView))
                            {{-- Mobile: cards --}}
                            <div class="mt-4 grid gap-3 md:hidden">
                                @foreach ($tasksForView as $t)
                                    @php
                                        $isClient = ($t['assign_type'] ?? '') === 'client';
                                        $name = $t['assign_name'] ?? ($isClient ? 'Client' : 'Unassigned');
                                        $color =
                                            $assigneeColors[$t['assign_id'] ?? ($t['assign_name'] ?? null)] ??
                                            $palette[0];

                                        $started = $t['started_at']
                                            ? \Carbon\Carbon::parse($t['started_at'])->format('M j, Y')
                                            : null;
                                        $completed = $t['completed_at']
                                            ? \Carbon\Carbon::parse($t['completed_at'])->format('M j, Y')
                                            : null;

                                        $status = strtolower($t['status'] ?? 'open');
                                        $statusPill = match ($status) {
                                            'completed' => 'oh-pill--success',
                                            'blocked' => 'oh-pill--danger',
                                            'overdue' => 'oh-pill--warning',
                                            'in_progress', 'in progress' => 'oh-pill--info',
                                            default => '',
                                        };

                                        $approvalRequired = (bool) ($t['requires_approval'] ?? false);
                                        $approvalStatus = strtolower($t['approval_status'] ?? '');
                                        $approvalLabel = $approvalStatus ?: 'pending';
                                        $approvalClass = match ($approvalLabel) {
                                            'approved' => 'oh-pill--success',
                                            'changes_requested' => 'oh-pill--danger',
                                            default => 'oh-pill--accent',
                                        };
                                    @endphp

                                    <div class="rounded-2xl border border-border-default/70 bg-surface-card p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="font-semibold text-text-base leading-snug">
                                                    {{ $t['title'] ?? 'Task' }}
                                                </div>

                                                <div class="mt-2 flex items-center gap-2 text-xs text-text-subtle">
                                                    <span
                                                        class="h-6 w-6 rounded-full grid place-items-center text-xs font-semibold text-white"
                                                        style="background: {{ $color }};">
                                                        {{ $assigneeInitials($name) }}
                                                    </span>
                                                    <span class="truncate">{{ $name }}</span>
                                                </div>
                                            </div>

                                            <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant->id ?? $tenant, 'edit' => $t['id'], 'back' => $backUrl]) }}"
                                                class="oh-icon-btn"
                                                aria-label="Edit task">
                                                <i class="fa-solid fa-pen text-[12px]"></i>
                                            </a>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <span class="oh-pill {{ $isClient ? 'oh-pill--info' : '' }}">
                                                {{ $isClient ? 'Client' : 'Admin/Internal' }}
                                            </span>

                                            <span class="oh-pill {{ $statusPill }}">
                                                {{ str_replace('_', ' ', $t['status'] ?? 'open') }}
                                            </span>

                                            @if ($approvalRequired)
                                                <span class="oh-pill {{ $approvalClass }}">
                                                    Approval: {{ str_replace('_', ' ', $approvalLabel) }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-3 space-y-1 text-xs text-text-subtle">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-regular fa-circle-play text-xs opacity-70"></i>
                                                <span>{{ $started ? "Started: {$started}" : 'Not started' }}</span>
                                            </div>

                                            @if ($completed)
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-regular fa-circle-check text-xs opacity-70"></i>
                                                    <span>Completed: {{ $completed }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Mobile approvals --}}
                                        @if ($approvalRequired && $approvalLabel === 'pending')
                                            <details class="mt-3">
                                                <summary
                                                    class="text-sm font-semibold text-text-base cursor-pointer select-none">
                                                    Review approval
                                                </summary>

                                                <div
                                                    class="mt-3 rounded-xl border border-border-default/70 bg-surface-muted/60 p-3 space-y-3">
                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.approve', ['tenant' => $tenant->id ?? $tenant, 'task' => $t['id']]) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="oh-btn oh-btn--primary w-full justify-center">
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.request_changes', ['tenant' => $tenant->id ?? $tenant, 'task' => $t['id']]) }}"
                                                        class="space-y-2">
                                                        @csrf
                                                        <input type="text" name="note" class="oh-input w-full"
                                                            placeholder="Optional note (short)" />
                                                        <button type="submit" class="oh-btn w-full justify-center">
                                                            Request changes
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Desktop: table --}}
                            <div class="hidden md:block mt-4">
                                <div class="rounded-2xl border border-border-default/70 bg-surface-card overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-surface-muted/50">
                                                <tr class="text-left text-text-subtle border-b border-border-default/70">
                                                    <th class="px-4 py-3 w-6/12">Task &amp; Details</th>
                                                    <th class="px-4 py-3 w-2/12">Assignee</th>
                                                    <th class="px-4 py-3 w-2/12">Status</th>
                                                    <th class="px-4 py-3 w-2/12 text-right">Hours</th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-border-default/60">
                                                @foreach ($tasksForView as $t)
                                                    @php
                                                        $started = $t['started_at']
                                                            ? \Carbon\Carbon::parse($t['started_at'])->format('M j, Y')
                                                            : null;
                                                        $completed = $t['completed_at']
                                                            ? \Carbon\Carbon::parse($t['completed_at'])->format(
                                                                'M j, Y',
                                                            )
                                                            : null;

                                                        $isClient = ($t['assign_type'] ?? '') === 'client';
                                                        $approvalRequired = (bool) ($t['requires_approval'] ?? false);
                                                        $approvalStatus = strtolower($t['approval_status'] ?? '');
                                                        $approvalLabel = $approvalStatus ?: 'pending';

                                                        $approvalClass = match ($approvalLabel) {
                                                            'approved' => 'oh-pill--success',
                                                            'changes_requested' => 'oh-pill--danger',
                                                            default => 'oh-pill--accent',
                                                        };

                                                        $status = strtolower($t['status'] ?? 'open');
                                                        $statusPill = match ($status) {
                                                            'completed' => 'oh-pill--success',
                                                            'blocked' => 'oh-pill--danger',
                                                            'overdue' => 'oh-pill--warning',
                                                            'in_progress', 'in progress' => 'oh-pill--info',
                                                            default => '',
                                                        };

                                                        $rowId =
                                                            $approvalRequired && $approvalLabel === 'pending'
                                                                ? 'task-row-' . $t['id']
                                                                : null;

                                                        $name =
                                                            $t['assign_name'] ?? ($isClient ? 'Client' : 'Unassigned');
                                                        $color =
                                                            $assigneeColors[
                                                                $t['assign_id'] ?? ($t['assign_name'] ?? null)
                                                            ] ?? $palette[0];
                                                    @endphp

                                                    <tr @if ($rowId) id="{{ $rowId }}" @endif class="align-top">
                                                        <td class="px-4 py-3.5">
                                                            <div class="space-y-1.5">
                                                                <div class="font-medium text-text-base leading-snug">
                                                                    {{ $t['title'] ?? 'Task' }}
                                                                </div>

                                                                <div
                                                                    class="flex flex-wrap items-center gap-2 text-xs text-text-subtle">
                                                                    <span
                                                                        class="oh-pill {{ $isClient ? 'oh-pill--info' : '' }}">
                                                                        {{ $isClient ? 'Client' : 'Admin/Internal' }}
                                                                    </span>
                                                                    <span class="h-1 w-1 rounded-full bg-[rgb(var(--border))]"></span>
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <i class="fa-regular fa-circle-play text-xs opacity-70"></i>
                                                                        <span>{{ $started ? "Started: {$started}" : 'Not started' }}</span>
                                                                    </span>
                                                                    @if ($completed)
                                                                        <span class="h-1 w-1 rounded-full bg-[rgb(var(--border))]"></span>
                                                                        <span class="inline-flex items-center gap-1">
                                                                            <i class="fa-regular fa-circle-check text-xs opacity-70"></i>
                                                                            <span>Completed: {{ $completed }}</span>
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-4">
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="h-8 w-8 rounded-full grid place-items-center text-xs font-semibold text-white"
                                                                    style="background: {{ $color }};">
                                                                    {{ $assigneeInitials($name) }}
                                                                </span>
                                                                <div class="min-w-0">
                                                                    <div
                                                                        class="text-sm font-semibold text-text-base leading-snug truncate">
                                                                        {{ $name }}
                                                                    </div>
                                                                    <div class="text-xs text-text-subtle">
                                                                        {{ $isClient ? 'External client' : 'Team member' }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-4">
                                                            <div class="space-y-2">
                                                                <span class="oh-pill {{ $statusPill }}">
                                                                    {{ str_replace('_', ' ', $t['status'] ?? 'open') }}
                                                                </span>

                                                                @if ($approvalRequired)
                                                                    <span class="oh-pill {{ $approvalClass }}">
                                                                        Approval:
                                                                        {{ str_replace('_', ' ', $approvalLabel) }}
                                                                    </span>

                                                                    <button type="button"
                                                                        class="oh-btn h-8 text-xs px-3 js-toggle-approval"
                                                                        data-target="approval-panel-{{ $t['id'] }}"
                                                                        aria-controls="approval-panel-{{ $t['id'] }}"
                                                                        aria-expanded="false">
                                                                        <i class="fa-regular fa-flag text-xs"></i>
                                                                        Review approval
                                                                    </button>

                                                                    @if (!empty($t['approval_note']))
                                                                        <div class="text-xs text-text-subtle">
                                                                            Note: {{ $t['approval_note'] }}
                                                                        </div>
                                                                    @endif
                                                                @elseif (!$completed)
                                                                    <div class="text-xs text-text-subtle">Watching for
                                                                        completion</div>
                                                                @endif
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-4 text-right">
                                                            <div class="flex items-center justify-end gap-2">
                                                                <div
                                                                    class="text-sm font-semibold text-text-base tabular-nums">
                                                                    {{ number_format($t['hours'] ?? 0, 2) }}h
                                                                </div>

                                                                <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant->id ?? $tenant, 'edit' => $t['id'], 'back' => $backUrl]) }}"
                                                                    class="oh-icon-btn"
                                                                    aria-label="Edit task">
                                                                    <i class="fa-solid fa-pen text-[12px]"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    @if ($approvalRequired)
                                                        <tr id="approval-panel-{{ $t['id'] }}" class="hidden">
                                                            <td colspan="4" class="px-4 pb-4">
                                                                <div
                                                                    class="rounded-xl border border-border-default/70 bg-surface-muted/60 p-4 space-y-3">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span
                                                                            class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Approval</span>
                                                                        <span class="oh-pill {{ $approvalClass }}">
                                                                            {{ str_replace('_', ' ', $approvalLabel) }}
                                                                        </span>
                                                                    </div>

                                                                    <div class="flex flex-wrap gap-3 items-center">
                                                                        <form method="POST"
                                                                            action="{{ route('tenant.tasks.approve', ['tenant' => $tenant->id ?? $tenant, 'task' => $t['id']]) }}">
                                                                            @csrf
                                                                            <button type="submit"
                                                                                class="oh-btn oh-btn--primary h-9 text-xs px-4">
                                                                                Approve
                                                                            </button>
                                                                        </form>

                                                                        <form method="POST"
                                                                            action="{{ route('tenant.tasks.request_changes', ['tenant' => $tenant->id ?? $tenant, 'task' => $t['id']]) }}"
                                                                            class="flex flex-wrap items-center gap-2">
                                                                            @csrf
                                                                            <input type="text" name="note"
                                                                                class="oh-input h-9 w-72"
                                                                                placeholder="Optional note (short)" />
                                                                            <button type="submit"
                                                                                class="oh-btn h-9 text-xs px-4">
                                                                                Request changes
                                                                            </button>
                                                                        </form>
                                                                    </div>

                                                                    @if (!empty($t['approval_note']))
                                                                        <div class="text-xs text-text-subtle">
                                                                            Last note: {{ $t['approval_note'] }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                            @if (!empty($unassignedHours) && $unassignedHours > 0)
                                                <tfoot>
                                                    <tr class="border-t border-border-default/60 bg-surface-muted/50">
                                                        <td class="px-4 py-3" colspan="3">
                                                            <div class="text-sm font-semibold text-text-base">Unassigned time</div>
                                                            <div class="text-xs text-text-subtle">Logged to the project without a task</div>
                                                        </td>
                                                        <td class="px-4 py-3 text-right font-semibold text-text-base">
                                                            {{ number_format($unassignedHours, 2) }}h
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div
                                class="mt-4 rounded-xl border border-border-default/70 bg-surface-card p-4 text-sm text-text-subtle">
                                No tasks yet.
                            </div>
                        @endif
                    </div>
                </section>

            </section>
            {{-- Right: tabbed support panel --}}
            <aside class="space-y-4">
                @php
                    $projectFiles = collect();
                    if (isset($project->uploads)) {
                        $projectFiles = collect($project->uploads);
                    } elseif (isset($project->files)) {
                        $projectFiles = collect($project->files);
                    }
                    $recentActivity = isset($recentActivity) ? $recentActivity : collect();
                    // Keep tabs visible even when empty so users can still manage uploads or see an empty state
                    $hasFilesTab = true;
                    $hasActivityTab = true;
                    $threadBadge = ($clientUnreadCount ?? 0) > 0 ? (int) $clientUnreadCount : null;
                    $status = $conversation->approval_status ?? 'pending';
                    $pill = match ($status) {
                        'approved' => ['label' => 'Approved', 'class' => 'oh-pill oh-pill--success'],
                        'changes_requested' => [
                            'label' => 'Changes requested',
                            'class' => 'oh-pill oh-pill--warning',
                        ],
                        default => ['label' => 'Pending review', 'class' => 'oh-pill'],
                    };
                @endphp

                <div class="oh-card oh-card--soft min-h-[520px]">
                    {{-- Tabs --}}
                    <div class="flex items-center gap-2 border-b border-border-default/70 px-2 py-2">
                        <button type="button"
                            class="oh-pill text-xs font-semibold js-thread-tab is-active"
                            data-target="#tab-thread">
                            Client Thread
                            @if ($threadBadge)
                                <span class="ml-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-amber-500 text-white px-1 text-xs tabular-nums">{{ $threadBadge }}</span>
                            @endif
                        </button>
                        <button type="button" class="oh-pill text-xs font-semibold js-thread-tab"
                            data-target="#tab-files">
                            Files
                        </button>
                        <button type="button" class="oh-pill text-xs font-semibold js-thread-tab"
                            data-target="#tab-activity">
                            Activity
                        </button>
                    </div>

                    {{-- Thread --}}
                    <div id="tab-thread" class="p-3 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-text-base">Client Thread</h3>
                                <span class="{{ $pill['class'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-90"></span>
                                    {{ $pill['label'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Share link compact --}}
                        <div
                            class="rounded-lg border border-border-default/70 bg-surface-muted/60 px-3 py-2 flex items-center gap-2">
                            <i class="fa-regular fa-link text-text-subtle text-xs"></i>
                            <input readonly value="{{ $publicUrl }}"
                                class="w-full h-8 rounded-md px-2 text-xs bg-transparent text-text-base focus:outline-none" />
                            <button type="button" class="oh-icon-btn"
                                onclick="navigator.clipboard.writeText('{{ $publicUrl }}')" aria-label="Copy review link">
                                <i class="fa-regular fa-copy text-xs"></i>
                            </button>
                        </div>
                        <p class="text-xs text-text-subtle">
                            Expires: {{ optional($conversation->public_expires_at)->format('M d, Y') ?? '—' }}
                        </p>

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
                            $contact = $project->contact ?? null;
                            $clientMsgHex = $contact?->portal_client_message_color ?? '#1C2E70';
                            $teamMsgHex = $contact?->portal_team_message_color ?? '#E6EAF2';
                            $clientMsgRgb = $hexToRgb($clientMsgHex, '#1C2E70');
                            $teamMsgRgb = $hexToRgb($teamMsgHex, '#E6EAF2');
                        @endphp

                        {{-- Messages --}}
                        <div class="space-y-3 overflow-y-auto pr-1"
                            style="max-height: 360px; --client-msg: {{ $clientMsgRgb }}; --team-msg: {{ $teamMsgRgb }};">
                            @forelse ($messages as $m)
                                @php
                                    $isClient = strtolower((string) ($m->user?->role ?? '')) === 'client';
                                    $bubbleBg = $isClient
                                        ? 'rgba(' . $clientMsgRgb . ', 0.22)'
                                        : 'rgba(' . $teamMsgRgb . ', 0.5)';
                                    $bubbleBorder = $isClient
                                        ? 'rgba(' . $clientMsgRgb . ', 0.7)'
                                        : 'rgba(' . $teamMsgRgb . ', 0.7)';
                                    $label = $isClient ? 'Client' : 'Team';
                                @endphp

                                <div class="flex {{ $isClient ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[92%] rounded-2xl px-3 py-2 border"
                                        style="background-color: {{ $bubbleBg }}; border-color: {{ $bubbleBorder }};">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs font-semibold text-text-base">{{ $label }}</span>
                                            <span
                                                class="text-xs text-text-subtle">{{ optional($m->created_at)->format('M d · g:i A') }}</span>
                                        </div>
                                        <div class="mt-1 text-sm text-text-base whitespace-pre-wrap">
                                            {{ $m->body ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-border-default/70 bg-surface-card p-3">
                                    <p class="text-sm text-text-subtle">
                                        No messages yet. Send the client a note to start the thread.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Composer --}}
                        <form method="POST"
                            action="{{ route('tenant.projects.messages.store', ['tenant' => $tenant, 'project' => $project->id]) }}">
                            @csrf
                            <div
                                class="rounded-xl border border-border-default/70 bg-surface-muted/60 p-3 space-y-2">
                                <textarea name="body" rows="3" placeholder="Write an update to the client…"
                                    class="w-full bg-transparent text-sm text-text-base placeholder:text-[rgb(var(--text-subtle))]
                               focus:outline-none resize-none"></textarea>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-text-subtle">Visible to client</span>
                                    <button type="submit" class="oh-btn oh-btn--primary">
                                        <i class="fa-regular fa-paper-plane text-xs"></i>
                                        Send
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Files --}}
                    <div id="tab-files" class="p-3 space-y-2 hidden">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-base">Files &amp; Links</h3>
                            @if (Route::has('tenant.projects.edit'))
                                <a href="{{ route('tenant.projects.edit', ['tenant' => $tenant, 'project' => $project->id]) }}"
                                    class="oh-btn oh-btn--ghost text-xs">Upload / Manage</a>
                            @endif
                        </div>
                        @if ($projectFiles->count() === 0)
                            <p class="text-sm text-text-subtle">No files or links yet.</p>
                        @else
                            <ul class="space-y-2 text-sm text-text-base">
                                @foreach ($projectFiles as $file)
                                    @php
                                        $fileName = data_get($file, 'name') ?? (data_get($file, 'filename') ?? 'File');
                                        $fileUrl = data_get($file, 'url') ?? (data_get($file, 'link') ?? '#');
                                    @endphp
                                    <li class="flex items-center gap-2">
                                        <i class="fa-regular fa-paperclip text-text-subtle"></i>
                                        <a href="{{ $fileUrl }}" class="text-brand-primary hover:underline truncate" target="_blank"
                                            rel="noopener">{{ $fileName }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Activity --}}
                    <div id="tab-activity" class="p-3 space-y-2 hidden">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-base">Recent Activity</h3>
                            @if (Route::has('admin.activity.index'))
                                <a href="{{ route('admin.activity.index') }}" class="oh-btn oh-btn--ghost text-xs">View all</a>
                            @endif
                        </div>

                        <ul class="space-y-2 text-sm text-text-base">
                            @php $count = 0; @endphp
                            @foreach ($recentActivity as $a)
                                @php
                                    $when = optional($a->created_at ?? null)->diffForHumans() ?? '—';
                                    $action = $a->action ?? 'Updated';
                                    $desc = $a->description ?? '';
                                @endphp
                                <li class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-text-base">{{ $action }}</div>
                                        @if ($desc)
                                            <div class="text-[12px] text-text-subtle">{{ $desc }}</div>
                                        @endif
                                    </div>
                                    <span class="text-xs text-text-subtle whitespace-nowrap">{{ $when }}</span>
                                </li>
                                @php $count++; @endphp
                                @if ($count >= 10)
                                    @break
                                @endif
                            @endforeach

                            @if ($count === 0)
                                <li class="text-sm text-text-subtle">No activity yet.</li>
                            @endif
                        </ul>
                    </div>
                </div>

            </aside>




        </div>

        {{-- Legacy edit task modal left as-is (you can modernize later) --}}
        <div id="editTaskModal" class="modal hidden">
            <div class="modal-content">
                <span class="close" onclick="closeEditTaskModal()">&times;</span>
                <h3>Edit Task</h3>
                <form id="editTaskForm" method="POST" action="#">
                    @csrf
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="project_id" id="edit-project-id" value="{{ $projectId }}">

                    <label for="edit-title">Title:</label>
                    <input type="text" name="title" id="edit-title" required>

                    <label for="edit-description">Description:</label>
                    <textarea name="description" id="edit-description"></textarea>

                    <label for="edit-due-date">Due Date:</label>
                    <input type="date" name="due_date" id="edit-due-date" required>

                    <label for="edit-assign-type">Assign Type:</label>
                    <select name="assign_type" id="edit-assign-type" onchange="filterAssignIdDropdown()">
                        <option value="user">User</option>
                        <option value="client">Client</option>
                    </select>

                    <label for="edit-assign-id">Assigned To:</label>
                    <select name="assign_id" id="edit-assign-id" required></select>

                    <label for="edit-phase-id">Phase ID:</label>
                    <select name="phase_id" id="edit-phase-id">
                        <option value="">No Phase</option>
                        @foreach ($phasesList as $phaseOption)
                            <option value="{{ data_get($phaseOption, 'id') }}">{{ data_get($phaseOption, 'name') }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-save">Save</button>
                </form>
            </div>
        </div>

        <div id="project-view-data" data-users='@json($users ?? [])' data-clients='@json($clients ?? [])'
            data-phases='@json($phasesList)' style="display:none;"></div>
    </div>
@endsection

@push('scripts')
    <script>
            document.addEventListener('DOMContentLoaded', () => {
                const buttons = document.querySelectorAll('.js-toggle-approval');

            const closeAll = () => {
                document.querySelectorAll('tr[id^="approval-panel-"]').forEach(row => row.classList.add(
                    'hidden'));
                buttons.forEach(b => b.setAttribute('aria-expanded', 'false'));
            };

            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();

                    const id = btn.getAttribute('data-target');
                    if (!id) return;

                    const row = document.getElementById(id);
                    if (!row) return;

                    const isOpen = !row.classList.contains('hidden');
                    closeAll();

                    if (!isOpen) {
                        row.classList.remove('hidden');
                        btn.setAttribute('aria-expanded', 'true');
                        // small scroll into view for long lists
                        row.scrollIntoView({
                            block: 'nearest',
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Optional: close when clicking outside any open panel
                document.addEventListener('click', (e) => {
                    const insidePanel = e.target.closest('tr[id^="approval-panel-"]');
                    const insideBtn = e.target.closest('.js-toggle-approval');
                    if (!insidePanel && !insideBtn) closeAll();
                });

                // Tabs on the right rail
                const tabButtons = document.querySelectorAll('.js-thread-tab');
                const tabPanels = ['#tab-thread', '#tab-files', '#tab-activity']
                    .map((sel) => document.querySelector(sel))
                    .filter(Boolean);

                const showTab = (btn) => {
                    const target = btn.getAttribute('data-target');
                    if (!target) return;
                    tabButtons.forEach((b) => {
                        b.classList.remove('is-active');
                        b.setAttribute('aria-selected', 'false');
                    });
                    tabPanels.forEach((p) => p.classList.add('hidden'));
                    btn.classList.add('is-active');
                    btn.setAttribute('aria-selected', 'true');
                    const panel = document.querySelector(target);
                    if (panel) panel.classList.remove('hidden');
                };

                let initial = Array.from(tabButtons).find((b) => b.classList.contains('is-active')) || tabButtons[0];
                if (initial) showTab(initial);

                tabButtons.forEach((btn) => {
                    btn.addEventListener('click', () => showTab(btn));
                });
            });
        </script>
@endpush
