@extends('layouts.app')
@section('title', 'Invoices Due')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $range = $filters['range'] ?? 'wtd';
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'due_date';
        $dir = $filters['dir'] ?? 'asc';
        $th = function ($key, $label) use ($sort, $dir, $tenantParam, $status, $range) {
            $nextDir = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.invoices', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $nextDir,
                'status' => $status,
                'range' => $range,
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


    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Invoices Due</h1>
                <p class="text-sm text-text-subtle">Window: {{ strtoupper($range) }}</p>
            </div>
            <form method="GET" action="{{ route('tenant.admin.reports.invoices', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <select name="range" class="oh-select">
                    @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30d'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($range === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="status" class="oh-select">
                    <option value="">Sent + Overdue</option>
                    @foreach (['sent' => 'Sent', 'overdue' => 'Overdue', 'draft' => 'Draft', 'paid' => 'Paid'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.invoices', ['tenant' => $tenantParam]) }}">Reset</a>

            </form>
        </div>
    </div>

    <div class="oh-card mb-4">
        <x-oh-chart :config="$invoicesConfig" class="w-full h-64 mb-6" />
    </div>

    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th('number', 'Number') !!}</th>
                    <th class="py-2 pr-4">{!! $th('client_name', 'Client') !!}</th>
                    <th class="py-2 pr-4">{!! $th('status', 'Status') !!}</th>
                    <th class="py-2 pr-4 text-right">{!! $th('balance_due', 'Balance Due') !!}</th>
                    <th class="py-2 pr-4">{!! $th('due_date', 'Due Date') !!}</th>
                    <th class="py-2 pr-2">{!! $th('created_at', 'Created') !!}</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows as $r)
                    <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                        <td class="py-2 pr-4">
                            <a class="text-brand-primary hover:underline"
                                href="{{ route('tenant.invoices.show', ['tenant' => $tenantParam, 'invoice' => $r->id]) }}">
                                {{ $r->number ?? '#' . $r->id }}
                            </a>
                        </td>
                        <td class="py-2 pr-4">{{ $r->client_name ?? '—' }}</td>
                        <td class="py-2 pr-4">
                            <span class="chip chip--muted">{{ ucfirst($r->status) }}</span>
                        </td>
                        <td class="py-2 pr-4 text-right">${{ number_format((float) $r->balance_due, 2) }}</td>
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($r->due_date)->format('M d, Y') }}</td>
                        <td class="py-2 pr-2 text-text-subtle">
                            {{ \Carbon\Carbon::parse($r->created_at)->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-text-subtle">No invoices in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>

    <div class="oh-card mt-4 flex items-center justify-between">
        <div class="text-sm text-text-subtle">Total</div>
        <div class="text-lg font-semibold">${{ number_format((float) ($summary['total'] ?? 0), 2) }}</div>
    </div>
@endsection
