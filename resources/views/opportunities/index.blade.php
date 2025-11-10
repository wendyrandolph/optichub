@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
    @php
        // Resolve tenant id (model or scalar → id)
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        // Current filters
        $q = request('q', '');
        $st = request('stage', '');
        $so = request('sort', 'recent');

        // Stage options (override by passing $stages from controller if you prefer)
        $stages = $stages ?? ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'];
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header + CTA --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Opportunities</h1>
                <p class="text-sm text-text-subtle mt-1">Track pipeline and projected revenue at a glance.</p>
            </div>

            <a href="{{ route('tenant.opportunities.create', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
              bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                <i class="fa-solid fa-plus mr-2 text-xs"></i> New Opportunity
            </a>
        </header>
        {{-- KPI Row --}}
        @php
            $k = $kpis ?? [
                'total' => 0,
                'pipeline' => 0,
                'won_mtd' => 0,
                'lost_mtd' => 0,
                'win_rate_90' => 0,
                'by_stage' => collect(),
            ];
            $formatMoney = fn($n) => '$' . number_format((float) $n, 0);
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Total Opportunities --}}
            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Total Opportunities</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['total'] }}</div>
            </div>

            {{-- Open Pipeline Value --}}
            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Open Pipeline</div>
                <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['pipeline']) }}</div>
            </div>

            {{-- Won (MTD) --}}
            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Won (MTD)</div>
                <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['won_mtd']) }}</div>
            </div>

            {{-- Win Rate (90d) --}}
            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Win Rate (90d)</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['win_rate_90'] }}%</div>
            </div>
        </div>

        {{-- Stage Chips --}}
        @php
            $stagePill = function ($stage) {
                return match (strtolower($stage)) {
                    'new' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'qualified' => 'bg-purple-100 text-purple-700 border-purple-200',
                    'proposal' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'negotiation' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'won' => 'bg-green-100 text-green-700 border-green-200',
                    'lost' => 'bg-red-100 text-red-700 border-red-200',
                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                };
            };
        @endphp

        <div class="flex flex-wrap gap-2">
            @foreach (['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'] as $s)
                @php $count = (int) ($k['by_stage'][$s] ?? 0); @endphp
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium {{ $stagePill($s) }}">
                    {{ $s }} <span class="ml-1 text-[11px] opacity-75">({{ $count }})</span>
                </span>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60">
            <form method="GET" action="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                class="p-4 md:p-5 flex flex-col md:flex-row md:flex-wrap gap-3 md:items-center">

                {{-- Search (slightly narrower so selects/buttons breathe) --}}
                <div class="flex-1 md:flex-none md:w-[300px]">
                    <label class="sr-only" for="q">Search</label>
                    <input id="q" name="q" value="{{ $q }}"
                        placeholder="Search title or organization…"
                        class="w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                {{-- Stage --}}
                <div class="md:w-[200px]">
                    <label class="sr-only" for="stage">Stage</label>
                    <select id="stage" name="stage"
                        class="h-10 w-full rounded-lg bg-surface-card text-text-base px-3 text-sm
                       border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="">All stages</option>
                        @foreach ($stages as $opt)
                            @php $val = (string) $opt; @endphp
                            <option value="{{ $val }}" @selected($st === $val)>{{ $val }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort --}}
                <div class="md:w-[200px]">
                    <label class="sr-only" for="sort">Sort</label>
                    <select id="sort" name="sort"
                        class="h-10 w-full rounded-lg bg-surface-card text-text-base px-3 text-sm
                       border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="recent" @selected($so === 'recent')>Recently updated</option>
                        <option value="title_asc" @selected($so === 'title_asc')>Title A–Z</option>
                        <option value="title_desc" @selected($so === 'title_desc')>Title Z–A</option>
                        <option value="value_desc" @selected($so === 'value_desc')>Value ↓</option>
                        <option value="value_asc" @selected($so === 'value_asc')>Value ↑</option>
                        <option value="close_asc" @selected($so === 'close_asc')>Close date ↑</option>
                        <option value="close_desc" @selected($so === 'close_desc')>Close date ↓</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2 md:ml-auto">
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
                       bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        <i class="fa-solid fa-filter mr-2 text-xs"></i> Filter
                    </button>

                    @if ($q || $st || $so !== 'recent')
                        <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                            class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm
                    bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-card">
                    <tr class="text-left text-text-subtle">
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Organization</th>
                        <th class="px-6 py-3 font-medium">Stage</th>
                        <th class="px-6 py-3 font-medium">Estimated Value</th>
                        <th class="px-6 py-3 font-medium">Close Date</th>
                        <th class="px-6 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60">
                    @forelse ($opportunities as $opp)
                        @php
                            $oppId = data_get($opp, 'id');
                            $title = data_get($opp, 'title', 'Untitled');
                            $orgName = data_get($opp, 'organization_name', data_get($opp, 'organization.name', '—'));
                            $stage = (string) data_get($opp, 'stage', '—');
                            $value = (float) data_get($opp, 'estimated_value', 0);
                            $close = data_get($opp, 'close_date');

                            $stageClass = \Illuminate\Support\Str::of($stage)
                                ->lower()
                                ->replace([' ', '/'], ['-', ''])
                                ->value();
                            $closeDisplay = $close
                                ? ($close instanceof \Illuminate\Support\Carbon
                                    ? $close->format('M j, Y')
                                    : \Illuminate\Support\Carbon::parse($close)->format('M j, Y'))
                                : '—';
                        @endphp
                        <tr class="hover:bg-surface-accent/30">
                            <td class="px-6 py-3 text-text-base">{{ $title }}</td>
                            <td class="px-6 py-3 text-text-base">{{ $orgName }}</td>
                            <td class="px-6 py-3">
                                {{-- Stage pill (map a few brandy colors) --}}
                                @php
                                    $pill = match (strtolower($stage)) {
                                        'new' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'qualified' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        'proposal' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'negotiation' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'won' => 'bg-green-100 text-green-700 border-green-200',
                                        'lost' => 'bg-red-100 text-red-700 border-red-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium {{ $pill }}">
                                    {{ $stage }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-text-base">${{ number_format($value, 2) }}</td>
                            <td class="px-6 py-3 text-text-base">{{ $closeDisplay }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-center gap-3 text-text-subtle">
                                    <a href="{{ route('tenant.opportunities.edit', ['tenant' => $tenantId, 'opportunity' => $oppId]) }}"
                                        class="hover:text-green-700 dark:hover:text-green-300" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('tenant.opportunities.destroy', ['tenant' => $tenantId, 'opportunity' => $oppId]) }}"
                                        onsubmit="return confirm('Delete this opportunity?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="hover:text-red-700 dark:hover:text-red-300"
                                            title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-text-subtle">
                                No opportunities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination (preserve filters) --}}
            @if (method_exists($opportunities, 'links'))
                <div class="px-4 py-3 border-t border-border-default/60">
                    {{ $opportunities->appends(request()->only('q', 'stage', 'sort'))->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
