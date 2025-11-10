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

    {{-- Top Controls (Quick Actions and Range Tabs) --}}
    <x-quick-actions :range="$range ?? 'wtd'" :range-label="$rangeLabel" />

    <div class="container mx-auto p-4">
        @if (($orgType ?? null) === 'provider')
            <section class="oh-card mb-10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-text-subtle mb-1">You’re in Provider mode</p>
                        <h3 class="text-lg font-semibold text-text-base">Tenant Management Tools</h3>
                        <p class="text-sm text-text-subtle mt-1">
                            Manage tenant orgs, trials, and global settings.
                        </p>
                    </div>
                    <a href="{{ route('admin.tenants.index') }}"
                        class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium text-white
                bg-brand-primary hover:bg-brand-secondary transition">
                        View All Tenants <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>
            </section>
        @endif


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
        <section class="oh-card mb-10 mt-10">
            <div class="oh-grid-kpis">
                <x-kpi-card title="Active Tenants" value="{{ $activeTenants ?? 0 }}" icon="fa-building" colorType="brand"
                    href="{{ route('admin.tenants.index') }}" />

                <x-kpi-card title="Service Clients" value="{{ $serviceClients ?? 0 }}" icon="fa-handshake"
                    colorType="success" href="{{ route('tenant.contacts.index', ['tenant' => $tenantParam]) }}" />

                <x-kpi-card title="Projects in Progress" value="{{ $projectsInProgress ?? 0 }}" icon="fa-diagram-project"
                    colorType="secondary"
                    href="{{ route('tenant.projects.index', ['tenant' => $tenantParam, 'status' => 'active']) }}" />

                <x-kpi-card title="Tasks Due Today" value="{{ $tasksDueTodayCount ?? 0 }}" icon="fa-calendar-day"
                    colorType="accent"
                    href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'due' => 'today']) }}" />

                <x-kpi-card title="Hours Logged ({{ strtoupper($range ?? 'WTD') }})"
                    value="{{ number_format((float) ($kpi_hours_wtd ?? 0), 1) }}" subtitle="{{ $hoursSubtitle ?? '' }}"
                    icon="fa-stopwatch" colorType="secondary"
                    href="{{ route('tenant.time.index', ['tenant' => $tenantParam, 'range' => $range ?? 'wtd']) }}" />
            </div>
        </section>

    </div>


    {{-- ========================== FINANCE (3-COL) ========================== --}}
    <section class="oh-card mb-10 mt-10">
        <h2 class="oh-section-title">Finance</h2>
        <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
            <x-kpi-card title="Invoices Due" :value="$inv_count" :subtitle="$inv_total > 0 ? '$' . number_format($inv_total, 0) : null" icon="fa-file-invoice"
                href="{{ $invoicesIndex }}" colorType="{{ $inv_count > 0 ? 'accent' : 'success' }}" />
            <x-kpi-card title="Collected ({{ $rangeLabel }})"
                value="${{ number_format((float) ($cash_collected ?? 0), 0) }}" icon="fa-money-bill-wave"
                href="{{ $invoicesAll }}" colorType="secondary" />
            <x-kpi-card title="Forecast (14d)"
                @if (!empty($forecast['hasAmount'] ?? false)) value="${{ number_format((float) ($forecast['total'] ?? 0), 0) }}" 
      @else
        value="{{ (int) ($forecast['count'] ?? 0) }} invoices" @endif
                icon="fa-calendar-day" href="{{ $invoicesNext14 }}" colorType="info" />
        </div>
    </section>


    {{-- ========================== OPERATIONS (4-COL) ========================== --}}
    <section class="oh-card mb-10 mt-10">
        <h2 class="oh-section-title">Operations</h2>
        <div class="grid gap-4 md:gap-6 grid-cols-2 lg:grid-cols-4">
            <x-kpi-card title="Tasks Today" :value="(int) count($tasksDueToday ?? [])" icon="fa-calendar-check"
                href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'due' => 'today']) }}"
                colorType="secondary" />
            <x-kpi-card title="Overdue Open" :value="$overdue_open" icon="fa-exclamation-circle"
                href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'filter' => 'overdue']) }}"
                colorType="{{ $overdue_open > 0 ? 'accent' : 'success' }}" />
            <x-kpi-card title="On-Time" value="{{ (int) $on_time['pct'] }}%"
                subtitle="{{ (int) $on_time['on_time'] }}/{{ (int) $on_time['total'] }}" icon="fa-stopwatch"
                href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'status' => 'completed']) }}"
                colorType="success" />
            <x-kpi-card title="Stale Projects" :value="$staleProjects" icon="fa-hourglass-half"
                href="{{ route('tenant.projects.index', ['tenant' => $tenantParam, 'filter' => 'stale']) }}"
                colorType="{{ $staleProjects > 0 ? 'accent' : 'success' }}" />
        </div>
    </section>

    {{-- ========================== REPORTS & LISTS ========================== --}}
    <section class="oh-card mt-10">
        <h2 class="oh-section-title">Reports</h2>

        <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
            {{-- Needs Attention --}}
            <x-report-section title="Needs Attention" icon="fa-bell">
                @php
                    $items = [];
                    if ($staleProjects > 0) {
                        $items[] = [
                            'label' => 'Stale projects',
                            'count' => $staleProjects,
                            'icon' => 'fa-hourglass-half text-[var(--brand-accent)]',
                            'href' => route('tenant.projects.index', ['tenant' => $tenantParam, 'filter' => 'stale']),
                        ];
                    }
                    if ($stuckTasks > 0) {
                        $items[] = [
                            'label' => 'Stuck tasks',
                            'count' => $stuckTasks,
                            'icon' => 'fa-triangle-exclamation text-status-danger',
                            'href' => route('tenant.tasks.index', ['tenant' => $tenantParam, 'filter' => 'stuck']),
                        ];
                    }
                @endphp

                @forelse ($items as $item)
                    <a class="flex justify-between items-center p-3 rounded-lg border bg-surface-card text-text-base
                   hover:bg-surface-accent transition-colors text-sm"
                        style="border-color: rgb(var(--border-default));" href="{{ $item['href'] }}">
                        <span><i class="fa {{ $item['icon'] }} mr-2"></i> {{ $item['label'] }}
                            {{ $item['count'] }}</span>
                        <span class="text-brand-primary hover:text-brand-secondary font-medium">Review</span>
                    </a>
                @empty
                    <p class="oh-chip-muted">All clear for now</p>
                @endforelse
            </x-report-section>

            {{-- AR Aging --}}
            <x-report-section title="AR Aging">
                @foreach ($aging as $key => $val)
                    @php $barStyle = cws_bar_width($val, $maxAging); @endphp
                    <li class="flex items-center justify-between gap-3 p-3 rounded-lg border bg-surface-card text-text-base text-sm list-none"
                        style="border-color: rgb(var(--border-default));">
                        <span class="w-16 text-text-subtle">{{ $key }}:</span>
                        <strong
                            class="w-20 text-right">{{ $aging_hasAmount ? '$' . number_format((float) $val, 0) : (int) $val }}</strong>
                        <span class="w-full h-1.5 rounded-full relative"
                            style="background: rgba(var(--border-default)/.5);">
                            <span class="absolute inset-y-0 left-0 rounded-full"
                                style="background: rgb(var(--brand-primary));" {!! $barStyle !!}></span>
                        </span>
                    </li>
                @endforeach
            </x-report-section>

            {{-- Team Capacity --}}
            <x-report-section title="Team Capacity" icon="fa-users">
                @forelse (($capacity ?? []) as $row)
                    <li class="flex justify-between items-center p-3 rounded-lg border bg-surface-card hover:bg-surface-accent text-sm list-none"
                        style="border-color: rgb(var(--border-default));">
                        <a href="{{ route('tenant.tasks.index', ['tenant' => $tenantParam, 'assignee' => (int) ($row['user_id'] ?? 0), 'status' => 'open,in_progress']) }}"
                            class="text-brand-primary hover:text-brand-secondary font-medium">
                            {{ $row['name'] ?? 'User #' . ($row['user_id'] ?? '') }}
                        </a>
                        — <strong>{{ (int) ($row['open'] ?? 0) }}</strong> open
                    </li>
                @empty
                    <p class="oh-chip-muted">No assigned work.</p>
                @endforelse
            </x-report-section>

            {{-- New Leads (WTD) --}}
            <x-report-section title="New Leads (WTD)" icon="fa-filter">
                @forelse (($pipeline ?? []) as $status => $count)
                    <li class="flex justify-between items-center p-3 rounded-lg border bg-surface-card text-text-base text-sm list-none"
                        style="border-color: rgb(var(--border-default));">
                        <span class="text-text-subtle">{{ ucfirst((string) $status) }} — </span>
                        <strong>{{ (int) $count }}</strong>
                    </li>
                @empty
                    <p class="oh-chip-muted">No new leads yet.</p>
                @endforelse
            </x-report-section>
        </div>
    </section>


    {{-- ========================== LOWER DETAIL LISTS ========================== --}}


    <hr class="border-t my-10 mx-6 md:mx-8" style="border-color: rgb(var(--border-default));">
    <section class="px-6 md:px-8 mt-10 first:mt-6">
        <h2 class="oh-section-title">Tasks</h2>
        <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
            <x-report-section title="Tasks Due Today" icon="fa-calendar-day">
                @forelse (($tasksDueToday ?? []) as $task)
                    <li class="flex justify-between items-center p-3 rounded-lg border bg-surface-card text-sm list-none"
                        style="border-color: rgb(var(--border-default));">
                        <span class="text-text-base">
                            <strong>{{ $task['title'] ?? '' }}</strong> <span
                                class="text-text-subtle">({{ $task['project_name'] ?? '' }})</span>
                        </span>
                        <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantParam, 'task' => (int) ($task['id'] ?? 0)]) }}"
                            class="text-brand-primary hover:text-brand-secondary font-medium">View</a>
                    </li>
                @empty
                    <p class="oh-chip-muted">Nothing due today.</p>
                @endforelse
            </x-report-section>

            <x-report-section title="All Assigned Tasks" icon="fa-person">
                @forelse (($assignedTasks ?? []) as $task)
                    <li class="flex justify-between items-center p-3 rounded-lg border bg-surface-card text-sm list-none"
                        style="border-color: rgb(var(--border-default));">
                        <span class="text-text-base">
                            {{ $task['title'] ?? '' }}
                            — <em class="text-[color:var(--brand-accent)]">{{ $task['status'] ?? '' }}</em>
                        </span>
                        <a href="{{ route('tenant.tasks.edit', ['tenant' => $tenantParam, 'task' => (int) ($task['id'] ?? 0)]) }}"
                            class="text-brand-primary hover:text-brand-secondary font-medium">Edit</a>
                    </li>
                @empty
                    <p class="oh-chip-muted">No tasks assigned to you.</p>
                @endforelse
            </x-report-section>

            @if ($isAdmin)
                <x-report-section title="Recent Projects (Admin)" icon="fa-arrows-rotate">
                    @forelse (($recentProjects ?? []) as $proj)
                        <li class="flex justify-between items-center p-3 rounded-lg border bg-surface-card text-sm list-none"
                            style="border-color: rgb(var(--border-default));">
                            <a href="{{ route('tenant.projects.show', ['tenant' => $tenantParam, 'project' => (int) ($proj['id'] ?? 0)]) }}"
                                class="text-brand-primary hover:text-brand-secondary font-medium">
                                {{ $proj['project_name'] ?? '' }}
                            </a>
                            @php $updated = isset($proj['updated_at']) ? \Carbon\Carbon::parse($proj['updated_at'])->format('M d') : ''; @endphp
                            <span class="text-text-subtle text-xs">updated {{ $updated }}</span>
                        </li>
                    @empty
                        <p class="oh-chip-muted">No recent updates.</p>
                    @endforelse
                </x-report-section>
            @endif
        </div>
    </section>

@endsection
