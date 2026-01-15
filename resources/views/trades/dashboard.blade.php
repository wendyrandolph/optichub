@extends('layouts.trades')

@section('title', 'Operations Overview')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Operations Overview</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Today’s schedule, work needing attention, and business health.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a class="oh-btn oh-btn--primary" href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenantKey]) }}">
                    + New Job
                </a>
                <a class="oh-btn" href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey]) }}">
                    + Schedule
                </a>
                <a class="oh-btn" href="{{ route('tenant.trades.quotes.create', ['tenant' => $tenantKey]) }}">
                    + New Quote
                </a>
            </div>
        </div>
        {{-- Quick access tiles --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <a href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}"
                class="oh-card p-5 flex flex-col gap-2 hover:bg-surface-accent/40 transition">
                <span class="text-xs text-text-subtle uppercase tracking-wide">Jobs</span>
                <div class="flex items-end justify-between gap-3">
                    <span class="text-lg font-semibold text-text-base">Jobs</span>
                    <span class="text-sm text-text-subtle">{{ $counts['jobs_open'] ?? 0 }} open</span>
                </div>
                <span class="text-sm text-text-subtle">{{ $counts['jobs_unscheduled'] ?? 0 }} unscheduled</span>
            </a>

            <a href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey]) }}"
                class="oh-card p-5 flex flex-col gap-2 hover:bg-surface-accent/40 transition">
                <span class="text-xs text-text-subtle uppercase tracking-wide">Schedule</span>
                <div class="flex items-end justify-between gap-3">
                    <span class="text-lg font-semibold text-text-base">Schedule</span>
                    <span class="text-sm text-text-subtle">{{ $counts['appts_today'] ?? 0 }} today</span>
                </div>
                <span class="text-sm text-text-subtle">{{ $counts['techs_on_site'] ?? 0 }} on site</span>
            </a>

            <a href="{{ route('tenant.trades.quotes.index', ['tenant' => $tenantKey]) }}"
                class="oh-card p-5 flex flex-col gap-2 hover:bg-surface-accent/40 transition">
                <span class="text-xs text-text-subtle uppercase tracking-wide">Quotes</span>
                <div class="flex items-end justify-between gap-3">
                    <span class="text-lg font-semibold text-text-base">Quotes</span>
                    <span class="text-sm text-text-subtle">{{ $counts['quotes_pending'] ?? 0 }} pending</span>
                </div>
                <span class="text-sm text-text-subtle">{{ $counts['quotes_expiring'] ?? 0 }} expiring soon</span>
            </a>

            <a href="{{ route('tenant.trades.locations.index', ['tenant' => $tenantKey]) }}"
                class="oh-card p-5 flex flex-col gap-2 hover:bg-surface-accent/40 transition">
                <span class="text-xs text-text-subtle uppercase tracking-wide">Locations</span>
                <div class="flex items-end justify-between gap-3">
                    <span class="text-lg font-semibold text-text-base">Locations</span>
                    <span class="text-sm text-text-subtle">{{ $counts['locations'] ?? 0 }}</span>
                </div>
                <span class="text-sm text-text-subtle">Service addresses</span>
            </a>
        </div>

        {{-- Insights --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-text-base">Insights</h2>

            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('tenant.trades.reports.schedule', ['tenant' => $tenantKey]) }}"
                    class="oh-card p-5 flex items-start justify-between gap-4 hover:bg-surface-accent/40 transition">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-text-subtle">Schedule Insights</div>
                        <div class="mt-3 text-3xl font-semibold text-text-base">{{ (int) ($appointmentsThisWeek ?? 0) }}
                        </div>
                        <div class="text-xs text-text-subtle mt-1">Volume, completion, cancellations.</div>
                    </div>
                    <span class="text-xs text-text-subtle">View insights →</span>
                </a>

                <a href="{{ route('tenant.trades.reports.jobs', ['tenant' => $tenantKey]) }}"
                    class="oh-card p-5 flex items-start justify-between gap-4 hover:bg-surface-accent/40 transition">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-text-subtle">Jobs Insights</div>
                        <div class="mt-3 text-3xl font-semibold text-text-base">
                            {{ (int) ($counts['jobs_unscheduled'] ?? 0) }}</div>
                        <div class="text-xs text-text-subtle mt-1">Unscheduled work, status mix.</div>
                    </div>
                    <span class="text-xs text-text-subtle">View insights →</span>
                </a>

                <a href="{{ route('tenant.trades.reports.tech', ['tenant' => $tenantKey]) }}"
                    class="oh-card p-5 flex items-start justify-between gap-4 hover:bg-surface-accent/40 transition">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-text-subtle">Tech Insights</div>
                        <div class="mt-3 text-3xl font-semibold text-text-base">{{ (int) ($activeTechsToday ?? 0) }}</div>
                        <div class="text-xs text-text-subtle mt-1">On-site activity and job time.</div>
                    </div>
                    <span class="text-xs text-text-subtle">View insights →</span>
                </a>
            </div>
        </div>


        {{-- Split column: Ops Today + Pipeline --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- LEFT: Ops Today --}}
            <div class="space-y-6">
                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Ops Today</h2>
                        <a class="text-xs text-text-subtle hover:text-text-base"
                            href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey]) }}">
                            View schedule →
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse(($todayAppointments ?? []) as $appt)
                            @php
                                $job = $appt->job ?? ($appt->tradeJob ?? null); // depending on your model shape
                                $title = $job?->summary ?? 'Job';
                                $start = $appt->start_at?->timezone($tz)->format('g:i A') ?? '';
                                $end = $appt->end_at?->timezone($tz)->format('g:i A') ?? '';
                                $status = ucfirst((string) ($appt->status ?? 'scheduled'));
                                $techCount = $appt->assignments?->count() ?? 0;
                            @endphp

                            <a href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $appt->id]) }}"
                                class="block rounded-lg border border-border-default px-4 py-3 hover:bg-surface-accent/30 transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-medium text-text-base">{{ $title }}</div>
                                        <div class="text-xs text-text-subtle mt-1">{{ $start }} @if ($end)
                                                – {{ $end }}
                                            @endif · {{ $techCount }} tech(s)</div>
                                    </div>
                                    <span class="oh-pill oh-pill--muted text-[11px]">{{ $status }}</span>
                                </div>
                            </a>
                        @empty
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No appointments today.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Techs On Site</h2>
                        <a class="text-xs text-text-subtle hover:text-text-base"
                            href="{{ route('tenant.trades.field.today', ['tenant' => $tenantKey]) }}">
                            Field overview →
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse(($techsOnSite ?? []) as $row)
                            @php
                                $name = $row->user?->name ?? 'Tech';
                                $appt = $row->appointment ?? null;
                                $job = $appt?->job ?? ($appt?->tradeJob ?? null);
                            @endphp

                            <div
                                class="flex items-center justify-between rounded-lg border border-border-default px-4 py-3">
                                <div>
                                    <div class="text-sm font-medium text-text-base">{{ $name }}</div>
                                    <div class="text-xs text-text-subtle mt-1">{{ $job?->summary ?? 'Job' }}</div>
                                </div>
                                <span class="oh-pill oh-pill--muted text-[11px]">On site</span>
                            </div>
                        @empty
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No one is on site right now.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- RIGHT: Pipeline --}}
            <div class="space-y-6">
                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Unscheduled Jobs</h2>
                        <a class="text-xs text-text-subtle hover:text-text-base"
                            href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}">
                            View jobs →
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse(($unscheduledJobs ?? []) as $job)
                            <div
                                class="flex items-center justify-between rounded-lg border border-border-default px-4 py-3">
                                <div>
                                    <div class="text-sm font-medium text-text-base">{{ $job->summary }}</div>
                                    <div class="text-xs text-text-subtle mt-1">
                                        {{ $job->client?->firstName ?? '' }} {{ $job->client?->lastName ?? '' }}
                                    </div>
                                </div>
                                <a class="oh-btn oh-btn--sm"
                                    href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                                    Schedule
                                </a>
                            </div>
                        @empty
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No unscheduled jobs.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Quotes Awaiting Acceptance</h2>
                        <a class="text-xs text-text-subtle hover:text-text-base"
                            href="{{ route('tenant.trades.quotes.index', ['tenant' => $tenantKey]) }}">
                            View quotes →
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse(($pendingQuotes ?? []) as $quote)
                            <a href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}"
                                class="block rounded-lg border border-border-default px-4 py-3 hover:bg-surface-accent/30 transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-text-base">Quote #{{ $quote->id }}</div>
                                        <div class="text-xs text-text-subtle mt-1">
                                            {{ $quote->client?->firstName ?? '' }} {{ $quote->client?->lastName ?? '' }}
                                            @if ($quote->expires_at)
                                                · Expires {{ $quote->expires_at->format('M j') }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($quote->status) }}</span>
                                </div>
                            </a>
                        @empty
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No pending quotes.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
