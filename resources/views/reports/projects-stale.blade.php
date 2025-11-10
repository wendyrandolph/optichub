@extends('layouts.app')
@section('title', 'Stale Projects')

@section('content')
    @php
        // ---- SAFE CONTEXT
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);

        // Filters with sane defaults (avoid undefined index notices)
        $f = $filters ?? [];
        $days = (int) ($f['days'] ?? 14);
        $minDays = $f['min_days'] ?? null;
        $maxDays = $f['max_days'] ?? null;
        $status = $f['status'] ?? '';
        $owner = $f['owner'] ?? '';
        $qText = $f['qText'] ?? '';
        $sort = $f['sort'] ?? 'stale_days';
        $dir = $f['dir'] ?? 'desc';

        // Guarded date formatter (handles null/invalid)
        $fmt = function ($v) {
            try {
                return \Carbon\Carbon::parse($v)->format('M d, Y');
            } catch (\Throwable $e) {
                return '—';
            }
        };

        // Sort link helper (only uses known-good route name)
        $th = function ($key, $label) use (
            $sort,
            $dir,
            $tenantParam,
            $days,
            $minDays,
            $maxDays,
            $status,
            $owner,
            $qText,
        ) {
            $next = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.projects_stale', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $next,
                'days' => $days,
                'min_days' => $minDays,
                'max_days' => $maxDays,
                'status' => $status,
                'owner' => $owner,
                'q' => $qText,
            ]);
            $arrow = $sort === $key ? ($dir === 'asc' ? '▲' : '▼') : '';
            return '<a href="' .
                $url .
                '" class="inline-flex items-center gap-1">' .
                $label .
                ' <span class="text-xs">' .
                $arrow .
                '</span></a>';
        };

        // Totals guard
        $tot = $totals ?? ['count' => 0, 'avg' => 0, 'max' => 0];
    @endphp

    {{-- Top: back + export --}}
    {{-- Back to Reports --}}
    <x-reports.back-button :tenant="$tenant" />


    {{-- Header / Filters --}}
    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Stale Projects</h1>
                <p class="text-sm text-text-subtle">Projects with no activity for {{ $minDays ?? $days }}+ days</p>
            </div>

            <form method="GET" action="{{ route('tenant.admin.reports.projects_stale', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <input type="number" min="1" name="days" value="{{ $days }}" class="oh-select"
                    title="Baseline days">
                <input type="number" min="0" name="min_days" value="{{ $minDays }}" placeholder="Min days"
                    class="oh-select">
                <input type="number" min="0" name="max_days" value="{{ $maxDays }}" placeholder="Max days"
                    class="oh-select">

                <select name="status" class="oh-select">
                    <option value="" @selected($status === '')>All statuses</option>
                    @foreach (['open' => 'Open', 'active' => 'Active', 'on_hold' => 'On Hold', 'closed' => 'Closed'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <input name="owner" value="{{ $owner }}" placeholder="Owner ID" class="oh-select"
                    style="width:140px">
                <input name="q" value="{{ $qText }}" placeholder="Search name / client" class="oh-select"
                    style="width:220px">

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.projects_stale', ['tenant' => $tenantParam]) }}">Reset</a>
            </form>
        </div>
    </div>

    {{-- Totals --}}
    <div class="oh-card mb-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-text-subtle">Stale projects</span>
            <span class="text-lg font-semibold">{{ number_format((int) ($tot['count'] ?? 0)) }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-sm text-text-subtle">Average stale days</span>
            <span class="text-lg font-semibold">{{ number_format((float) ($tot['avg'] ?? 0), 1) }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-sm text-text-subtle">Max stale days</span>
            <span class="text-lg font-semibold">{{ number_format((int) ($tot['max'] ?? 0)) }}</span>
        </div>
    </div>

    <div class="oh-card mb-3 flex flex-wrap gap-2">
        @foreach ([14, 30, 60, 90] as $preset)
            <a href="{{ route('tenant.admin.reports.projects_stale', ['tenant' => $tenantParam, 'days' => $preset]) }}"
                class="chip {{ ($days ?? 14) == $preset ? 'chip--status-active' : 'chip--muted' }}">
                {{ $preset }} days
            </a>
        @endforeach
    </div>

    <div class="oh-card mb-4">
        <x-oh-chart :config="$staleConfig" class="w-full h-64 mb-6" />
    </div>

    {{-- Table --}}
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th('stale_days', 'Days') !!}</th>
                    <th class="py-2 pr-4">{!! $th('name', 'Project') !!}</th>
                    <th class="py-2 pr-4">{!! $th('client', 'Client') !!}</th>
                    <th class="py-2 pr-4">{!! $th('owner', 'Owner') !!}</th>
                    <th class="py-2 pr-4">{!! $th('status', 'Status') !!}</th>
                    <th class="py-2 pr-4">{!! $th('updated_at', 'Last Activity') !!}</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows ?? [] as $r)
                    <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                        <td class="py-2 pr-4 tabular-nums">{{ (int) ($r->stale_days ?? 0) }}</td>
                        <td class="py-2 pr-4">
                            @php $pid = $r->id ?? null; @endphp
                            @if ($pid)
                                <a class="text-brand-primary hover:underline"
                                    href="{{ route('tenant.projects.show', ['tenant' => $tenantParam, 'project' => $pid]) }}">
                                    {{ $r->name ?? '#' . $pid }}
                                </a>
                            @else
                                {{ $r->name ?? '—' }}
                            @endif
                        </td>
                        <td class="py-2 pr-4">{{ $r->client_name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->owner_name ?? '—' }}</td>
                        <td class="py-2 pr-4"><span
                                class="chip chip--muted">{{ isset($r->status) ? ucwords(str_replace('_', ' ', $r->status)) : '—' }}</span>
                        </td>
                        <td class="py-2 pr-4">{{ $fmt($r->last_activity_at ?? null) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center">
                            <div class="text-text-subtle">No stale projects match these filters.</div>
                            <a href="{{ route('tenant.projects.index', ['tenant' => $tenantParam]) }}"
                                class="mt-3 inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-border-default text-sm hover:bg-surface-accent">
                                View all projects
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Only call links() if paginator is present --}}
        @if (isset($rows) && method_exists($rows, 'links'))
            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
@endsection
