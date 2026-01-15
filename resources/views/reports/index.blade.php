@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">Reports</p>
                <h1 class="text-2xl font-semibold text-text-base">Reports</h1>
                <p class="text-sm text-text-subtle">Insights that help you steer the day (and the month).</p>
            </div>
        </header>

        {{-- Filters --}}
        <section class="oh-card">
            <form method="GET" action="{{ route('tenant.reports.index', ['tenant' => $tenantParam]) }}"
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Range</label>
                    <select name="range" class="oh-select h-10">
                        @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30d'] as $k => $lbl)
                            <option value="{{ $k }}" @selected(request('range', 'wtd') === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>

                    <label class="text-xs font-semibold uppercase tracking-wide text-text-subtle ml-2">Group by</label>
                    <select name="group" class="oh-select h-10">
                        @foreach (['tenant' => 'Tenant', 'project' => 'Project', 'assignee' => 'Assignee', 'status' => 'Status'] as $k => $lbl)
                            <option value="{{ $k }}" @selected(request('group', 'tenant') === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button class="oh-btn oh-btn--primary oh-btn--sm" type="submit">Apply</button>
                    <a class="oh-btn oh-btn--ghost oh-btn--sm"
                        href="{{ route('tenant.reports.index', ['tenant' => $tenantParam]) }}">Reset</a>
                </div>
            </form>
        </section>

        {{-- Report tiles --}}
        <section class="oh-card">
            <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                {{-- Finance --}}
                <x-report-tile title="Invoices Due" icon="fa-file-invoice" colorType="accent" :stat="$inv_count ?? 0" :substat="($inv_total ?? 0) > 0 ? '$' . number_format($inv_total, 0) : null"
                    href="{{ route('tenant.reports.invoices', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="Collected ({{ strtoupper(request('range', 'wtd')) }})" icon="fa-money-bill-wave"
                    colorType="secondary" :stat="number_format((float) ($cash_collected ?? 0), 0)"
                    href="{{ route('tenant.reports.collected', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="Forecast (14d)" icon="fa-calendar-day" colorType="info" :stat="$forecast['hasAmount'] ?? false
                    ? '$' . number_format((float) ($forecast['total'] ?? 0), 0)
                    : ($forecast['count'] ?? 0) . ' invoices'"
                    href="{{ route('tenant.reports.forecast', ['tenant' => $tenantParam] + request()->query()) }}" />

                {{-- Operations --}}
                <x-report-tile title="Tasks Due Today" icon="fa-calendar-check" colorType="secondary" :stat="(int) ($tasksDueTodayCount ?? 0)"
                    href="{{ route('tenant.reports.tasks.due', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="On-Time Delivery" icon="fa-stopwatch" colorType="success" :stat="((int) ($on_time['pct'] ?? 0)) . '%'" :substat="((int) ($on_time['on_time'] ?? 0)) . '/' . ((int) ($on_time['total'] ?? 0))"
                    href="{{ route('tenant.reports.tasks-on-time', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="Stale Projects" icon="fa-hourglass-half"
                    colorType="{{ ($staleProjects ?? 0) > 0 ? 'accent' : 'success' }}" :stat="(int) ($staleProjects ?? 0)"
                    href="{{ route('tenant.reports.projects_stale', ['tenant' => $tenantParam] + request()->query()) }}" />

                {{-- CRM / Email --}}
                <x-report-tile title="New Leads ({{ strtoupper(request('range', 'wtd')) }})" icon="fa-filter" colorType="brand"
                    :stat="(int) ($new_leads ?? 0)"
                    href="{{ route('tenant.reports.leads.new', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="Email Activity" icon="fa-envelope" colorType="brand" :stat="(int) ($email_outbound ?? 0) . ' sent'" :substat="(int) ($email_inbound ?? 0) . ' received'"
                    href="{{ route('tenant.reports.emails.activity', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="AR Aging" icon="fa-chart-column" colorType="info" stat="View buckets"
                    href="{{ route('tenant.reports.ar_aging', ['tenant' => $tenantParam] + request()->query()) }}" />

                {{-- Bottlenecks --}}
                <x-report-tile title="Pipeline Aging" icon="fa-hourglass-half" colorType="warning"
                    href="{{ route('tenant.reports.pipeline_aging', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="WIP / Workload" icon="fa-layer-group" colorType="secondary"
                    href="{{ route('tenant.reports.wip', ['tenant' => $tenantParam] + request()->query()) }}" />

                <x-report-tile title="Throughput" icon="fa-chart-line" colorType="brand"
                    href="{{ route('tenant.reports.throughput', ['tenant' => $tenantParam] + request()->query()) }}" />
            </div>
        </section>

    {{-- Optional: data table preview (keeps page useful without click-through) --}}
    @if (!empty($previewRows ?? []))
        <div class="oh-card mt-6">
            <h2 class="oh-section-title mb-3">{{ $previewTitle ?? 'Preview' }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-text-subtle">
                        <tr>
                            @foreach (array_keys($previewRows[0]) as $col)
                                <th class="py-2 pr-4">{{ ucfirst($col) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                        @foreach ($previewRows as $row)
                            <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                                @foreach ($row as $val)
                                    <td class="py-2 pr-4 text-text-base">{{ is_scalar($val) ? $val : json_encode($val) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
