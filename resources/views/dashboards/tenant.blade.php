@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        $metrics = $metrics ?? ['new' => 0, 'convRate' => 0, 'avgDaysToConvert' => 0, 'active' => 0];
        $byStatus = $byStatus ?? ['labels' => [], 'datasets' => []];
        $bySource = $bySource ?? ['labels' => [], 'datasets' => []];
        $growth = $growth ?? ['labels' => [], 'datasets' => []];
        $funnel = $funnel ?? [
            'labels' => ['New', 'Contacted', 'Qualified', 'Proposal', 'Won'],
            'datasets' => [['label' => 'Leads', 'data' => [0, 0, 0, 0, 0], 'backgroundColor' => '#2E5D95']],
        ];
        $recentLeads = $recentLeads ?? collect();
        $owners = $owners ?? [];
        $sources = $sources ?? [];

        $range = request('range', 'mtd');
        $owner = request('owner');
        $src = request('source');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Dashboard</p>
                <h1 class="text-2xl md:text-3xl font-semibold text-text-base">Workspace Overview</h1>
                <p class="text-sm text-text-subtle mt-1">Key metrics for your leads, tasks, and projects.</p>
            </div>
        </div>

        {{-- Filter bar --}}
        <form method="GET" class="oh-card border border-border-default/70 shadow-sm rounded-2xl p-4 md:p-5">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['today' => 'Today', 'wtd' => 'WTD', 'mtd' => 'MTD', '30d' => '30D', '90d' => '90D'] as $key => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="range" value="{{ $key }}" class="sr-only" @checked($range === $key)>
                            <span class="oh-btn h-9 px-3 {{ $range === $key ? 'oh-btn--primary' : '' }}">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <select name="owner" class="oh-select h-9 text-sm">
                    <option value="">All owners</option>
                    @foreach ($owners as $o)
                        <option value="{{ $o['id'] }}" @selected($owner == $o['id'])>{{ $o['name'] }}</option>
                    @endforeach
                </select>

                <select name="source" class="oh-select h-9 text-sm">
                    <option value="">All sources</option>
                    @foreach ($sources as $s)
                        <option value="{{ $s }}" @selected($src === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2 md:justify-end">
                    <button class="oh-btn oh-btn--primary h-9 px-3">Filter</button>
                    <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId]) }}" class="oh-btn h-9 px-3">Reset</a>
                </div>
            </div>
        </form>

        {{-- KPI row --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-insight-kpi-tile title="New Leads ({{ strtoupper($range) }})" icon="fa-user-plus" :stat="(int) ($metrics['new'] ?? 0)" colorType="brand"
                href="{{ route('tenant.leads.index', ['tenant' => $tenantId] + request()->query()) }}" />
            <x-insight-kpi-tile title="Conversion Rate" icon="fa-chart-line" :stat="number_format((float) ($metrics['convRate'] ?? 0), 1) . '%'" colorType="success"
                href="{{ route('tenant.leads.index', ['tenant' => $tenantId] + request()->query()) }}" />
            <x-insight-kpi-tile title="Avg. Days to Convert" icon="fa-stopwatch" :stat="number_format((float) ($metrics['avgDaysToConvert'] ?? 0), 1)" colorType="info"
                href="{{ route('tenant.leads.index', ['tenant' => $tenantId] + request()->query()) }}" />
            <x-insight-kpi-tile title="Active Leads" icon="fa-filter" :stat="(int) ($metrics['active'] ?? 0)" colorType="accent"
                href="{{ route('tenant.leads.index', ['tenant' => $tenantId] + request()->query()) }}" />
        </div>

        {{-- Charts --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-text-base mb-3">Pipeline by Status</h3>
                <div class="relative h-[300px]">
                    @if (!empty($byStatus['labels']))
                        <canvas id="statusPie"></canvas>
                    @else
                        <x-empty-state message="No pipeline data." />
                    @endif
                </div>
            </section>

            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-text-base mb-3">Lead Sources</h3>
                <div class="relative h-[300px]">
                    @if (!empty($bySource['labels']))
                        <canvas id="sourceBar"></canvas>
                    @else
                        <x-empty-state message="No source data." />
                    @endif
                </div>
            </section>

            <section class="oh-card border border-border-default/70 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-text-base mb-3">Conversion Funnel</h3>
                <div class="relative h-[300px]">
                    <canvas id="funnelBar"></canvas>
                </div>
            </section>

            <section class="oh-card lg:col-span-3 border border-border-default rounded-2xl shadow-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold text-heading">New Leads Over Time</h3>
                </div>
                <div class="relative h-[340px]">
                    @if (!empty($growth['labels']))
                        <canvas id="growthLine"></canvas>
                    @else
                        <x-empty-state message="No timeline yet." />
                    @endif
                </div>
            </section>
        </div>

        {{-- Recent leads --}}
        <section class="oh-card bg-surface-card border border-border-default rounded-2xl shadow-card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-heading">Recent Leads</h3>
                <a href="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}" class="oh-btn">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left bg-[rgb(var(--surface-muted)/.55)]">
                        <tr class="text-[11px] uppercase tracking-wide font-semibold text-text-subtle border-b border-border-default/60">
                            <th class="py-2 pr-4">Lead</th>
                            <th class="py-2 pr-4">Owner</th>
                            <th class="py-2 pr-4">Source</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Created</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border) / .35);">
                        @forelse ($recentLeads as $lead)
                            @php
                                $id = data_get($lead, 'id');
                                $name = trim((data_get($lead, 'first_name') ?? '') . ' ' . (data_get($lead, 'last_name') ?? '')) ?: (data_get($lead, 'email') ?? 'Unknown');
                                $ownerN = data_get($lead, 'owner_name', '—');
                                $statusVal = strtolower((string) data_get($lead, 'status', 'new'));
                                $sourceVal = strtolower((string) data_get($lead, 'source', '—'));
                                $status = ucfirst((string) data_get($lead, 'status', 'New'));
                                $source = ucfirst((string) data_get($lead, 'source', '—'));
                                $created = optional(data_get($lead, 'created_at') ? \Carbon\Carbon::parse(data_get($lead, 'created_at')) : null)?->format('M j, Y') ?? '—';
                                $statusPillClass = match (true) {
                                    str_contains($statusVal, 'won') || str_contains($statusVal, 'client') || str_contains($statusVal, 'converted') => 'oh-pill oh-pill--success',
                                    str_contains($statusVal, 'lost') || str_contains($statusVal, 'closed') => 'oh-pill oh-pill--danger',
                                    str_contains($statusVal, 'qualified') || str_contains($statusVal, 'interested') => 'oh-pill oh-pill--info',
                                    str_contains($statusVal, 'contact') => 'oh-pill oh-pill--warning',
                                    default => 'oh-pill',
                                };
                                $sourcePillClass = match ($sourceVal) {
                                    'referral' => 'oh-pill oh-pill--success',
                                    'ads' => 'oh-pill oh-pill--warning',
                                    'web' => 'oh-pill oh-pill--info',
                                    default => 'oh-pill',
                                };
                            @endphp
                            <tr class="hover:bg-surface-accent/40 transition-colors">
                                <td class="py-2 pr-4 font-medium text-heading">{{ $name }}</td>
                                <td class="py-2 pr-4 text-text-subtle">{{ $ownerN }}</td>
                                <td class="py-3 pr-4"><span class="{{ $sourcePillClass }}">{{ $source }}</span></td>
                                <td class="py-3 pr-4"><span class="{{ $statusPillClass }}">{{ $status }}</span></td>
                                <td class="py-2 pr-4 text-text-subtle">{{ $created }}</td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $id]) }}" class="oh-icon-btn" title="View">
                                        <i class="fa-solid fa-circle-info text-[12px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-text-subtle">No recent leads.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
