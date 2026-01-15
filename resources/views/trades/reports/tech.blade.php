@extends('layouts.trades')

@section('title', 'Tech Report')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');

        $formatSeconds = function (?int $seconds): string {
            $seconds = max(0, (int) ($seconds ?? 0));
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);
            return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
        };

        $exportParams = array_filter([
            'tenant' => $tenantKey,
            'from' => optional($from ?? null)?->toDateString() ?? request('from'),
            'to' => optional($to ?? null)?->toDateString() ?? request('to'),
            'export' => 'payroll',
        ]);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Insights</p>
                <h1 class="text-2xl font-semibold text-text-base">Tech</h1>
                <p class="text-sm text-text-subtle mt-1">On-site activity and job time totals by tech.</p>
            </div>

            <div class="flex items-center gap-2">
            <a class="oh-btn" href="{{ route('tenant.trades.dashboard', ['tenant' => $tenantKey]) }}">
                Back to overview
            </a>
                <a class="oh-btn" href="{{ route('tenant.trades.reports.tech', $exportParams) }}">
                    Export payroll CSV
                </a>
            </div>
        </div>

        {{-- Date range filter --}}
        <form method="GET" action="{{ route('tenant.trades.reports.tech', ['tenant' => $tenantKey]) }}"
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
                <a class="oh-btn" href="{{ route('tenant.trades.reports.tech', ['tenant' => $tenantKey]) }}">Reset</a>
            </div>
        </form>

        @php
            $totalAssignments = collect($presence ?? [])->sum('assignments');
            $totalSeconds = (int) collect($timerTotals ?? [])->sum('total_seconds');
            $totalCompletedSeconds = (int) collect($timerTotals ?? [])->sum('completed_seconds');
            $totalRunningSeconds = (int) collect($timerTotals ?? [])->sum('running_seconds');
        @endphp

        {{-- Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Total assignments</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $totalAssignments }}</div>
                <div class="text-xs text-text-subtle mt-1">
                    {{ optional($from ?? null)?->format('M j') }} – {{ optional($to ?? null)?->format('M j') }}
                </div>
            </div>

            <div class="oh-card p-5">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Total job time</div>
                <div class="text-2xl font-semibold text-text-base mt-1">{{ $formatSeconds($totalSeconds) }}</div>
                <div class="text-xs text-text-subtle mt-1">
                    Completed {{ $formatSeconds($totalCompletedSeconds) }} · Running (on-site)
                    {{ $formatSeconds($totalRunningSeconds) }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            {{-- Today overview --}}
            <div class="oh-card p-5 space-y-4">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-text-base">Today’s tech activity</div>
                        <div class="text-xs text-text-subtle">Clock in/out and job start/finish times.</div>
                    </div>
                </div>

                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-text-subtle border-b border-border-default/60">
                            <tr>
                                <th class="text-left py-2">Tech</th>
                                <th class="text-left py-2">Clock in</th>
                                <th class="text-left py-2">Last job start</th>
                                <th class="text-left py-2">Last job end</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-right py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-default/60">
                            @forelse(($techOverview ?? []) as $row)
                                @php
                                    $user = $row['user'] ?? null;
                                    $shift = $row['shift'] ?? null;
                                    $name =
                                        $user?->name ??
                                        trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')) ?:
                                        $user?->email ?? 'Tech';
                                    $clockIn = $shift?->clock_in_at;
                                    $lastStart = $row['last_timer_start'] ?? null;
                                    $lastEnd = $row['last_timer_end'] ?? null;
                                    $isOpen = $shift && $shift->clock_out_at === null;
                                    $statusLabel = $shift ? ($isOpen ? 'Clocked in' : 'Clocked out') : 'No shift';
                                @endphp
                                <tr>
                                    <td class="py-2">
                                        <div class="font-medium text-text-base">{{ $name }}</div>
                                    </td>
                                    <td class="py-2 text-text-subtle">
                                        {{ $clockIn ? \Illuminate\Support\Carbon::parse($clockIn)->timezone($tz)->format('g:ia') : '—' }}
                                    </td>
                                    <td class="py-2 text-text-subtle">
                                        {{ $lastStart ? \Illuminate\Support\Carbon::parse($lastStart)->timezone($tz)->format('g:ia') : '—' }}
                                    </td>
                                    <td class="py-2 text-text-subtle">
                                        {{ $lastEnd ? \Illuminate\Support\Carbon::parse($lastEnd)->timezone($tz)->format('g:ia') : '—' }}
                                    </td>
                                    <td class="py-2">
                                        <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="py-2 text-right">
                                        @if (auth()->check() && in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true))
                                            <form method="POST"
                                                action="{{ route('tenant.trades.reports.tech.force-clockout', ['tenant' => $tenantKey, 'user' => $user->id]) }}"
                                                onsubmit="return confirm('Clock this tech out and end any running job timers?');"
                                                class="inline-flex">
                                                @csrf
                                                <button class="oh-btn" type="submit">Force clock out</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-sm text-text-subtle">No tech activity today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:hidden">
                    @forelse(($techOverview ?? []) as $row)
                        @php
                            $user = $row['user'] ?? null;
                            $shift = $row['shift'] ?? null;
                            $timers = $row['timers'] ?? collect();
                            $name =
                                $user?->name ??
                                trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')) ?:
                                $user?->email ?? 'Tech';
                            $clockIn = $shift?->clock_in_at;
                            $clockOut = $shift?->clock_out_at;
                            $isOpen = $shift && $shift->clock_out_at === null;
                            $statusLabel = $shift ? ($isOpen ? 'Clocked in' : 'Clocked out') : 'No shift';
                        @endphp
                        <div class="border border-border-default/60 rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-text-base">{{ $name }}</div>
                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                            </div>
                            <div class="text-xs text-text-subtle">
                                Clock in: {{ $clockIn ? \Illuminate\Support\Carbon::parse($clockIn)->timezone($tz)->format('g:ia') : '—' }}
                                @if ($clockOut)
                                    · Out: {{ \Illuminate\Support\Carbon::parse($clockOut)->timezone($tz)->format('g:ia') }}
                                @endif
                            </div>
                            <div class="space-y-1 text-xs text-text-subtle">
                                @forelse($timers as $timer)
                                    <div>
                                        Job: {{ \Illuminate\Support\Carbon::parse($timer->started_at)->timezone($tz)->format('g:ia') }}
                                        @if ($timer->ended_at)
                                            → {{ \Illuminate\Support\Carbon::parse($timer->ended_at)->timezone($tz)->format('g:ia') }}
                                        @else
                                            → In progress
                                        @endif
                                    </div>
                                @empty
                                    <div>No job timers today.</div>
                                @endforelse
                            </div>
                            @if (auth()->check() && in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true))
                                <form method="POST"
                                    action="{{ route('tenant.trades.reports.tech.force-clockout', ['tenant' => $tenantKey, 'user' => $user->id]) }}"
                                    onsubmit="return confirm('Clock this tech out and end any running job timers?');">
                                    @csrf
                                    <button class="oh-btn w-full" type="submit">Force clock out</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No tech activity today.</div>
                    @endforelse
                </div>
            </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Utilization --}}
            <div class="oh-card p-5 space-y-3 lg:col-span-2">
                <div class="text-sm font-semibold text-text-base">Utilization (scheduled vs worked)</div>
                <div class="text-xs text-text-subtle">Scheduled time from appointments vs job timers and PTO shifts.</div>

                @forelse(($utilization ?? []) as $row)
                    @php
                        $name =
                            $row['user']?->name ??
                            trim(($row['user']?->first_name ?? '') . ' ' . ($row['user']?->last_name ?? '')) ?:
                            $row['user']?->email ?? 'Tech';
                        $scheduled = (int) ($row['scheduled_seconds'] ?? 0);
                        $worked = (int) ($row['worked_seconds'] ?? 0);
                        $pto = (int) ($row['pto_seconds'] ?? 0);
                        $overtime = (int) ($row['overtime_seconds'] ?? 0);
                    @endphp

                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-border-default/60 py-2 last:border-b-0">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-text-base truncate">{{ $name }}</div>
                            <div class="text-xs text-text-subtle mt-1">Scheduled {{ $formatSeconds($scheduled) }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[11px]">
                            <span class="oh-pill oh-pill--muted">Worked {{ $formatSeconds($worked) }}</span>
                            <span class="oh-pill oh-pill--muted">PTO {{ $formatSeconds($pto) }}</span>
                            @if ($overtime > 0)
                                <span class="oh-pill oh-pill--warning">Overtime {{ $formatSeconds($overtime) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No utilization data in this range.</div>
                @endforelse
            </div>

            {{-- Presence / assignment volume --}}
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Assignments by tech</div>

                @forelse(($presence ?? []) as $row)
                    @php
                        $name =
                            $row->user?->name ??
                            trim(($row->user?->first_name ?? '') . ' ' . ($row->user?->last_name ?? '')) ?:
                            $row->user?->email ?? 'Tech';
                    @endphp

                    <div
                        class="flex items-center justify-between gap-3 border-b border-border-default/60 py-2 last:border-b-0">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-text-base truncate">{{ $name }}</div>
                            <div class="text-xs text-text-subtle mt-1">User #{{ $row->user_id }}</div>
                        </div>
                        <span class="oh-pill oh-pill--muted text-[11px]">{{ (int) $row->assignments }} assigned</span>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No assignment data in this range.</div>
                @endforelse
            </div>

            {{-- Timer totals --}}
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Job time by tech</div>

                @forelse(($timerTotals ?? []) as $row)
                    @php
                        $name =
                            $row->user?->name ??
                            trim(($row->user?->first_name ?? '') . ' ' . ($row->user?->last_name ?? '')) ?:
                            $row->user?->email ?? 'Tech';
                        $total = (int) ($row->total_seconds ?? 0);
                        $completed = (int) ($row->completed_seconds ?? 0);
                        $running = (int) ($row->running_seconds ?? 0);
                    @endphp

                    <div
                        class="flex items-center justify-between gap-3 border-b border-border-default/60 py-2 last:border-b-0">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-text-base truncate">{{ $name }}</div>
                            <div class="text-xs text-text-subtle mt-1">User #{{ $row->user_id }}</div>
                        </div>
                        <span class="oh-pill oh-pill--muted text-[11px]">{{ $formatSeconds($total) }}</span>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No timer data in this range.</div>
                @endforelse
            </div>
        </div>

        <div class="oh-card p-5 space-y-2">
            <div class="text-sm font-semibold text-text-base">Notes</div>
            <div class="text-sm text-text-subtle">
                Timer totals include completed and currently running timers. Running timers stop counting once a tech is
                marked done or clocked off-site.
            </div>
        </div>
    </div>
@endsection
