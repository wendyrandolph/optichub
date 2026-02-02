@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? request()->route('tenant'));
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;

        $q = request('q', '');
        $st = request('stage', '');
        $so = request('sort', 'recent');

        $stages = $stages ?? ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'];
        $stageCounts = $kpis['by_stage'] ?? collect();
        $formatMoney = fn($n) => '$' . number_format((float) $n, 0);
    @endphp

    <div class="oh-page max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 overflow-hidden">
        {{-- Header --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Pipeline</p>
                <h1 class="text-2xl font-semibold text-text-base">Opportunities</h1>
                <p class="text-sm text-text-subtle">Track pipeline health and stay on top of follow-ups.</p>
            </div>
            <div class="flex items-center justify-end">
                <a href="{{ route('tenant.opportunities.create', ['tenant' => $tenantId]) }}"
                    class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus mr-2 text-xs"></i> New Opportunity
                </a>
            </div>
        </header>

        {{-- KPIs --}}
        @php
            $k = $kpis ?? ['total' => 0, 'pipeline' => 0, 'won_mtd' => 0, 'win_rate_90' => 0];
        @endphp
        <section class="oh-card p-4 sm:p-5 space-y-4">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Overview</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Active Decisions</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $k['total'] ?? 0 }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Potential Revenue</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['pipeline'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Closed Revenue</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['won_mtd'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Close Rate</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $k['win_rate_90'] ?? 0 }}%</div>
                </div>
            </div>
        </section>

        @php
            $oppCollection = method_exists($opportunities, 'getCollection') ? $opportunities->getCollection() : $opportunities;
            $now = \Illuminate\Support\Carbon::now();
            $stalledDays = 14;
            $readyWindow = 7;
            $overdueCount = $oppCollection->filter(function ($opp) use ($now) {
                return $opp->next_followup_at &&
                    !in_array(strtolower((string) $opp->stage), ['won', 'lost']) &&
                    $opp->next_followup_at->isPast();
            })->count();
            $stalledCount = $oppCollection->filter(function ($opp) use ($now, $stalledDays) {
                return $opp->updated_at && $opp->updated_at->lt($now->copy()->subDays($stalledDays));
            })->count();
            $readyCloseCount = $oppCollection->filter(function ($opp) use ($now, $readyWindow) {
                return $opp->expected_close_date &&
                    !in_array(strtolower((string) $opp->stage), ['won', 'lost']) &&
                    $opp->expected_close_date->between($now, $now->copy()->addDays($readyWindow));
            })->count();

            $attentionCards = [
                [
                    'label' => 'Overdue follow-ups',
                    'count' => $overdueCount,
                    'view' => 'overdue',
                    'icon' => 'fa-triangle-exclamation',
                ],
                [
                    'label' => 'Stalled opportunities',
                    'count' => $stalledCount,
                    'view' => 'stalled',
                    'icon' => 'fa-hourglass-half',
                ],
                [
                    'label' => 'Ready to close',
                    'count' => $readyCloseCount,
                    'view' => 'ready_close',
                    'icon' => 'fa-flag-checkered',
                ],
            ];

            $stages = $stages ?? ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'];
            $pillClass = function ($stage) {
                return match (strtolower($stage)) {
                    'new' => 'oh-pill oh-pill--info',
                    'qualified' => 'oh-pill oh-pill--muted',
                    'proposal' => 'oh-pill oh-pill--warning',
                    'negotiation' => 'oh-pill oh-pill--warning',
                    'won' => 'oh-pill oh-pill--success',
                    'lost' => 'oh-pill oh-pill--danger',
                    default => 'oh-pill',
                };
            };

            $healthFilter = request('health', '');
            $isHealthFilter = in_array($healthFilter, ['overdue', 'stalled', 'ready', 'on_track'], true);
            if ($isHealthFilter) {
                $displayOpps = $oppCollection->filter(function ($opp) use ($healthFilter, $now, $stalledDays, $readyWindow) {
                    $stage = strtolower((string) $opp->stage);
                    if ($healthFilter === 'overdue') {
                        return $opp->next_followup_at && !in_array($stage, ['won', 'lost']) && $opp->next_followup_at->isPast();
                    }
                    if ($healthFilter === 'stalled') {
                        $stageStalled = $stage === 'stalled';
                        return $stageStalled || ($opp->updated_at && $opp->updated_at->lt($now->copy()->subDays($stalledDays)));
                    }
                    if ($healthFilter === 'ready') {
                        return $opp->expected_close_date &&
                            !in_array($stage, ['won', 'lost']) &&
                            $opp->expected_close_date->between($now, $now->copy()->addDays($readyWindow));
                    }
                    if ($healthFilter === 'on_track') {
                        $isOverdue = $opp->next_followup_at && !in_array($stage, ['won', 'lost']) && $opp->next_followup_at->isPast();
                        $isStalled = $opp->updated_at && $opp->updated_at->lt($now->copy()->subDays($stalledDays));
                        $isReady = $opp->expected_close_date &&
                            !in_array($stage, ['won', 'lost']) &&
                            $opp->expected_close_date->between($now, $now->copy()->addDays($readyWindow));
                        return !($isOverdue || $isStalled || $isReady);
                    }
                    return true;
                });
            } else {
                $displayOpps = $opportunities;
            }
        @endphp
        <section class="oh-card p-4 sm:p-5 space-y-4">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Needs Attention</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($attentionCards as $card)
                    <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-text-subtle">
                            <i class="fa-solid {{ $card['icon'] }} text-[11px]" aria-hidden="true"></i>
                            <span>{{ $card['label'] }}</span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <div class="text-2xl font-semibold text-text-base">{{ $card['count'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-text-subtle">Focus on the deals most likely to slip without attention.</p>
            <p class="text-xs text-text-subtle">Counts reflect the current page of results.</p>
        </section>

        {{-- Toolbar --}}
        <section class="oh-card p-4 sm:p-5 space-y-4">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Decide Next</div>
            <form method="GET" action="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                class="flex flex-col md:flex-row md:flex-wrap gap-3 md:items-center">
                <div class="flex-1 md:w-[320px]">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Search</span>
                        <input name="q" value="{{ $q }}" placeholder="Search title, company, or lead…"
                            class="oh-input h-10">
                    </label>
                </div>
                <div class="md:w-[200px]">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Stage</span>
                        <select name="stage" class="oh-select h-10 w-full">
                            <option value="">All stages</option>
                            @foreach ($stages as $stage)
                                @php $val = strtolower((string) $stage); @endphp
                                <option value="{{ $val }}" @selected($st === $val)>{{ ucfirst($val) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="md:w-[200px]">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Health</span>
                        <select name="health" class="oh-select h-10 w-full">
                            <option value="">All health</option>
                            <option value="overdue" @selected($healthFilter === 'overdue')>Overdue</option>
                            <option value="stalled" @selected($healthFilter === 'stalled')>Stalled</option>
                            <option value="ready" @selected($healthFilter === 'ready')>Ready</option>
                            <option value="on_track" @selected($healthFilter === 'on_track')>On track</option>
                        </select>
                    </label>
                </div>
                <div class="md:w-[200px]">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Sort by</span>
                        <select name="sort" class="oh-select h-10 w-full">
                            <option value="recent" @selected($so === 'recent')>Recently updated</option>
                            <option value="followup" @selected($so === 'followup')>Follow-up date</option>
                            <option value="close_asc" @selected($so === 'close_asc')>Close date ↑</option>
                            <option value="close_desc" @selected($so === 'close_desc')>Close date ↓</option>
                            <option value="value_desc" @selected($so === 'value_desc')>Value ↓</option>
                            <option value="value_asc" @selected($so === 'value_asc')>Value ↑</option>
                            <option value="title_asc" @selected($so === 'title_asc')>Title A–Z</option>
                            <option value="title_desc" @selected($so === 'title_desc')>Title Z–A</option>
                        </select>
                    </label>
                </div>
                <div class="flex flex-wrap gap-2 md:ml-auto md:self-end">
                    <button type="submit" class="oh-btn oh-btn--primary">
                        <i class="fa-solid fa-filter mr-2 text-xs"></i> Apply
                    </button>
                    @if ($q || $st || $healthFilter || $so !== 'recent')
                        <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
            <p class="text-xs text-text-subtle">Stay focused on the next best move.</p>
        </section>

        {{-- Desktop table --}}
        <section class="oh-card p-0 hidden min-[1220px]:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-muted/60">
                    <tr class="text-left text-text-subtle">
                        <th class="px-4 py-3 font-semibold uppercase tracking-wide text-[11px]">#</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Opportunity</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Owner</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Health</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Value</th>
                        <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px] text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60">
            @forelse ($displayOpps as $opp)
                @php
                    $overdue = $opp->next_followup_at && !in_array(strtolower($opp->stage), ['won', 'lost']) && $opp->next_followup_at->isPast();
                    $dueToday = $opp->next_followup_at && $opp->next_followup_at->isToday();
                    $lastActivity = $opp->last_activity_at ?? $opp->updated_at;
                    $stalled = $lastActivity && $lastActivity->lt($now->copy()->subDays($stalledDays));
                    $readyClose = $opp->expected_close_date &&
                        !in_array(strtolower($opp->stage), ['won', 'lost']) &&
                        $opp->expected_close_date->between($now, $now->copy()->addDays($readyWindow));
                    $healthLabel = $overdue ? 'Overdue' : ($stalled ? 'Stalled' : ($readyClose ? 'Ready' : 'On track'));
                    $healthPill = $overdue
                        ? 'oh-pill oh-pill--danger'
                        : ($stalled
                            ? 'oh-pill oh-pill--warning'
                            : ($readyClose ? 'oh-pill oh-pill--success' : 'oh-pill oh-pill--muted'));
                @endphp
                <tr class="hover:bg-surface-accent/40">
                    <td class="px-4 py-3 text-text-subtle">
                        @php
                            $baseIndex = method_exists($opportunities, 'firstItem') ? ($opportunities->firstItem() ?? 1) : 1;
                        @endphp
                        {{ $baseIndex + $loop->index }}
                    </td>
                    <td class="px-6 py-3">
                        <div class="font-semibold text-text-base">{{ $opp->title }}</div>
                        @if ($opp->next_step)
                            <div class="text-[12px] text-text-subtle line-clamp-1">Next: {{ $opp->next_step }}</div>
                        @endif
                        <div class="text-[12px] text-text-subtle">Lead: {{ $opp->lead->name ?? '—' }}</div>
                        <div class="text-[12px] text-text-subtle">Company: {{ $opp->company->company_name ?? '—' }}</div>
                    </td>
                    @php
                        $ownerName = $opp->owner
                            ? trim(($opp->owner->first_name ?? '') . ' ' . ($opp->owner->last_name ?? '')) ?: ($opp->owner->email ?? '—')
                            : '—';
                    @endphp
                    <td class="px-6 py-3 text-text-base">{{ $ownerName }}</td>
                    <td class="px-6 py-3">
                        <div class="space-y-1">
                            <span class="{{ $healthPill }}">{{ $healthLabel }}</span>
                            <div class="text-[11px] text-text-subtle">Stage: {{ ucfirst($opp->stage) }}</div>
                            @if (!is_null($opp->days_in_stage))
                                <div class="text-[11px] text-text-subtle">{{ $opp->days_in_stage }}d in stage</div>
                            @endif
                            @if ($opp->next_followup_at)
                                <div class="text-[11px] text-text-subtle">
                                    Follow-up: {{ $opp->next_followup_at->format('M j, Y g:ia') }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-3 text-text-base">{{ '$' . number_format((float) $opp->estimated_value, 0) }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('tenant.opportunities.show', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                                    class="oh-btn">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-text-subtle">
                                No opportunities yet. Create one to start your pipeline.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Mobile cards --}}
        <section class="grid gap-3 min-[1220px]:hidden md:grid-cols-2">
            @forelse ($displayOpps as $opp)
                @php
                    $overdue = $opp->next_followup_at && !in_array(strtolower($opp->stage), ['won', 'lost']) && $opp->next_followup_at->isPast();
                    $dueToday = $opp->next_followup_at && $opp->next_followup_at->isToday();
                    $leadName = $opp->lead->name ?? '—';
                    $companyName = $opp->company->company_name ?? '—';
                    $ownerName = $opp->owner
                        ? trim(($opp->owner->first_name ?? '') . ' ' . ($opp->owner->last_name ?? '')) ?: ($opp->owner->email ?? '—')
                        : '—';
                @endphp
                <article class="oh-card p-4 space-y-2 w-full max-w-[720px] mx-auto md:max-w-none md:mx-0">
                    <div class="flex items-start justify-between gap-3 min-w-0">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-text-base break-words">{{ $opp->title }}</div>
                            <div class="text-[12px] text-text-subtle truncate">Lead: {{ $leadName }}</div>
                            <div class="text-[12px] text-text-subtle truncate">Company: {{ $companyName }}</div>
                            <div class="text-[12px] text-text-subtle truncate">Owner: {{ $ownerName }}</div>
                        </div>
                        <span class="{{ $pillClass($opp->stage) }}">{{ ucfirst($opp->stage) }}</span>
                    </div>
                    <div class="text-sm text-text-base">
                        Value: {{ '$' . number_format((float) $opp->estimated_value, 0) }}
                    </div>
                    @if (!is_null($opp->days_in_stage))
                        <div class="text-[12px] text-text-subtle">In stage: {{ $opp->days_in_stage }}d</div>
                    @endif
                    <div class="text-[12px] text-text-subtle flex flex-wrap gap-2">
                        <span>Close: {{ $opp->expected_close_date ? $opp->expected_close_date->format('M j, Y') : '—' }}</span>
                        @if ($opp->next_followup_at)
                            @if ($overdue)
                                <span class="oh-pill oh-pill--danger text-[11px]">Overdue</span>
                            @elseif ($dueToday)
                                <span class="oh-pill oh-pill--warning text-[11px]">Due today</span>
                            @else
                                <span class="oh-pill oh-pill--muted text-[11px]">Scheduled</span>
                            @endif
                        @endif
                    </div>
                    @if ($opp->next_step)
                        <div class="text-[12px] text-text-subtle">Next: {{ $opp->next_step }}</div>
                    @endif
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('tenant.opportunities.show', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                            class="oh-btn">
                            View
                        </a>
                    </div>
                </article>
            @empty
                <div class="oh-card p-6 text-center text-text-subtle">
                    No opportunities yet.
                </div>
            @endforelse
        </section>

        {{-- Pagination --}}
        @if (!$isHealthFilter && method_exists($opportunities, 'links'))
            @php $pager = $opportunities->appends(request()->query()); @endphp
            @if ($pager->hasPages())
                <div class="text-sm text-text-subtle space-y-3">
                    <div>
                        {{ $pager->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
