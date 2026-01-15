@extends('layouts.app')
@section('title', 'Pipeline Aging')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $leadStats = $leadStats ?? collect();
        $oppStats = $oppStats ?? collect();
        $leadBuckets = $leadBuckets ?? [];
        $oppBuckets = $oppBuckets ?? [];
        $bucketRanges = $bucketRanges ?? [];
        $owners = $owners ?? collect();
        $leadStatuses = $leadStatuses ?? collect();
        $oppStages = $oppStages ?? collect();
        $filters = $filters ?? [];
        $baseLeadQuery = array_filter([
            'owner_id' => $filters['owner_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ], fn($v) => $v !== null && $v !== '');
        $baseOppQuery = array_filter([
            'owner_id' => $filters['owner_id'] ?? null,
            'stage' => $filters['stage_id'] ?? null,
        ], fn($v) => $v !== null && $v !== '');
    @endphp

    <x-reports.back-button :tenant="$tenant" />

    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Pipeline Aging</h1>
                <p class="text-sm text-text-subtle">Average days since last movement (status/stage change) by stage/status.</p>
            </div>
            <form method="GET" action="{{ route('tenant.reports.pipeline_aging', ['tenant' => $tenantParam]) }}" class="flex flex-wrap items-end gap-3 text-sm">
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">Range</span>
                    <select name="range" class="oh-select">
                        @foreach ([30, 60, 90] as $opt)
                            <option value="{{ $opt }}" @selected(($filters['range'] ?? 90) == $opt)>{{ $opt }} days</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">Owner</span>
                    <select name="owner_id" class="oh-select">
                        <option value="">All owners</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected(($filters['owner_id'] ?? '') == $owner->id)>
                                {{ $owner->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">Lead Status</span>
                    <select name="status" class="oh-select">
                        <option value="">All statuses</option>
                        @foreach ($leadStatuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">Opportunity Stage</span>
                    <select name="stage_id" class="oh-select">
                        <option value="">All stages</option>
                        @foreach ($oppStages as $stage)
                            <option value="{{ $stage }}" @selected(($filters['stage_id'] ?? '') === $stage)>
                                {{ ucfirst($stage) }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">Min Age (days)</span>
                    <input type="number" min="0" name="min_age_days" value="{{ $filters['min_age_days'] ?? 0 }}" class="oh-input w-24">
                </label>
                <div class="flex items-end gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                    <a href="{{ route('tenant.reports.pipeline_aging', ['tenant' => $tenantParam]) }}" class="oh-btn">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="oh-card">
            <h2 class="text-sm font-semibold text-text-base mb-2">Leads Aging Buckets</h2>
            <x-oh-chart :config="$leadConfig" class="w-full h-64" />
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                @foreach ($leadBuckets as $label => $count)
                    @php
                        [$min, $max] = $bucketRanges[$label] ?? [null, null];
                        $query = $baseLeadQuery;
                        if ($min !== null) {
                            $query['min_age_days'] = $min;
                        }
                        if ($max !== null) {
                            $query['max_age_days'] = $max;
                        }
                    @endphp
                    <a href="{{ route('tenant.leads.index', array_merge(['tenant' => $tenantParam], $query)) }}"
                       class="flex items-center justify-between rounded-md border px-2 py-1 text-text-subtle hover:text-text-base"
                       style="border-color: rgb(var(--border-default));">
                        <span>{{ $label }} days</span>
                        <span class="font-semibold text-text-base">{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="oh-card">
            <h2 class="text-sm font-semibold text-text-base mb-2">Opportunities Aging Buckets</h2>
            <x-oh-chart :config="$oppConfig" class="w-full h-64" />
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                @foreach ($oppBuckets as $label => $count)
                    @php
                        [$min, $max] = $bucketRanges[$label] ?? [null, null];
                        $query = $baseOppQuery;
                        if ($min !== null) {
                            $query['min_age_days'] = $min;
                        }
                        if ($max !== null) {
                            $query['max_age_days'] = $max;
                        }
                    @endphp
                    <a href="{{ route('tenant.opportunities.index', array_merge(['tenant' => $tenantParam], $query)) }}"
                       class="flex items-center justify-between rounded-md border px-2 py-1 text-text-subtle hover:text-text-base"
                       style="border-color: rgb(var(--border-default));">
                        <span>{{ $label }} days</span>
                        <span class="font-semibold text-text-base">{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mt-4">
        <div class="oh-card">
            <h3 class="text-sm font-semibold text-text-base mb-3">Leads by Status</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle">
                        <tr class="border-b" style="border-color: rgb(var(--border-default));">
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4 text-right">Count</th>
                            <th class="py-2 pr-2 text-right">Avg Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                        @forelse ($leadStats as $status => $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.leads.index', array_merge(['tenant' => $tenantParam], $baseLeadQuery, ['status' => $status])) }}">
                                        {{ ucfirst($status) }}
                                    </a>
                                </td>
                                <td class="py-2 pr-4 text-right">
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.leads.index', array_merge(['tenant' => $tenantParam], $baseLeadQuery, ['status' => $status])) }}">
                                        {{ (int) ($row['count'] ?? 0) }}
                                    </a>
                                </td>
                                <td class="py-2 pr-2 text-right">{{ number_format((float) ($row['avg'] ?? 0), 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-text-subtle">No leads yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="oh-card">
            <h3 class="text-sm font-semibold text-text-base mb-3">Opportunities by Stage</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle">
                        <tr class="border-b" style="border-color: rgb(var(--border-default));">
                            <th class="py-2 pr-4">Stage</th>
                            <th class="py-2 pr-4 text-right">Count</th>
                            <th class="py-2 pr-2 text-right">Avg Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                        @forelse ($oppStats as $stage => $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.opportunities.index', array_merge(['tenant' => $tenantParam], $baseOppQuery, ['stage' => $stage])) }}">
                                        {{ ucfirst($stage) }}
                                    </a>
                                </td>
                                <td class="py-2 pr-4 text-right">
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.opportunities.index', array_merge(['tenant' => $tenantParam], $baseOppQuery, ['stage' => $stage])) }}">
                                        {{ (int) ($row['count'] ?? 0) }}
                                    </a>
                                </td>
                                <td class="py-2 pr-2 text-right">{{ number_format((float) ($row['avg'] ?? 0), 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-text-subtle">No opportunities yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
