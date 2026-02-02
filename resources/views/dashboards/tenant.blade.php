@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        $range = $range ?? request('range', 'wtd');

        $tasksDueToday = $tasksDueToday ?? collect();
        $tasksOverdue = $tasksOverdue ?? collect();
        $tasksDueSoon = $tasksDueSoon ?? collect();
        $tasksDueTodayCount = $tasksDueTodayCount ?? 0;
        $tasksOverdueCount = $tasksOverdueCount ?? 0;
        $tasksDueSoonCount = $tasksDueSoonCount ?? 0;
        $tasksBlockedCount = $tasksBlockedCount ?? 0;
        $tasksQueue = $tasksQueue ?? collect();
        $queue = $queue ?? 'today';
        $invoicesOverdueCount = $invoicesOverdueCount ?? 0;
        $invoicesOverdueTotal = $invoicesOverdueTotal ?? 0;
        $invoicesDueSoonCount = $invoicesDueSoonCount ?? 0;
        $invoicesDueSoonTotal = $invoicesDueSoonTotal ?? 0;
        $invoicesDueSoon = $invoicesDueSoon ?? collect();
        $outstandingTotal = $outstandingTotal ?? 0;
        $collectedTotal = $collectedTotal ?? 0;
        $draftInvoicesCount = $draftInvoicesCount ?? 0;
        $hoursLogged = $hoursLogged ?? 0;
        $showHoursLogged = $showHoursLogged ?? false;
        $atRiskProjects = $atRiskProjects ?? collect();
        $atRiskProjectsCount = $atRiskProjectsCount ?? 0;
        $recentActivity = $recentActivity ?? collect();

        $formatCurrency = fn ($value) => '$' . number_format((float) $value, 2);
        $rangeLabel = match ($range) {
            'today' => 'Today',
            'wtd' => 'WTD',
            'mtd' => 'MTD',
            '30d' => '30D',
            default => strtoupper($range),
        };
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Workspace</p>
                <h1 class="text-2xl md:text-3xl font-semibold text-text-base">Workspace Overview</h1>
                <p class="text-sm text-text-subtle mt-1">See what needs attention, what’s at risk, and what money is in motion.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ Route::has('tenant.projects.create') ? route('tenant.projects.create', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus mr-2 text-[12px]"></i>
                    New Project
                </a>
                <a href="{{ Route::has('tenant.invoices.create') ? route('tenant.invoices.create', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn">
                    <i class="fa-solid fa-file-invoice-dollar mr-2 text-[12px]"></i>
                    New Invoice
                </a>
                <a href="{{ Route::has('tenant.time.create') ? route('tenant.time.create', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn">
                    <i class="fa-solid fa-clock mr-2 text-[12px]"></i>
                    Log Time
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Date range</p>
                    <p class="text-sm text-text-subtle mt-1">Quick filters for this overview.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['today' => 'Today', 'wtd' => 'WTD', 'mtd' => 'MTD', '30d' => '30D'] as $key => $label)
                        <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId, 'range' => $key, 'queue' => $queue]) }}"
                            class="oh-pill {{ $range === $key ? 'oh-pill--active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId, 'range' => $range, 'queue' => $queue]) }}"
                        class="oh-btn h-9 px-3">Filter</a>
                    @if (request()->has('range') || request()->has('queue'))
                        <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId]) }}" class="oh-btn h-9 px-3">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Attention in the next 7 days --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Tasks due soon</p>
                        <div class="mt-2 text-2xl font-semibold text-text-base">{{ $tasksDueSoonCount }}</div>
                        <p class="text-sm text-text-subtle mt-1">Due in the next 7 days.</p>
                    </div>
                    <span class="oh-pill">Due soon</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($tasksDueSoon->take(3) as $task)
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <div class="font-medium text-text-base">{{ $task->title }}</div>
                                <div class="text-xs text-text-subtle">{{ $task->project?->project_name ?? 'No project' }}</div>
                            </div>
                            <div class="text-xs text-text-subtle">{{ $task->due_date?->format('M j') ?? '—' }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No tasks due soon.</div>
                    @endforelse
                </div>
                <a href="{{ Route::has('tenant.tasks.index') ? route('tenant.tasks.index', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn oh-btn--ghost mt-4">View tasks</a>
            </section>

            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Invoices due soon</p>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-semibold text-text-base">{{ $formatCurrency($invoicesDueSoonTotal) }}</span>
                            <span class="text-sm text-text-subtle">({{ $invoicesDueSoonCount }})</span>
                        </div>
                        <p class="text-sm text-text-subtle mt-1">Due in the next 7 days.</p>
                    </div>
                    <span class="oh-pill">Due soon</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($invoicesDueSoon as $invoice)
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <div class="font-medium text-text-base">{{ $invoice->invoice_number ?? 'Invoice' }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $invoice->contact?->first_name ?? '' }} {{ $invoice->contact?->last_name ?? '' }}
                                </div>
                            </div>
                            <div class="text-xs text-text-subtle">{{ $invoice->due_date?->format('M j') ?? '—' }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No invoices due soon.</div>
                    @endforelse
                </div>
                <a href="{{ Route::has('tenant.invoices.index') ? route('tenant.invoices.index', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn oh-btn--ghost mt-4">View invoices</a>
            </section>

            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Projects at risk</p>
                        <div class="mt-2 text-2xl font-semibold text-text-base">{{ $atRiskProjectsCount }}</div>
                        <p class="text-sm text-text-subtle mt-1">Needs attention this week.</p>
                    </div>
                    <span class="oh-pill">At risk</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($atRiskProjects->take(3) as $project)
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <div class="font-medium text-text-base">{{ $project['name'] }}</div>
                                <div class="text-xs text-text-subtle">{{ $project['reason'] }}</div>
                            </div>
                            <div class="text-xs text-text-subtle">{{ $project['client'] }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No projects flagged right now.</div>
                    @endforelse
                </div>
                <a href="{{ Route::has('tenant.projects.index') ? route('tenant.projects.index', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn oh-btn--ghost mt-4">View projects</a>
            </section>
        </div>

        {{-- Main content --}}
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-7 space-y-6">
                <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h2 class="text-base font-semibold text-text-base">Attention queue</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach (['overdue' => 'Overdue', 'today' => 'Due today', 'soon' => 'Due soon', 'blocked' => 'Blocked'] as $key => $label)
                                <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId, 'range' => $range, 'queue' => $key]) }}"
                                    class="oh-pill {{ $queue === $key ? 'oh-pill--active' : '' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @forelse ($tasksQueue as $task)
                        @php
                            $dueDate = $task->due_date?->format('M j') ?? '—';
                            $status = strtolower((string) ($task->status ?? 'todo'));
                            $statusPill = match ($status) {
                                'completed' => 'oh-pill oh-pill--success',
                                'in_progress' => 'oh-pill oh-pill--info',
                                'blocked' => 'oh-pill oh-pill--warning',
                                default => 'oh-pill',
                            };
                        @endphp
                        <div class="flex items-start justify-between gap-4 py-3 border-b border-border-default/60 last:border-b-0">
                            <div>
                                <div class="text-sm font-medium text-text-base">{{ $task->title }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $task->project?->project_name ?? 'No project' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="{{ $statusPill }}">{{ str_replace('_', ' ', $status) }}</span>
                                <div class="text-xs text-text-subtle mt-1">{{ $dueDate }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">
                            No items in this queue. Create a task or start a project to populate your work list.
                            <a class="oh-btn oh-btn--ghost ml-2"
                                href="{{ Route::has('tenant.tasks.create') ? route('tenant.tasks.create', ['tenant' => $tenantId]) : '#' }}">
                                New Task
                            </a>
                        </div>
                    @endforelse
                    <div class="mt-4">
                        <a href="{{ Route::has('tenant.tasks.index') ? route('tenant.tasks.index', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn">View all tasks</a>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-5 space-y-6">
                <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-text-base">Money in motion</h2>
                        <span class="text-xs uppercase tracking-wide text-text-subtle">{{ $rangeLabel }}</span>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-text-subtle">Outstanding</span>
                            <span class="font-semibold text-text-base">{{ $formatCurrency($outstandingTotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-text-subtle">Due in next 7 days</span>
                            <span class="font-semibold text-text-base">{{ $formatCurrency($invoicesDueSoonTotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-text-subtle">Overdue</span>
                            <span class="font-semibold text-text-base">{{ $formatCurrency($invoicesOverdueTotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-text-subtle">Collected ({{ $rangeLabel }})</span>
                            <span class="font-semibold text-text-base">{{ $formatCurrency($collectedTotal) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ Route::has('tenant.invoices.create') ? route('tenant.invoices.create', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn oh-btn--primary">Create invoice</a>
                        <a href="{{ Route::has('tenant.invoices.index') ? route('tenant.invoices.index', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn {{ $invoicesOverdueCount > 0 ? '' : 'opacity-50 pointer-events-none' }}"
                            @if ($invoicesOverdueCount === 0) aria-disabled="true" @endif>
                            Send reminder
                        </a>
                    </div>
                </section>

                <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-text-base">At-risk projects</h2>
                        <span class="text-xs uppercase tracking-wide text-text-subtle">Last activity</span>
                    </div>
                    @forelse ($atRiskProjects as $project)
                        <div class="flex items-start justify-between gap-4 py-3 border-b border-border-default/60 last:border-b-0">
                            <div>
                                <div class="text-sm font-medium text-text-base">{{ $project['name'] }}</div>
                                <div class="text-xs text-text-subtle">{{ $project['client'] }}</div>
                                <div class="text-xs text-text-subtle mt-1">{{ $project['reason'] }}</div>
                            </div>
                            <a href="{{ Route::has('tenant.projects.show') ? route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $project['id']]) : '#' }}"
                                class="oh-btn">View</a>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No projects at risk.</div>
                    @endforelse
                </section>
            </div>
        </div>

        {{-- Recent activity --}}
        <section class="oh-card border border-border-default/70 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-base">Recent activity</h2>
                @if (Route::has('tenant.activity.index'))
                    <a href="{{ route('tenant.activity.index', ['tenant' => $tenantId]) }}" class="oh-btn">View all</a>
                @endif
            </div>
            <div class="space-y-0">
                @forelse ($recentActivity as $activity)
                    @php
                        $actor = trim(($activity->user?->first_name ?? '') . ' ' . ($activity->user?->last_name ?? '')) ?: ($activity->user?->email ?? 'Someone');
                        $label = $activity->description ?: str_replace('_', ' ', (string) $activity->action);
                        $time = $activity->created_at ? $activity->created_at->diffForHumans() : '';
                    @endphp
                    <div class="flex items-start justify-between gap-4 py-3 border-b border-border-default/60 last:border-b-0">
                        <div>
                            <div class="text-sm text-text-base">{{ $label }}</div>
                            <div class="text-xs text-text-subtle">By {{ $actor }}</div>
                        </div>
                        <div class="text-xs text-text-subtle">{{ $time }}</div>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">
                        No activity yet. Log time, add a task, or create a project to get started.
                        <a class="oh-btn oh-btn--ghost ml-2"
                            href="{{ Route::has('tenant.time.create') ? route('tenant.time.create', ['tenant' => $tenantId]) : '#' }}">
                            Log time
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
