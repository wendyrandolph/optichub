@extends('layouts.trades')

@section('title', 'Performance')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $ranges = [
            'this_week' => 'This week',
            'this_month' => 'This month',
            'last_30' => 'Last 30 days',
        ];
        $currency = $tenant->default_currency ?? 'USD';
        $formatValue = function ($kpi) use ($currency) {
            $value = $kpi['value'] ?? null;
            if ($value === null) {
                return '—';
            }
            if (($kpi['value_format'] ?? null) === 'labor_hours' && is_array($value)) {
                $billable = number_format((float) ($value['billable_hours'] ?? 0), 1);
                $paid = number_format((float) ($value['paid_hours'] ?? 0), 1);
                return "{$billable} billable hrs · {$paid} paid hrs";
            }
            if (($kpi['value_format'] ?? null) === 'money') {
                return $currency . ' ' . number_format((float) $value, 2);
            }
            $suffix = $kpi['value_suffix'] ?? '';
            return $suffix ? $value . $suffix : $value;
        };
        $cardOrder = [
            'leads',
            'sales',
            'capacity',
            'labor',
            'results',
            'cash',
            'unscheduled',
        ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Performance</h1>
                <p class="text-sm text-text-subtle mt-1">
                    A quick view of pipeline, capacity, and cash health.
                </p>
            </div>
            <div class="inline-flex rounded-full border border-border-default bg-surface-card p-1 text-xs">
                @foreach ($ranges as $key => $label)
                    <a href="{{ route('tenant.trades.performance.index', ['tenant' => $tenantKey, 'range' => $key]) }}"
                        class="px-3 py-1 rounded-full {{ $range === $key ? 'bg-[rgb(var(--brand-primary))] text-white' : 'text-text-subtle hover:text-text-base' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($cardOrder as $key)
                @php
                    $kpi = $kpis[$key] ?? null;
                    $value = $kpi ? $formatValue($kpi) : '—';
                    $delta = $kpi['delta'] ?? null;
                    $tone = 'muted';
                    if ($delta) {
                        $tone = str_starts_with($delta, '+') ? 'success' : (str_starts_with($delta, '-') ? 'danger' : 'muted');
                    }
                    $helper = $kpi['note'] ?? ($kpi['secondary'] ?? 'View details');
                    $title = $kpi['label'] ?? ucfirst($key);
                @endphp
                @if ($kpi)
                    @include('trades.performance._kpi-card', [
                        'title' => $title,
                        'value' => $value,
                        'delta' => $delta,
                        'helper' => $helper,
                        'href' => route('tenant.trades.performance.show', ['tenant' => $tenantKey, 'kpi' => $key, 'range' => $range]),
                        'tone' => $tone,
                    ])
                @endif
            @endforeach
        </div>
    </div>
@endsection
