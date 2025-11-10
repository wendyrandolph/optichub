@extends('layouts.app')

@section('title', 'Lead Insights')

@section('content')
    @php
        // Resolve tenant for links safely
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        // Safe fallbacks from controller
        $metrics = $metrics ?? ['new' => 0, 'convRate' => 0, 'avgDaysToConvert' => 0, 'active' => 0];
        $byStatus = $byStatus ?? ['labels' => [], 'datasets' => []];
        $bySource = $bySource ?? ['labels' => [], 'datasets' => []];
        $growth = $growth ?? ['labels' => [], 'datasets' => []];
        $funnel = $funnel ?? [
            'labels' => ['New', 'Contacted', 'Qualified', 'Proposal', 'Won'],
            'datasets' => [['label' => 'Leads', 'data' => [0, 0, 0, 0, 0], 'backgroundColor' => '#2E5D95']],
        ];
        $recentLeads = $recentLeads ?? collect(); // collection or array
        $owners = $owners ?? []; // [{id,name}]
        $sources = $sources ?? []; // ['web','referral',...]

        $range = request('range', 'mtd');
        $owner = request('owner');
        $src = request('source');
    @endphp

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        {{-- Header --}}
        <header>
            <h1 class="text-2xl md:text-3xl font-semibold text-heading">Lead Insights</h1>
            <p class="mt-1 text-sm text-muted">Track acquisition, conversion, and pipeline health.</p>
        </header>

        {{-- Filter bar --}}
        <form method="GET"
            class="bg-white/80 dark:bg-gray-900/60 border border-border-default rounded-xl shadow-card p-3 md:p-4">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="flex items-center gap-2">
                    @foreach (['today' => 'Today', 'wtd' => 'WTD', 'mtd' => 'MTD', '30d' => '30D', '90d' => '90D'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['range' => $key]) }}"
                            class="px-2.5 py-1.5 rounded-lg text-sm font-medium
                         {{ $range === $key ? 'bg-blue-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <select name="owner"
                    class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                    <option value="">All owners</option>
                    @foreach ($owners as $o)
                        <option value="{{ $o['id'] }}" @selected($owner == $o['id'])>{{ $o['name'] }}</option>
                    @endforeach
                </select>

                <select name="source"
                    class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                    <option value="">All sources</option>
                    @foreach ($sources as $s)
                        <option value="{{ $s }}" @selected($src === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2 md:justify-end">
                    <button
                        class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Filter</button>
                    <a href="{{ route('tenant.dashboards.index', ['tenant' => $tenantId]) }}"
                        class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- KPI row --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-kpi-card title="New Leads ({{ strtoupper($range) }})" :value="(int) ($metrics['new'] ?? 0)" icon="fa-user-plus"
                color-type="primary" href="#" />
            <x-kpi-card title="Conversion Rate" value="{{ number_format((float) ($metrics['convRate'] ?? 0), 1) }}%"
                icon="fa-chart-line" color-type="success" href="#" />
            <x-kpi-card title="Avg. Days to Convert" :value="number_format((float) ($metrics['avgDaysToConvert'] ?? 0), 1)" icon="fa-stopwatch" color-type="info"
                href="#" />
            <x-kpi-card title="Active Leads" :value="(int) ($metrics['active'] ?? 0)" icon="fa-filter" color-type="warning" href="#" />
        </div>

        {{-- Charts --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Status pie --}}
            <section class="bg-surface-card border border-border-default rounded-xl shadow-card p-4">
                <h3 class="text-base font-semibold text-heading mb-2">Pipeline by Status</h3>
                <div class="relative h-[300px]">
                    @if (!empty($byStatus['labels']))
                        <canvas id="statusPie"></canvas>
                    @else
                        <x-empty-state message="No pipeline data." />
                    @endif
                </div>
            </section>

            {{-- Source bar --}}
            <section class="bg-surface-card border border-border-default rounded-xl shadow-card p-4">
                <h3 class="text-base font-semibold text-heading mb-2">Lead Sources</h3>
                <div class="relative h-[300px]">
                    @if (!empty($bySource['labels']))
                        <canvas id="sourceBar"></canvas>
                    @else
                        <x-empty-state message="No source data." />
                    @endif
                </div>
            </section>

            {{-- Conversion funnel (simple bar) --}}
            <section class="bg-surface-card border border-border-default rounded-xl shadow-card p-4">
                <h3 class="text-base font-semibold text-heading mb-2">Conversion Funnel</h3>
                <div class="relative h-[300px]">
                    <canvas id="funnelBar"></canvas>
                </div>
            </section>

            {{-- Growth line (full width) --}}
            <section class="lg:col-span-3 bg-surface-card border border-border-default rounded-xl shadow-card p-4">
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
        <section class="bg-surface-card border border-border-default rounded-xl shadow-card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-heading">Recent Leads</h3>
                <a href="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}"
                    class="text-blue-600 hover:text-blue-700 text-sm font-medium">View all →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-muted">
                        <tr>
                            <th class="py-2 pr-4">Lead</th>
                            <th class="py-2 pr-4">Owner</th>
                            <th class="py-2 pr-4">Source</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Created</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        @forelse ($recentLeads as $lead)
                            @php
                                $id = data_get($lead, 'id');
                                $name =
                                    trim(
                                        (data_get($lead, 'first_name') ?? '') .
                                            ' ' .
                                            (data_get($lead, 'last_name') ?? ''),
                                    ) ?:
                                    data_get($lead, 'email') ?? 'Unknown';
                                $ownerN = data_get($lead, 'owner_name', '—');
                                $status = ucfirst((string) data_get($lead, 'status', 'new'));
                                $source = ucfirst((string) data_get($lead, 'source', '—'));
                                $created =
                                    optional(
                                        data_get($lead, 'created_at')
                                            ? \Carbon\Carbon::parse(data_get($lead, 'created_at'))
                                            : null,
                                    )?->format('M j, Y') ?? '—';

                                $badge = match (strtolower($status)) {
                                    'won', 'client', 'converted' => 'bg-green-100 text-green-700 border-green-200',
                                    'lost' => 'bg-red-100 text-red-700 border-red-200',
                                    'qualified', 'interested' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    default => 'bg-blue-100 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <tr>
                                <td class="py-2 pr-4 font-medium text-heading">{{ $name }}</td>
                                <td class="py-2 pr-4 text-muted">{{ $ownerN }}</td>
                                <td class="py-2 pr-4 text-muted">{{ $source }}</td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full border text-xs {{ $badge }}">{{ $status }}</span>
                                </td>
                                <td class="py-2 pr-4 text-muted">{{ $created }}</td>
                                <td class="py-2">
                                    <a href="{{ route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $id]) }}"
                                        class="text-blue-600 hover:text-blue-700">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-muted">No recent leads.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Hidden payloads for charts --}}
        <div id="li-data" data-status='@json($byStatus)' data-source='@json($bySource)'
            data-growth='@json($growth)' data-funnel='@json($funnel)' class="hidden"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const root = document.getElementById('li-data');
            if (!root) return;

            const get = key => {
                try {
                    return JSON.parse(root.getAttribute(key) || '{}');
                } catch {
                    return {};
                }
            };

            const status = get('data-status');
            const source = get('data-source');
            const growth = get('data-growth');
            const funnel = get('data-funnel');

            const inkGrid = 'rgba(148,163,184,.18)';

            const base = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: inkGrid
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    },
                    y: {
                        grid: {
                            color: inkGrid
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    }
                }
            };

            const mk = (id, type, data, opt = {}) => {
                const el = document.getElementById(id);
                if (!el) return;
                return new Chart(el.getContext('2d'), {
                    type,
                    data,
                    options: {
                        ...base,
                        ...opt
                    }
                });
            };

            if (status?.labels?.length) {
                mk('statusPie', 'pie', status, {
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {}
                });
            }
            if (source?.labels?.length) {
                mk('sourceBar', 'bar', source);
            }
            if (growth?.labels?.length) {
                mk('growthLine', 'line', growth, {
                    elements: {
                        line: {
                            tension: .35
                        }
                    }
                });
            }
            // funnel as simple left-to-right bars (descending)
            if (funnel?.labels?.length) {
                mk('funnelBar', 'bar', funnel, {
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: inkGrid
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endpush
