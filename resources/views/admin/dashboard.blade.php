@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    @php
        // ====== SAFE DEFAULTS / CONTEXT ======
        // Ensure we always have a tenant param for route links
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);

        // Range + KPI fallbacks
        $rangeLabel = strtoupper($range ?? 'WTD');
        $kpi_hours_wtd = (float) ($kpi_hours_wtd ?? 0);

        $inv_count = (int) ($kpi_invoices_due_count ?? 0);
        $inv_total = (float) ($kpi_invoices_due_total ?? 0);

        $overdue_open = (int) ($overdue_open ?? 0);
        $staleProjects = (int) ($staleProjects ?? 0);
        $stuckTasks = (int) ($stuckTasks ?? 0);

        $aging = $aging ?? ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $aging_hasAmount = (bool) ($aging_hasAmount ?? false);
        $maxAging = max(1, max(array_values($aging))); // guard divide-by-zero

        $on_time = is_array($on_time ?? null)
            ? $on_time
            : ['pct' => (int) ($on_time ?? 0), 'on_time' => 0, 'total' => 0];

        $isAdmin = (bool) ($isAdmin ?? false);

        // Optional: use route names if they exist; otherwise default to '#'
        $invoicesIndex = \Illuminate\Support\Facades\Route::has('tenant.invoices.index')
            ? route('tenant.invoices.index', ['tenant' => $tenantParam, 'status' => 'Sent,Overdue', 'due' => 'now'])
            : '#';

        $invoicesAll = \Illuminate\Support\Facades\Route::has('tenant.invoices.index')
            ? route('tenant.invoices.index', ['tenant' => $tenantParam])
            : '#';

        $invoicesNext14 = \Illuminate\Support\Facades\Route::has('tenant.invoices.index')
            ? route('tenant.invoices.index', ['tenant' => $tenantParam, 'due' => 'next14d'])
            : '#';

        // Tiny helper for the aging bar width
        if (!function_exists('cws_bar_width')) {
            function cws_bar_width($val, $max)
            {
                $den = max(1, (int) $max);
                $p = (int) round(((float) $val / $den) * 100);
                return "style=\"width:{$p}%\"";
            }
        }
    @endphp
    @php
        $user = auth('admin')->user() ?? auth()->user();
        $role = strtolower($user->role ?? '');
        $isProvider = in_array($role, ['provider'], true);
    @endphp
    <div class="oh-page">
        <section class="rounded-2xl border border-border/60 bg-white shadow-card-light p-6 space-y-4">
            <header class="space-y-1">
                @if ($isProvider)
                    <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">You’re in Provider mode</p>
                @endif
                <h1 class="text-2xl font-semibold text-card-fg leading-tight">My Dashboard</h1>
                <p class="text-sm text-muted-fg">Overview of tasks, projects, and activity for {{ $rangeLabel }}.</p>
            </header>

            <x-quick-actions :range="$range ?? 'wtd'" :range-label="$rangeLabel" />

        </section>

        @php
            $heroKPIs = [
                [
                    'label' => 'Active Tenants',
                    'value' => number_format((int) ($activeTenants ?? 0)),
                    'from' => 'from-blue-700',
                    'to' => 'to-blue-500',
                ],
                [
                    'label' => 'Service Clients',
                    'value' => number_format((int) ($crmClients ?? 0)),
                    'from' => 'from-green-700',
                    'to' => 'to-green-500',
                ],
                [
                    'label' => 'Projects In Progress',
                    'value' => number_format((int) ($openProjects ?? 0)),
                    'from' => 'from-purple-700',
                    'to' => 'to-purple-500',
                ],
                [
                    'label' => 'Tasks Due Today',
                    'value' => number_format((int) count($tasksDueToday ?? [])),
                    'from' => 'from-blue-800',
                    'to' => 'to-blue-600',
                ],
            ];
        @endphp

        {{-- KPI band --}}
        <section>
            <p class="text-xs font-semibold tracking-wide text-text-subtle uppercase mb-3">
                Key Metrics
            </p>

            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-5">
                <x-kpi-hero label="Active Tenants" value="{{ $activeTenants ?? 0 }}" icon="fa-building" color="brand"
                    href="{{ route('admin.tenants.index') }}" />

                <x-kpi-hero label="Service Clients" value="{{ $serviceClients ?? 0 }}" icon="fa-handshake"
                    color="success" />

                <x-kpi-hero label="Projects in Progress" value="{{ $projectsInProgress ?? 0 }}" icon="fa-diagram-project"
                    color="secondary" />

                <x-kpi-hero label="Tasks Due Today" value="{{ $tasksDueTodayCount ?? 0 }}" icon="fa-calendar-day"
                    color="accent" />

                <x-kpi-hero label="Hours Logged ({{ strtoupper($range ?? 'WTD') }})"
                    value="{{ number_format((float) ($kpi_hours_wtd ?? 0), 1) }}" icon="fa-stopwatch" color="secondary" />
            </div>
        </section>

        {{-- ========================== FINANCE ========================== --}}
        @php
            $forecastValue = !empty($forecast['hasAmount'] ?? false)
                ? '$' . number_format((float) ($forecast['total'] ?? 0), 0)
                : (int) ($forecast['count'] ?? 0) . ' invoices';
        @endphp
        <section class="mt-8">
            <div class="flex items-end justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wide text-muted-fg uppercase">Finance</h2>
            </div>
            <div class="rounded-2xl border border-border/60 bg-card shadow-card-light p-4">
                <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                    <x-kpi-card title="Invoices Due" :value="$inv_count" :subtitle="$inv_total > 0 ? '$' . number_format($inv_total, 0) : null" icon="fa-file-invoice"
                        href="{{ $invoicesIndex }}" colorType="{{ $inv_count > 0 ? 'accent' : 'success' }}"
                        variant="card" />

                    <x-kpi-card title="Collected ({{ $rangeLabel }})"
                        value="${{ number_format((float) ($cash_collected ?? 0), 0) }}" icon="fa-money-bill-wave"
                        href="{{ $invoicesAll }}" colorType="secondary" variant="card" />

                    <x-kpi-card title="Forecast (14d)" value="{{ $forecastValue }}" icon="fa-calendar-day"
                        href="{{ $invoicesNext14 }}" colorType="info" variant="card" />
                </div>
            </div>
        </section>


        {{-- ========================== OPERATIONS (4-COL) ========================== --}}
        @php
            $onTimeSubtitle =
                ((int) ($on_time['total'] ?? 0)) > 0
                    ? ((int) $on_time['on_time']) . '/' . ((int) $on_time['total'])
                    : null;
        @endphp

        <section class="mt-8">
            <div class="flex items-end justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wide text-muted-fg uppercase">Operations</h2>
            </div>

            <div class="rounded-2xl border border-border/60 bg-card shadow-card-light p-4">
                <div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
                    <x-kpi-card title="Tasks Today" :value="(int) count($tasksDueToday ?? [])" icon="fa-calendar-check"
                        href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'due' => 'today']) }}"
                        colorType="secondary" variant="card" />

                    <x-kpi-card title="Overdue Tasks" :value="$overdue_open" icon="fa-exclamation-circle"
                        href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'filter' => 'overdue']) }}"
                        colorType="{{ $overdue_open > 0 ? 'accent' : 'success' }}" variant="card" />

                    <x-kpi-card title="On-Time" value="{{ (int) $on_time['pct'] }}%" :subtitle="$onTimeSubtitle" icon="fa-stopwatch"
                        href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'status' => 'completed']) }}"
                        colorType="success" variant="card" />

                    <x-kpi-card title="Stale Projects" :value="$staleProjects" icon="fa-hourglass-half"
                        href="{{ route('tenant.projects.index', ['tenant' => $tenantParam, 'filter' => 'stale']) }}"
                        colorType="{{ $staleProjects > 0 ? 'accent' : 'success' }}" variant="card" />
                </div>
            </div>
        </section>

        {{-- ========================== PROFITABILITY SIGNALS ========================== --}}
        @php
            $profitabilitySignals = $profitabilitySignals ?? ['healthy' => 0, 'drifting' => 0, 'time-heavy' => 0];
            $avgEhrText = $avgEhr ?? '—';
        @endphp
        <section class="mt-8">
            <div class="flex items-end justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wide text-muted-fg uppercase">Profitability signal</h2>
            </div>
            <div class="oh-card border border-border/60 bg-card shadow-card-light p-4 space-y-3">
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach (['healthy' => 'Healthy', 'drifting' => 'Drifting', 'time-heavy' => 'Time-heavy'] as $key => $label)
                        <div class="space-y-1">
                            <div class="text-xs text-text-subtle uppercase tracking-[0.3em]">{{ $label }}</div>
                            <div class="text-2xl font-semibold text-text-base">
                                {{ number_format($profitabilitySignals[$key] ?? 0) }}</div>
                            <div class="text-sm text-text-subtle">
                                {{ $key === 'healthy' ? 'Projects hitting target' : ($key === 'drifting' ? 'Close watch advised' : 'Time-heavy jobs') }}
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-sm text-text-subtle">Avg. EHR this week: {{ $avgEhrText }}</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantParam]) }}"
                        class="oh-btn oh-btn--ghost">Go to tenant dashboard</a>
                </div>
            </div>
        </section>

        {{-- ========================== PROFITABILITY SIGNALS ========================== --}}


        {{-- ========================== REPORTS & LISTS ========================== --}}
        <section class="mt-8">
            <div class="flex items-end justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wide text-muted-fg uppercase">Reports</h2>
            </div>

            <div class="rounded-2xl border border-border/60 bg-card shadow-card-light p-4">
                <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
                    {{-- ================= Needs Attention ================= --}}
                    <x-report-section title="Needs Attention" icon="fa-bell">
                        @php
                            $items = [];
                            if ($staleProjects > 0) {
                                $items[] = [
                                    'label' => 'Stale projects',
                                    'count' => $staleProjects,
                                    'icon' => 'fa-hourglass-half text-[var(--brand-accent)]',
                                    'href' => route('tenant.projects.index', [
                                        'tenant' => $tenantParam,
                                        'filter' => 'stale',
                                    ]),
                                ];
                            }
                            if ($stuckTasks > 0) {
                                $items[] = [
                                    'label' => 'Stuck tasks',
                                    'count' => $stuckTasks,
                                    'icon' => 'fa-triangle-exclamation text-status-danger',
                                    'href' => route('tenant.tasks.index', [
                                        'tenant' => $tenantParam,
                                        'filter' => 'stuck',
                                    ]),
                                ];
                            }
                        @endphp

                        @forelse ($items as $item)
                            <li>
                                <a href="{{ $item['href'] }}"
                                    class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
                              bg-surface-card hover:bg-surface-accent transition text-sm
                              border border-border/50 bg-card hover:bg-muted/40">

                                    <span class="flex items-center gap-2 min-w-0">
                                        <i class="fa {{ $item['icon'] }} flex-shrink-0"></i>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                        <span class="oh-chip-muted flex-shrink-0">{{ $item['count'] }}</span>
                                    </span>

                                    <span class="text-brand-primary font-medium">Review</span>
                                </a>
                            </li>
                        @empty
                            <li>
                                <p class="oh-chip-muted">All clear for now</p>
                            </li>
                        @endforelse
                    </x-report-section>

                    {{-- ================= AR Aging ================= --}}
                    <x-report-section title="AR Aging" icon="fa-chart-column">
                        @foreach ($aging as $key => $val)
                            @php $barStyle = cws_bar_width($val, $maxAging); @endphp

                            <li>
                                <div
                                    class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
              bg-surface-card text-sm border border-border/50 bg-card hover:bg-muted/40">
                                    <span class="w-16 text-text-subtle">{{ $key }}</span>
                                    <strong class="text-right tabular-nums">
                                        {{ $aging_hasAmount ? '$' . number_format((float) $val, 0) : (int) $val }}
                                    </strong>
                                </div>
                            </li>
                        @endforeach
                    </x-report-section>

                    {{-- ================= Team Capacity ================= --}}
                    <x-report-section title="Team Capacity" icon="fa-users">
                        @forelse (($capacity ?? []) as $row)
                            <li>
                                <div
                                    class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
                                bg-surface-card text-sm border border-border/50 bg-card hover:bg-muted/40">
                                    <a href="{{ route('tenant.tasks.index', [
                                        'tenant' => $tenantParam,
                                        'assignee' => (int) ($row['user_id'] ?? 0),
                                        'status' => 'open,in_progress',
                                    ]) }}"
                                        class="text-brand-primary hover:text-brand-secondary font-medium truncate">
                                        {{ $row['name'] ?? 'User #' . ($row['user_id'] ?? '') }}
                                    </a>

                                    <span class="text-text-subtle">
                                        <strong>{{ (int) ($row['open'] ?? 0) }}</strong> open
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li>
                                <p class="oh-chip-muted">No assigned work.</p>
                            </li>
                        @endforelse
                    </x-report-section>

                    {{-- ================= New Leads ================= --}}
                    <x-report-section title="New Leads (WTD)" icon="fa-filter">
                        @forelse (($pipeline ?? []) as $status => $count)
                            <li>
                                <div
                                    class="flex items-center justify-between p-2.5 sm:p-3 rounded-lg
                                bg-surface-card text-sm border border-border/50 bg-card hover:bg-muted/40">
                                    <span class="text-text-subtle">
                                        {{ ucfirst((string) $status) }}
                                    </span>
                                    <strong>{{ (int) $count }}</strong>
                                </div>
                            </li>
                        @empty
                            <li>
                                <p class="oh-chip-muted">No new leads yet.</p>
                            </li>
                        @endforelse
                    </x-report-section>
                </div>
            </div>
        </section>



        {{-- ========================== LOWER DETAIL LISTS ========================== --}}
        <div class="my-8 h-px bg-[rgb(var(--border)/.35)]"></div>

        <section class="mt-8">
            <div class="flex items-end justify-between mb-3">
                <h2 class="text-xs font-semibold tracking-wide text-muted-fg uppercase">Tasks</h2>
            </div>

            <div class="rounded-2xl border border-border/60 bg-card shadow-card-light p-4">

                <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                    {{-- Tasks Due Today --}}
                    <x-report-section title="Tasks Due Today" icon="fa-calendar-day">
                        @forelse (($tasksDueToday ?? []) as $task)
                            <li>
                                <div
                                    class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
                                bg-surface-card text-sm ring-1 ring-[rgb(var(--border)/.6)]">
                                    <span class="min-w-0 text-text-base">
                                        <strong class="truncate block">{{ $task['title'] ?? '' }}</strong>
                                        <span class="text-text-subtle text-xs block truncate">
                                            {{ $task['project_name'] ?? '' }}
                                        </span>
                                    </span>

                                    <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantParam, 'task' => (int) ($task['id'] ?? 0)]) }}"
                                        class="text-brand-primary hover:text-brand-secondary font-medium flex-shrink-0">
                                        View
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li>
                                <p class="oh-chip-muted">Nothing due today.</p>
                            </li>
                        @endforelse
                    </x-report-section>

                    {{-- All Assigned Tasks --}}
                    <x-report-section title="All Assigned Tasks" icon="fa-person">
                        @forelse (($assignedTasks ?? []) as $task)
                            <li>
                                <div
                                    class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
                                bg-surface-card text-sm ring-1 ring-[rgb(var(--border)/.6)]">
                                    <span class="min-w-0 text-text-base truncate">
                                        {{ $task['title'] ?? '' }}
                                        <span class="text-text-subtle">—</span>
                                        <em
                                            class="text-[color:var(--brand-accent)] not-italic">{{ $task['status'] ?? '' }}</em>
                                    </span>

                                    <a href="{{ route('tenant.tasks.edit', ['tenant' => $tenantParam, 'task' => (int) ($task['id'] ?? 0)]) }}"
                                        class="text-brand-primary hover:text-brand-secondary font-medium flex-shrink-0">
                                        Edit
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li>
                                <p class="oh-chip-muted">No tasks assigned to you.</p>
                            </li>
                        @endforelse
                    </x-report-section>

                    {{-- Recent Projects (Admin) --}}
                    @if ($isAdmin)
                        <x-report-section title="Recent Projects (Admin)" icon="fa-arrows-rotate">
                            @forelse (($recentProjects ?? []) as $proj)
                                @php
                                    $updated = isset($proj['updated_at'])
                                        ? \Carbon\Carbon::parse($proj['updated_at'])->format('M d')
                                        : null;
                                @endphp

                                <li>
                                    <div
                                        class="flex items-center justify-between gap-3 p-2.5 sm:p-3 rounded-lg
                                    bg-surface-card text-sm ring-1 ring-[rgb(var(--border)/.6)]">
                                        <a href="{{ route('tenant.projects.show', ['tenant' => $tenantParam, 'project' => (int) ($proj['id'] ?? 0)]) }}"
                                            class="text-brand-primary hover:text-brand-secondary font-medium truncate min-w-0">
                                            {{ $proj['project_name'] ?? '' }}
                                        </a>

                                        @if ($updated)
                                            <span class="text-text-subtle text-xs flex-shrink-0">updated
                                                {{ $updated }}</span>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li>
                                    <p class="oh-chip-muted">No recent updates.</p>
                                </li>
                            @endforelse
                        </x-report-section>
                    @endif
                </div>
        </section>


    </div>

@endsection
