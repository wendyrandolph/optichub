@extends('layouts.app')
@section('title', 'Collected')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $range = $filters['range'] ?? 'wtd';
        $method = $filters['method'] ?? '';
        $status = $filters['status'] ?? '';
        $sort = $filters['sort'] ?? ($dateCol ?? 'created_at');
        $dir = $filters['dir'] ?? 'desc';

        $th = function ($key, $label) use ($sort, $dir, $tenantParam, $range, $method, $status) {
            $next = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.collected', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $next,
                'range' => $range,
                'method' => $method,
                'status' => $status,
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
    {{-- Back to Reports --}}
    <x-reports.back-button :tenant="$tenant" />


    {{-- Header / Filters --}}
    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Collected</h1>
                <p class="text-sm text-text-subtle">Window: {{ strtoupper($range) }} • Date:
                    {{ strtoupper($dateCol ?? 'created_at') }}</p>
            </div>

            <form method="GET" action="{{ route('tenant.admin.reports.collected', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <select name="range" class="oh-select">
                    @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30d'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($range === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <input name="method" value="{{ $method }}" placeholder="Method (e.g. stripe, card)" class="oh-select"
                    style="width: 180px">

                <select name="status" class="oh-select">
                    <option value="" @selected($status === '')>All Statuses</option>
                    @foreach (['succeeded', 'pending', 'failed', 'refunded', 'partially_refunded', 'canceled', 'requires_action'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>
                            {{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.collected', ['tenant' => $tenantParam]) }}">Reset</a>


            </form>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="oh-card mb-4 flex items-center justify-between">
        <div class="text-sm text-text-subtle">Total collected</div>
        <div class="text-lg font-semibold">${{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</div>
    </div>

    {{-- Optional: by method breakdown --}}
    @if (!empty($byMethod))
        <div class="oh-card mb-4">
            <h3 class="oh-section-title mb-2">By Method</h3>
            <ul class="grid gap-2">
                @foreach ($byMethod as $row)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-text-base">{{ $row->method ?? '—' }}</span>
                        <span class="tabular-nums">${{ number_format((float) $row->total, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="oh-card mb-4">
        <x-oh-chart :config="$collectedConfig" class="w-full h-64 mb-6" />
    </div>
    {{-- Table --}}
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th($dateCol ?? 'created_at', 'Date') !!}</th>
                    <th class="py-2 pr-4">Invoice</th>
                    <th class="py-2 pr-4">{!! $th('method', 'Method') !!}</th>
                    <th class="py-2 pr-4">{!! $th('status', 'Status') !!}</th>
                    <th class="py-2 pr-4">Reference</th>
                    <th class="py-2 pr-2 text-right">{!! $th('amount', 'Amount') !!}</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows as $r)
                    <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                        <td class="py-2 pr-4">
                            @php
                                $d = isset($r->paid_at)
                                    ? \Carbon\Carbon::parse($r->paid_at)
                                    : \Carbon\Carbon::parse($r->created_at);
                            @endphp
                            {{ $d->format('M d, Y') }}
                        </td>
                        <td class="py-2 pr-4">
                            @if ($r->invoice_id)
                                <a class="text-brand-primary hover:underline"
                                    href="{{ route('tenant.invoices.show', ['tenant' => $tenantParam, 'invoice' => $r->invoice_id]) }}">
                                    #{{ $r->invoice_id }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-2 pr-4">{{ $r->method ?? '—' }}</td>
                        <td class="py-2 pr-4">
                            <span class="chip chip--muted">{{ ucwords(str_replace('_', ' ', $r->status ?? '')) }}</span>
                        </td>
                        <td class="py-2 pr-4 text-[12px] text-text-subtle">{{ $r->provider }} / {{ $r->provider_ref }}
                        </td>
                        <td class="py-2 pr-2 text-right tabular-nums">${{ number_format((float) $r->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-text-subtle">No payments in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
@endsection
