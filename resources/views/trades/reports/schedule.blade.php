@extends('layouts.trades')

@section('title', 'Schedule Report')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Insights</p>
                <h1 class="text-2xl font-semibold text-text-base">Schedule</h1>
                <p class="text-sm text-text-subtle mt-1">Appointments volume and completion over a date range.</p>
            </div>

            <a class="oh-btn" href="{{ route('tenant.trades.dashboard', ['tenant' => $tenantKey]) }}">
                Back to overview
            </a>
        </div>

        {{-- Date range filter --}}
        <form method="GET" action="{{ route('tenant.trades.reports.schedule', ['tenant' => $tenantKey]) }}"
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
                <a class="oh-btn" href="{{ route('tenant.trades.reports.schedule', ['tenant' => $tenantKey]) }}">Reset</a>
            </div>
        </form>

        {{-- Summary tiles --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Appointments</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $totals['appointments_total'] ?? 0 }}</div>
            </div>
            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Completed</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $totals['appointments_completed'] ?? 0 }}</div>
            </div>
            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Canceled</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $totals['appointments_canceled'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Per-day breakdown --}}
        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Appointments by day</div>

            @forelse(($perDay ?? []) as $row)
                <div
                    class="flex items-center justify-between text-sm border-b border-border-default/60 py-2 last:border-b-0">
                    <span
                        class="text-text-subtle">{{ \Illuminate\Support\Carbon::parse($row->day)->format('M j, Y') }}</span>
                    <span class="text-text-base font-medium">{{ $row->count }}</span>
                </div>
            @empty
                <div class="text-sm text-text-subtle">No appointment data for this range.</div>
            @endforelse
        </div>

        {{-- Upcoming --}}
        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Upcoming appointments</div>

            @forelse(($upcoming ?? []) as $appt)
                <div class="flex items-center justify-between gap-3 border-b border-border-default/60 py-2 last:border-b-0">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-text-base truncate">{{ $appt->job?->summary ?? 'Job' }}</div>
                        <div class="text-xs text-text-subtle mt-1">
                            {{ $appt->start_at?->timezone($tz)->format('M j, g:i A') }}
                            @if ($appt->end_at)
                                – {{ $appt->end_at->timezone($tz)->format('g:i A') }}
                            @endif
                        </div>
                    </div>
                    <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($appt->status ?? 'scheduled') }}</span>
                </div>
            @empty
                <div class="text-sm text-text-subtle">No upcoming appointments.</div>
            @endforelse
        </div>
    </div>
@endsection
