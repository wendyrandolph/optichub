@extends('layouts.trades')

@section('title', 'Field Time')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $tz = $timezone ?? ($tenant->timezone ?? config('app.timezone'));
    @endphp
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Time Clock</h1>
                <p class="text-sm text-text-subtle mt-1">Clock in or out and review your recent shifts.</p>
            </div>
            <a class="oh-btn" href="{{ route('tenant.trades.field.today', ['tenant' => $tenantKey]) }}">
                Back to today
            </a>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error_message') }}
            </div>
        @endif

        <div class="oh-card p-5 space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                    <div class="font-semibold text-text-base">Shift controls</div>
                    <div class="text-xs text-text-subtle mt-1">
                        @if ($openShift)
                            Clocked in at {{ $openShift->clock_in_at?->timezone($tz)->format('g:i A') }}.
                        @else
                            You are currently clocked out.
                        @endif
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        @if ($openShift)
                            <form method="POST"
                                action="{{ route('tenant.trades.field.clock-out', ['tenant' => $tenantKey]) }}">
                                @csrf
                                <button class="oh-btn oh-btn--danger" type="submit">Clock out</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('tenant.trades.field.clock-in', ['tenant' => $tenantKey]) }}">
                                @csrf
                                <button class="oh-btn oh-btn--primary" type="submit">Clock in</button>
                            </form>
                        @endif
                        @if ($openTimer)
                            <a class="oh-btn"
                                href="{{ route('tenant.trades.field.show', ['tenant' => $tenantKey, 'appointment' => $openTimer->appointment_id]) }}">
                                View active job
                            </a>
                        @endif
                    </div>
                    @if ($openTimer)
                        <div class="mt-2 text-xs text-text-subtle">
                            Active job started at {{ $openTimer->started_at?->timezone($tz)->format('g:i A') }}.
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                    <div class="font-semibold text-text-base">Recent shifts</div>
                    @if (($shiftHistory ?? collect())->isEmpty())
                        <div class="text-xs text-text-subtle mt-2">No recent shifts yet.</div>
                    @else
                        <ul class="mt-2 space-y-2 text-xs text-text-subtle">
                            @foreach ($shiftHistory as $shift)
                                @php
                                    $typeLabel = $shift->shift_type === 'pto' ? 'PTO' : 'Work';
                                @endphp
                                <li class="flex items-center justify-between">
                                    <span>
                                        {{ $typeLabel }} · {{ $shift->clock_in_at?->timezone($tz)->format('D, M j g:i A') }}
                                    </span>
                                    <span>
                                        {{ $shift->clock_out_at?->timezone($tz)->format('g:i A') ?? 'In progress' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
