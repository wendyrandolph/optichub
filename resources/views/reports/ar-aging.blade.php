@extends('layouts.app')
@section('title', 'AR Aging')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $asOf = $filters['as_of'] ?? now()->toDateString();
        $status = $filters['status'] ?? null;
        $bucket = $filters['bucket'] ?? 'all';
        $sort = $filters['sort'] ?? 'due_date';
        $dir = $filters['dir'] ?? 'asc';
        $qText = $filters['qText'] ?? '';
        $min = $filters['min'] ?? '';
        $max = $filters['max'] ?? '';
        $buckets = ['current', '0-30', '31-60', '61-90', '90+'];

        $th = function ($key, $label) use ($sort, $dir, $tenantParam, $asOf, $status, $bucket, $qText, $min, $max) {
            $next = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.ar_aging', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $next,
                'as_of' => $asOf,
                'status' => $status,
                'bucket' => $bucket,
                'q' => $qText,
                'min' => $min,
                'max' => $max,
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
    @endphp

    <x-reports.back-button :tenant="$tenant" />

    {{-- Header / Filters --}}
    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">AR Aging</h1>
                <p class="text-sm text-text-subtle">As of {{ \Carbon\Carbon::parse($asOf)->format('M d, Y') }}</p>
            </div>

            <form method="GET" action="{{ route('tenant.admin.reports.ar_aging', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <input type="date" name="as_of" value="{{ $asOf }}" class="oh-select">

                <select name="status" class="oh-select">
                    <option value="" @selected($status === null)>Sent + Overdue</option>
                    @foreach (['sent' => 'Sent', 'overdue' => 'Overdue', 'draft' => 'Draft', 'paid' => 'Paid', 'void' => 'Void'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <input name="q" value="{{ $qText }}" placeholder="Search client / number" class="oh-select"
                    style="width:220px">
                <input name="min" value="{{ $min }}" placeholder="Min $" class="oh-select" style="width:120px">
                <input name="max" value="{{ $max }}" placeholder="Max $" class="oh-select" style="width:120px">

                @foreach ($buckets as $label => $amount)
                    <input type="hidden" name="buckets[{{ $label }}]" value="{{ (float) $amount }}">
                @endforeach
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.ar_aging', ['tenant' => $tenantParam]) }}">Reset</a>
            </form>
        </div>
    </div>

    {{-- Bucket chips + totals --}}
    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center gap-2">
            @php
                $chipUrl = fn($b) => route(
                    'tenant.admin.reports.ar_aging',
                    ['tenant' => $tenantParam] + array_merge(request()->query(), ['bucket' => $b]),
                );
            @endphp
            <a href="{{ $chipUrl('all') }}" class="chip {{ $bucket === 'all' ? 'chip--status-active' : 'chip--muted' }}">
                All • ${{ number_format(array_sum(array_map(fn($x) => $x['total'], $summary ?? [])), 2) }}
            </a>
            @foreach ($buckets as $b)
                <a href="{{ $chipUrl($b) }}" class="chip {{ $bucket === $b ? 'chip--status-active' : 'chip--muted' }}">
                    {{ strtoupper($b) }} • ${{ number_format((float) ($summary[$b]['total'] ?? 0), 2) }}
                    <span class="ml-1 text-[11px] text-text-subtle">({{ (int) ($summary[$b]['count'] ?? 0) }})</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Grand total --}}
    <div class="oh-card mb-4 flex items-center justify-between">
        <div class="text-sm text-text-subtle">Total outstanding (all buckets)</div>
        <div class="text-lg font-semibold">${{ number_format((float) ($total ?? 0), 2) }}</div>
    </div>
    <div class="oh-card mb-4">
        <x-oh-chart :config="$agingConfig ?? []" class="w-full h-64 mb-4" />
    </div>
    {{-- Detail table --}}
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th('due_date', 'Due') !!}</th>
                    <th class="py-2 pr-4">{!! $th('number', 'Invoice') !!}</th>
                    <th class="py-2 pr-4">{!! $th('client_name', 'Client') !!}</th>
                    <th class="py-2 pr-4">{!! $th('status', 'Status') !!}</th>
                    <th class="py-2 pr-2 text-right">{!! $th('balance_due', 'Outstanding') !!}</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows as $r)
                    <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($r->due_date)->format('M d, Y') }}</td>
                        <td class="py-2 pr-4">
                            <a class="text-brand-primary hover:underline"
                                href="{{ route('tenant.invoices.show', ['tenant' => $tenantParam, 'invoice' => $r->id]) }}">
                                {{ $r->number ?? '#' . $r->id }}
                            </a>
                        </td>
                        <td class="py-2 pr-4">{{ $r->client_name ?? '—' }}</td>
                        <td class="py-2 pr-4"><span class="chip chip--muted">{{ ucfirst($r->status) }}</span></td>
                        <td class="py-2 pr-2 text-right tabular-nums">${{ number_format((float) $r->balance_due, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-text-subtle">No open invoices match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
@endsection
