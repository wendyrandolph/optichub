@extends('layouts.trades')

@section('title', 'Performance Detail')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $ranges = [
            'this_week' => 'This week',
            'this_month' => 'This month',
            'last_30' => 'Last 30 days',
        ];
        $titles = [
            'leads' => 'Lead Flow',
            'sales' => 'Sales Performance',
            'capacity' => 'Fulfillment Capacity',
            'labor' => 'Labor Efficiency',
            'results' => 'Results',
            'cash' => 'Cash Flow',
            'unscheduled' => 'Unscheduled Jobs',
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
        $title = $titles[$kpi] ?? 'Performance';
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('tenant.trades.performance.index', ['tenant' => $tenantKey, 'range' => $range]) }}"
                    class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                    Back to performance
                </a>
                <h1 class="text-2xl font-semibold text-text-base mt-2">{{ $title }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Showing {{ $from->format('M j') }} – {{ $to->format('M j, Y') }}.
                </p>
            </div>
            <div class="inline-flex rounded-full border border-border-default bg-surface-card p-1 text-xs">
                @foreach ($ranges as $key => $label)
                    <a href="{{ route('tenant.trades.performance.show', ['tenant' => $tenantKey, 'kpi' => $kpi, 'range' => $key]) }}"
                        class="px-3 py-1 rounded-full {{ $range === $key ? 'bg-[rgb(var(--brand-primary))] text-white' : 'text-text-subtle hover:text-text-base' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <form method="GET"
            action="{{ route('tenant.trades.performance.show', ['tenant' => $tenantKey, 'kpi' => $kpi]) }}"
            class="oh-card p-4 flex flex-wrap items-end gap-3 text-sm">
            <input type="hidden" name="range" value="{{ $range }}">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-text-subtle">From</label>
                <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}"
                    class="oh-input h-9">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-text-subtle">To</label>
                <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}"
                    class="oh-input h-9">
            </div>
            <button class="oh-btn oh-btn--primary" type="submit">Update</button>
        </form>

        <div class="oh-card p-5">
            <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $title }}</div>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div class="text-3xl font-semibold text-text-base tabular-nums">
                    {{ $summary ? $formatValue($summary) : '—' }}
                </div>
                @if (!empty($summary['delta']))
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] text-text-subtle">
                        {{ $summary['delta'] }}
                    </span>
                @endif
                @if (!empty($summary['avg_days_delta']))
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] text-text-subtle">
                        {{ $summary['avg_days_delta'] }} avg days
                    </span>
                @endif
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-xs text-text-subtle">
                @if (!empty($summary['secondary']))
                    <span class="inline-flex items-center rounded-full border px-2 py-1">{{ $summary['secondary'] }}</span>
                @endif
                @if (!empty($summary['note']))
                    <span class="inline-flex items-center rounded-full border px-2 py-1">Not available yet</span>
                @endif
            </div>
        </div>

        @if (!empty($detail['note']))
            <div class="oh-card p-4 text-sm text-text-subtle">
                Not available yet. {{ $detail['note'] }}
            </div>
        @else
            @if ($kpi === 'leads')
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="oh-card p-4 lg:col-span-2">
                        <div class="text-sm font-medium text-text-base">Leads by day</div>
                        <div class="mt-3">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-text-subtle bg-surface-muted">
                                        <th class="text-left py-2">Day</th>
                                        <th class="text-right py-2">Leads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($detail['rows'] ?? []) as $row)
                                        <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                            <td class="py-2">{{ $row['day'] }}</td>
                                            <td class="py-2 text-right">{{ $row['count'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-3 text-text-subtle">No leads yet in this range.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="oh-card p-4">
                            <div class="text-sm font-medium text-text-base">Response time</div>
                            @php
                                $response = $detail['response'] ?? [];
                            @endphp
                            @if (!empty($response['available']))
                                <div class="mt-3 space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Median</span>
                                        <span class="text-text-subtle">
                                            {{ $response['median'] !== null ? $response['median'] . ' min' : '—' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>P90</span>
                                        <span class="text-text-subtle">
                                            {{ $response['p90'] !== null ? $response['p90'] . ' min' : '—' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Average</span>
                                        <span class="text-text-subtle">
                                            {{ $response['avg'] !== null ? $response['avg'] . ' min' : '—' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>SLA breaches</span>
                                        <span class="text-text-subtle">
                                            {{ $response['breaches'] ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                                @if (!empty($response['note']))
                                    <p class="text-xs text-text-subtle mt-3">{{ $response['note'] }}</p>
                                @endif
                            @else
                                <p class="text-sm text-text-subtle mt-3">Not available yet.</p>
                            @endif
                        </div>

                        <div class="oh-card p-4">
                            <div class="text-sm font-medium text-text-base">Status mix</div>
                            <ul class="mt-3 space-y-2 text-sm">
                                @forelse (($detail['status_counts'] ?? []) as $status)
                                    <li class="flex justify-between">
                                        <span class="capitalize">{{ str_replace('_', ' ', $status['status']) }}</span>
                                        <span class="text-text-subtle">{{ $status['count'] }}</span>
                                    </li>
                                @empty
                                    <li class="text-text-subtle">No status data yet.</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="oh-card p-4">
                            <div class="text-sm font-medium text-text-base">Attribution</div>
                            <ul class="mt-3 space-y-2 text-sm">
                                @php
                                    $attribution = $detail['attribution'] ?? [];
                                    $sources = $detail['sources'] ?? [];
                                @endphp
                                @if (!empty($attribution))
                                    @foreach ($attribution as $row)
                                        <li class="flex justify-between">
                                            <span>{{ $row['label'] }}</span>
                                            <span class="text-text-subtle">{{ $row['count'] }}</span>
                                        </li>
                                    @endforeach
                                @elseif (!empty($sources))
                                    @foreach ($sources as $source)
                                        <li class="flex justify-between">
                                            <span>{{ $source['source'] }}</span>
                                            <span class="text-text-subtle">{{ $source['count'] }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="text-text-subtle">No attribution data yet.</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            @elseif ($kpi === 'sales')
                <div class="oh-card p-4">
                    <div class="text-sm font-medium text-text-base">Quotes sent vs accepted</div>
                    <table class="w-full text-sm mt-3">
                        <thead>
                            <tr class="text-text-subtle bg-surface-muted">
                                <th class="text-left py-2">Day</th>
                                <th class="text-right py-2">Sent</th>
                                <th class="text-right py-2">Accepted</th>
                                <th class="text-right py-2">Win rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($detail['rows'] ?? []) as $row)
                                <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                    <td class="py-2">{{ $row['day'] }}</td>
                                    <td class="py-2 text-right">{{ $row['sent'] }}</td>
                                    <td class="py-2 text-right">{{ $row['accepted'] }}</td>
                                    <td class="py-2 text-right">{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-text-subtle">No quotes in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($kpi === 'capacity')
                <div class="oh-card p-4">
                    <div class="text-sm font-medium text-text-base">Scheduled vs available hours</div>
                    <table class="w-full text-sm mt-3">
                        <thead>
                            <tr class="text-text-subtle bg-surface-muted">
                                <th class="text-left py-2">Day</th>
                                <th class="text-right py-2">Scheduled</th>
                                <th class="text-right py-2">Available</th>
                                <th class="text-right py-2">Utilization</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($detail['rows'] ?? []) as $row)
                                <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                    <td class="py-2">{{ $row['day'] }}</td>
                                    <td class="py-2 text-right">{{ $row['scheduled_hours'] }}</td>
                                    <td class="py-2 text-right">{{ $row['available_hours'] }}</td>
                                    <td class="py-2 text-right">{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-text-subtle">No schedule data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($kpi === 'labor')
                <div class="oh-card p-4">
                    <div class="text-sm font-medium text-text-base">Top techs by billable hours</div>
                    <table class="w-full text-sm mt-3">
                        <thead>
                            <tr class="text-text-subtle bg-surface-muted">
                                <th class="text-left py-2">Tech</th>
                                <th class="text-right py-2">Billable</th>
                                <th class="text-right py-2">Paid</th>
                                <th class="text-right py-2">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($detail['rows'] ?? []) as $row)
                                <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                    <td class="py-2">{{ $row['name'] }}</td>
                                    <td class="py-2 text-right">{{ $row['billable_hours'] }}</td>
                                    <td class="py-2 text-right">{{ $row['paid_hours'] }}</td>
                                    <td class="py-2 text-right">{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-text-subtle">No labor data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($kpi === 'results')
                <div class="oh-card p-4">
                    <div class="text-sm font-medium text-text-base">On-time completion</div>
                    <table class="w-full text-sm mt-3">
                        <thead>
                            <tr class="text-text-subtle bg-surface-muted">
                                <th class="text-left py-2">Day</th>
                                <th class="text-right py-2">Completed</th>
                                <th class="text-right py-2">On time</th>
                                <th class="text-right py-2">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($detail['rows'] ?? []) as $row)
                                <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                    <td class="py-2">{{ $row['day'] }}</td>
                                    <td class="py-2 text-right">{{ $row['completed'] }}</td>
                                    <td class="py-2 text-right">{{ $row['on_time'] }}</td>
                                    <td class="py-2 text-right">{{ $row['rate'] !== null ? $row['rate'] . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-text-subtle">No completion data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($kpi === 'cash')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="oh-card p-4">
                        <div class="text-sm font-medium text-text-base">Outstanding buckets</div>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach (($detail['buckets'] ?? []) as $label => $amount)
                                <li class="flex justify-between">
                                    <span>{{ $label }}</span>
                                    <span class="text-text-subtle">{{ $currency }} {{ number_format($amount, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="oh-card p-4">
                        <div class="text-sm font-medium text-text-base">Average days to pay</div>
                        <div class="mt-3 text-3xl font-semibold text-text-base">
                            {{ $detail['avg_days'] !== null ? $detail['avg_days'] : '—' }}
                        </div>
                        <p class="text-xs text-text-subtle mt-1">Based on paid invoices in range.</p>
                    </div>
                </div>
            @elseif ($kpi === 'unscheduled')
                <div class="oh-card p-4">
                    <div class="text-sm font-medium text-text-base">Unscheduled jobs</div>
                    <table class="w-full text-sm mt-3">
                        <thead>
                            <tr class="text-text-subtle bg-surface-muted">
                                <th class="text-left py-2">Job</th>
                                <th class="text-right py-2">Age (days)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($detail['rows'] ?? []) as $row)
                                <tr class="border-t border-border-default/60 odd:bg-surface-muted/50">
                                    <td class="py-2">
                                        <a href="{{ route('tenant.trades.jobs.show', ['tenant' => $tenantKey, 'job' => $row['id']]) }}"
                                            class="hover:text-text-base">
                                            {{ $row['summary'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 text-right">{{ $row['age_days'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-3 text-text-subtle">No unscheduled jobs.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
@endsection
