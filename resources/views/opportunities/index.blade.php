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
                    <div class="text-xs text-text-subtle mb-1">Total Opportunities</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $k['total'] ?? 0 }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Open Pipeline</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['pipeline'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Won (MTD)</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $formatMoney($k['won_mtd'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-border-default/60 bg-surface-card/60 p-4">
                    <div class="text-xs text-text-subtle mb-1">Win Rate (90d)</div>
                    <div class="text-2xl font-semibold text-text-base">{{ $k['win_rate_90'] ?? 0 }}%</div>
                </div>
            </div>
        </section>

        {{-- Stage chips --}}
        @php
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
        @endphp
        <section class="oh-card p-4 sm:p-5 space-y-3">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Pipeline Stages</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($stages as $stage)
                    @php
                        $val = strtolower((string) $stage);
                        $isActive = $st === $val;
                        $count = (int) ($stageCounts[ucfirst($val)] ?? 0);
                        $url = route('tenant.opportunities.index', [
                            'tenant' => $tenantId,
                            'stage' => $isActive ? null : $val,
                            'q' => $q ?: null,
                            'sort' => $so ?: null,
                        ]);
                        $avgDays = $kpis['stage_meta'][$val]['avg_days'] ?? 0;
                    @endphp
                    <a href="{{ $url }}"
                        class="oh-pill {{ $isActive ? 'oh-pill--info ring-1 ring-[rgb(var(--brand-primary)/.25)]' : 'oh-pill--muted' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        <span class="flex items-center gap-2">
                            <span>{{ ucfirst($val) }}</span>
                            <span class="text-[11px] opacity-75">({{ $count }})</span>
                            @if ($avgDays)
                                <span class="text-[11px] text-text-subtle">· {{ $avgDays }}d avg</span>
                            @endif
                            @if ($isActive)
                                <i class="fa-solid fa-check text-[10px] opacity-70"></i>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-text-subtle">Filter by stage to focus follow-ups and close dates.</p>
        </section>

        {{-- Quick filters --}}
        @php
            $quickFilters = [
                ['label' => 'All', 'view' => ''],
                ['label' => 'My', 'view' => 'my'],
                ['label' => 'Needs follow-up', 'view' => 'needs_followup'],
                ['label' => 'Overdue', 'view' => 'overdue'],
                ['label' => 'Closing soon', 'view' => 'closing_soon'],
            ];
        @endphp
        <section class="oh-card p-4 sm:p-5 space-y-3">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Quick Filters</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($quickFilters as $f)
                    @php
                        $isActive = ($view ?? '') === $f['view'];
                        $url = route('tenant.opportunities.index', [
                            'tenant' => $tenantId,
                            'view' => $f['view'] ?: null,
                            'stage' => $st ?: null,
                            'q' => $q ?: null,
                            'sort' => $so ?: null,
                        ]);
                    @endphp
                    <a href="{{ $url }}"
                        class="oh-pill {{ $isActive ? 'oh-pill--info ring-1 ring-[rgb(var(--brand-primary)/.25)]' : 'oh-pill--muted' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        <span class="flex items-center gap-2">
                            <span>{{ $f['label'] }}</span>
                            @if ($isActive)
                                <i class="fa-solid fa-check text-[10px] opacity-70"></i>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-text-subtle">Use these views to prioritize the right pipeline next.</p>
        </section>

        {{-- Toolbar --}}
        <section class="oh-card p-4 sm:p-5 space-y-4">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Search & Sort</div>
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
                    @if ($q || $st || $so !== 'recent')
                        <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
            <p class="text-xs text-text-subtle">Results update instantly after applying filters.</p>
        </section>

        {{-- Desktop table --}}
        <section class="oh-card p-0 hidden min-[1220px]:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-text-subtle">
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Lead</th>
                        <th class="px-6 py-3 font-medium">Company</th>
                        <th class="px-6 py-3 font-medium">Owner</th>
                        <th class="px-6 py-3 font-medium">Stage</th>
                        <th class="px-6 py-3 font-medium">Value</th>
                        <th class="px-6 py-3 font-medium">Follow-up</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60">
            @forelse ($opportunities as $opp)
                @php
                    $overdue = $opp->next_followup_at && !in_array(strtolower($opp->stage), ['won', 'lost']) && $opp->next_followup_at->isPast();
                    $dueToday = $opp->next_followup_at && $opp->next_followup_at->isToday();
                @endphp
                <tr class="hover:bg-surface-accent/40">
                    <td class="px-6 py-3">
                        <div class="font-semibold text-text-base">{{ $opp->title }}</div>
                        @if ($opp->next_step)
                            <div class="text-[12px] text-text-subtle line-clamp-1">Next: {{ $opp->next_step }}</div>
                        @endif
                    </td>
                    @php
                        $ownerName = $opp->owner
                            ? trim(($opp->owner->first_name ?? '') . ' ' . ($opp->owner->last_name ?? '')) ?: ($opp->owner->username ?? '—')
                            : '—';
                    @endphp
                    <td class="px-6 py-3 text-text-base">{{ $opp->lead->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-text-base">{{ $opp->company->company_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-text-base">{{ $ownerName }}</td>
                            <td class="px-6 py-3">
                                <div class="space-y-1">
                                    <span class="{{ $pillClass($opp->stage) }}">{{ ucfirst($opp->stage) }}</span>
                                    @if (!is_null($opp->days_in_stage))
                                        <div class="text-[11px] text-text-subtle">{{ $opp->days_in_stage }}d in stage</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 text-text-base">{{ '$' . number_format((float) $opp->estimated_value, 0) }}</td>
                            <td class="px-6 py-3 text-text-base">
                                @if ($opp->next_followup_at)
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="text-left underline decoration-dotted js-followup" data-id="{{ $opp->id }}"
                                            data-date="{{ $opp->next_followup_at->format('Y-m-d\TH:i') }}">
                                            {{ $opp->next_followup_at->format('M j, Y g:ia') }}
                                        </button>
                                        @if ($overdue)
                                            <span class="oh-pill oh-pill--danger text-[11px]">Overdue</span>
                                        @elseif ($dueToday)
                                            <span class="oh-pill oh-pill--warning text-[11px]">Due today</span>
                                        @else
                                            <span class="oh-pill oh-pill--muted text-[11px]">Scheduled</span>
                                        @endif
                                    </div>
                                @else
                                    <button type="button" class="text-left underline decoration-dotted js-followup" data-id="{{ $opp->id }}">Set follow-up</button>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('tenant.opportunities.show', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                                        class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View opportunity">
                                        <i class="fa-solid fa-circle-info text-[12px]"></i>
                                    </a>
                                    <a href="{{ route('tenant.opportunities.edit', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                                        class="oh-icon-btn oh-tooltip" data-tooltip="Edit" aria-label="Edit opportunity">
                                        <i class="fa-solid fa-pen-to-square text-[12px]"></i>
                                    </a>
                                    <form method="POST" class="inline"
                                        action="{{ route('tenant.opportunities.destroy', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                                        onsubmit="return confirm('Delete this opportunity?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600" data-tooltip="Delete"
                                            aria-label="Delete opportunity">
                                            <i class="fa-solid fa-trash text-[12px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-text-subtle">
                                No opportunities yet. Create one to start your pipeline.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{-- Mobile cards --}}
        <section class="grid gap-3 min-[1220px]:hidden md:grid-cols-2">
            @forelse ($opportunities as $opp)
                @php
                    $overdue = $opp->next_followup_at && !in_array(strtolower($opp->stage), ['won', 'lost']) && $opp->next_followup_at->isPast();
                    $dueToday = $opp->next_followup_at && $opp->next_followup_at->isToday();
                    $leadName = $opp->lead->name ?? '—';
                    $companyName = $opp->company->company_name ?? '—';
                @endphp
                <article class="oh-card p-4 space-y-2 w-full max-w-[720px] mx-auto md:max-w-none md:mx-0">
                    <div class="flex items-start justify-between gap-3 min-w-0">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-text-base break-words">{{ $opp->title }}</div>
                            <div class="text-[12px] text-text-subtle truncate">Lead: {{ $leadName }}</div>
                            <div class="text-[12px] text-text-subtle truncate">Company: {{ $companyName }}</div>
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
                            class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View opportunity">
                            <i class="fa-solid fa-circle-info text-[12px]"></i>
                        </a>
                        <a href="{{ route('tenant.opportunities.edit', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                            class="oh-icon-btn oh-tooltip" data-tooltip="Edit" aria-label="Edit opportunity">
                            <i class="fa-solid fa-pen-to-square text-[12px]"></i>
                        </a>
                        <form method="POST" class="inline"
                            action="{{ route('tenant.opportunities.destroy', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                            onsubmit="return confirm('Delete this opportunity?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600" data-tooltip="Delete"
                                aria-label="Delete opportunity">
                                <i class="fa-solid fa-trash text-[12px]"></i>
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="oh-card p-6 text-center text-text-subtle">
                    No opportunities yet.
                </div>
            @endforelse
        </section>

        {{-- Pagination --}}
        @if (method_exists($opportunities, 'links'))
            @php $pager = $opportunities->appends(request()->query()); @endphp
            @if ($pager->hasPages())
                <div class="text-sm text-text-subtle space-y-3">
                    <div>Showing {{ $pager->firstItem() }} to {{ $pager->lastItem() }} of {{ $pager->total() }} results</div>
                    <div class="flex items-center justify-between">
                        @if ($pager->onFirstPage())
                            <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                        @else
                            <a href="{{ $pager->previousPageUrl() }}" class="oh-btn">Previous</a>
                        @endif
                        @if ($pager->hasMorePages())
                            <a href="{{ $pager->nextPageUrl() }}" class="oh-btn">Next</a>
                        @else
                            <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const followupTemplate = @json(route('tenant.opportunities.followup', ['tenant' => $tenantId, 'opportunity' => '__ID__']));
            document.querySelectorAll('.js-followup').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const current = btn.dataset.date || '';
                    const next = prompt('Set follow-up (YYYY-MM-DDTHH:MM, leave blank to clear):', current);
                    if (next === null) return;
                    const url = followupTemplate.replace('__ID__', id);
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ next_followup_at: next || null })
                    }).then(res => {
                        if (res.ok) location.reload();
                        else alert('Failed to update follow-up.');
                    }).catch(() => alert('Failed to update follow-up.'));
                });
            });
        });
    </script>
@endpush
