@extends('layouts.trades')

@section('title', 'Jobs Report')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();

        $formatDuration = function (?int $seconds): string {
            $seconds = max(0, (int) ($seconds ?? 0));
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);
            return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
        };
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Insights</p>
                <h1 class="text-2xl font-semibold text-text-base">Jobs</h1>
                <p class="text-sm text-text-subtle mt-1">Job volume, backlog, and status breakdown.</p>
            </div>

            <a class="oh-btn" href="{{ route('tenant.trades.dashboard', ['tenant' => $tenantKey]) }}">
                Back to overview
            </a>
        </div>

        {{-- Date range filter --}}
        <form method="GET" action="{{ route('tenant.trades.reports.jobs', ['tenant' => $tenantKey]) }}"
            class="oh-card p-4 flex flex-col gap-3 md:flex-row md:items-end md:gap-4">
            <div class="flex-1">
                <label class="text-xs text-text-subtle">From</label>
                <input type="date" name="from"
                    value="{{ optional($from ?? null)?->toDateString() ?? request('from') }}" class="oh-input mt-1 w-full">
            </div>
            <div class="flex-1">
                <label class="text-xs text-text-subtle">To</label>
                <input type="date" name="to" value="{{ optional($to ?? null)?->toDateString() ?? request('to') }}"
                    class="oh-input mt-1 w-full">
            </div>
            <div class="flex gap-2">
                <button class="oh-btn oh-btn--primary" type="submit">Apply</button>
                <a class="oh-btn" href="{{ route('tenant.trades.reports.jobs', ['tenant' => $tenantKey]) }}">Reset</a>
            </div>
        </form>

        {{-- Summary tiles --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Jobs created</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $jobsCreated ?? 0 }}</div>
                <div class="text-xs text-text-subtle mt-1">
                    {{ optional($from ?? null)?->format('M j') }} – {{ optional($to ?? null)?->format('M j') }}
                </div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Unscheduled backlog</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $unscheduledCount ?? 0 }}</div>
                <div class="text-xs text-text-subtle mt-1">No future appointment scheduled</div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Top status</div>
                @php
                    $topStatus = ($statusBreakdown ?? collect())->first();
                @endphp
                <div class="text-2xl font-semibold text-text-base mt-1">
                    {{ $topStatus?->status ? ucfirst($topStatus->status) : '—' }}
                </div>
                <div class="text-xs text-text-subtle mt-1">
                    {{ $topStatus?->count ?? 0 }} jobs
                </div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Top type</div>
                @php
                    $topType = ($typeBreakdown ?? collect())->first();
                @endphp
                <div class="text-2xl font-semibold text-text-base mt-1">
                    {{ $topType?->type ? ucfirst($topType->type) : '—' }}
                </div>
                <div class="text-xs text-text-subtle mt-1">
                    {{ $topType?->count ?? 0 }} jobs
                </div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">First-time fix</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $firstTimeFixRate ?? 0 }}%</div>
                <div class="text-xs text-text-subtle mt-1">Completed jobs with 1 visit</div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Avg job duration</div>
                <div class="text-2xl font-semibold text-text-base mt-1">
                    {{ $formatDuration($avgDurationSeconds ?? 0) }}
                </div>
                <div class="text-xs text-text-subtle mt-1">Completed job timers</div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Billed vs job value</div>
                <div class="text-sm font-semibold text-text-base mt-2">
                    ${{ number_format((float) ($invoiceTotal ?? 0), 2) }}
                </div>
                <div class="text-xs text-text-subtle mt-1">
                    Job items ${{ number_format((float) ($jobItemTotal ?? 0), 2) }} · Unbilled
                    ${{ number_format((float) ($unbilledTotal ?? 0), 2) }}
                </div>
            </div>
        </div>

        {{-- Breakdown: Status + Type --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Jobs by status</div>

                @forelse(($statusBreakdown ?? []) as $row)
                    <div
                        class="flex items-center justify-between text-sm border-b border-border-default/60 py-2 last:border-b-0">
                        <span class="text-text-subtle">{{ ucfirst((string) $row->status) }}</span>
                        <span class="text-text-base font-medium">{{ $row->count }}</span>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No status data.</div>
                @endforelse
            </div>

            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Jobs by type</div>

                @forelse(($typeBreakdown ?? []) as $row)
                    <div
                        class="flex items-center justify-between text-sm border-b border-border-default/60 py-2 last:border-b-0">
                        <span class="text-text-subtle">{{ ucfirst((string) $row->type) }}</span>
                        <span class="text-text-base font-medium">{{ $row->count }}</span>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No type data.</div>
                @endforelse
            </div>
        </div>

        {{-- Unscheduled list --}}
        <div class="oh-card p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-text-base">Unscheduled jobs</div>
                <a class="text-xs text-text-subtle hover:text-text-base"
                    href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey, 'scheduling' => 'unscheduled']) }}">
                    View in jobs →
                </a>
            </div>

            @forelse(($unscheduled ?? []) as $job)
                <div class="flex items-center justify-between gap-3 border-b border-border-default/60 py-2 last:border-b-0">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-text-base truncate">{{ $job->summary }}</div>
                        <div class="text-xs text-text-subtle mt-1">
                            {{ ucfirst((string) $job->type) }} · {{ ucfirst((string) $job->status) }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a class="oh-btn text-xs"
                            href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                            Schedule
                        </a>
                        <a class="oh-btn text-xs"
                            href="{{ route('tenant.trades.jobs.show', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                            View
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-sm text-text-subtle">No unscheduled jobs 🎉</div>
            @endforelse
        </div>
    </div>
@endsection
