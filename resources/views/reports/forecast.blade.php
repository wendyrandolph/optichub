@extends('layouts.app')
@section('title', 'Forecast')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $window = $filters['window'] ?? 'next14';
        $from = $filters['from'] ?? now()->toDateString();
        $to = $filters['to'] ?? now()->addDays(14)->toDateString();
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'due_date';
        $dir = $filters['dir'] ?? 'asc';
        $qText = $filters['qText'] ?? '';
        $min = $filters['min'] ?? '';
        $max = $filters['max'] ?? '';

        $th = function ($key, $label) use (
            $sort,
            $dir,
            $tenantParam,
            $window,
            $from,
            $to,
            $status,
            $qText,
            $min,
            $max,
        ) {
            $next = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.forecast', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $next,
                'window' => $window,
                'from' => $from,
                'to' => $to,
                'status' => $status,
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
                <h1 class="text-lg font-semibold text-text-base">Forecast</h1>
                <p class="text-sm text-text-subtle">Expected receipts in the selected window</p>
            </div>

            <form method="GET" action="{{ route('tenant.admin.reports.forecast', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <select name="window" class="oh-select"
                    onchange="this.form.querySelectorAll('[data-custom-range]').forEach(el=>el.classList.toggle('hidden', this.value!=='custom'))">
                    @foreach (['next7' => 'Next 7d', 'next14' => 'Next 14d', 'next30' => 'Next 30d', 'custom' => 'Custom'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($window === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <div class="{{ $window === 'custom' ? '' : 'hidden' }} flex items-center gap-2" data-custom-range>
                    <input type="date" name="from" value="{{ $from }}" class="oh-select">
                    <span class="text-text-subtle text-xs">to</span>
                    <input type="date" name="to" value="{{ $to }}" class="oh-select">
                </div>

                <select name="status" class="oh-select">
                    <option value="" @selected($status === null)>Draft + Sent</option>
                    @foreach (['draft' => 'Draft', 'sent' => 'Sent', 'overdue' => 'Overdue', 'paid' => 'Paid', 'void' => 'Void'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <input name="q" value="{{ $qText }}" placeholder="Search client / number" class="oh-select"
                    style="width:220px">
                <input name="min" value="{{ $min }}" placeholder="Min $" class="oh-select" style="width:120px">
                <input name="max" value="{{ $max }}" placeholder="Max $" class="oh-select"
                    style="width:120px">

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.forecast', ['tenant' => $tenantParam]) }}">Reset</a>


            </form>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="oh-card mb-4 flex items-center justify-between">
        <div class="text-sm text-text-subtle">
            Total expected ({{ strtoupper($window) }}{{ $window === 'custom' ? " $from → $to" : '' }})
        </div>
        <div class="text-lg font-semibold">
            ${{ number_format((float) ($summary['total'] ?? 0), 2) }}
        </div>
    </div>

    <div class="oh-card mb-4">
        <x-oh-chart :config="$forecastConfig" class="w-full h-64 mb-6" />
    </div>

    {{-- Table --}}
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th('due_date', 'Due') !!}</th>
                    <th class="py-2 pr-4">{!! $th('number', 'Invoice') !!}</th>
                    <th class="py-2 pr-4">{!! $th('client_name', 'Client') !!}</th>
                    <th class="py-2 pr-4">{!! $th('status', 'Status') !!}</th>
                    <th class="py-2 pr-2 text-right">{!! $th('balance_due', 'Expected') !!}</th>
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
                        <td class="py-2 pr-2 text-right tabular-nums">
                            ${{ number_format((float) $r->balance_due, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-text-subtle">No invoices in this window.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
@endsection
