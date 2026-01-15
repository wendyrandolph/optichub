@extends('layouts.trades')

@section('title', 'Field Today')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $clockedInAt = $openShift?->clock_in_at;
        $tz = $timezone ?? ($tenant->timezone ?? config('app.timezone'));
        $todayLabel = now($tz)->format('D, M j');
        $tab = $tab ?? request('tab', 'jobs');
        $jobsUrl = route('tenant.trades.field.today', ['tenant' => $tenantKey, 'tab' => 'jobs']);
        $timeUrl = route('tenant.trades.field.today', ['tenant' => $tenantKey, 'tab' => 'time']);
    @endphp
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Field Today</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-text-subtle">
                    <span>{{ $todayLabel }}</span>
                    <span class="h-1 w-1 rounded-full bg-border-default"></span>
                    <span class="oh-pill {{ $openShift ? 'oh-pill--success' : 'oh-pill--muted' }} text-[11px]">
                        {{ $openShift ? 'Clocked in' : 'Not clocked in' }}
                    </span>
                </div>
                <p class="text-sm text-text-subtle mt-2">Your assigned appointments for today.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card p-1">
                    <button type="button" data-tab-button="jobs"
                        class="inline-flex items-center rounded-full px-3 py-1.5 text-xs transition {{ $tab === 'jobs' ? 'bg-white font-semibold text-text-base shadow-sm' : 'text-text-subtle hover:text-text-base' }}">
                        My Jobs
                    </button>
                    <button type="button" data-tab-button="time"
                        class="inline-flex items-center rounded-full px-3 py-1.5 text-xs transition {{ $tab === 'time' ? 'bg-white font-semibold text-text-base shadow-sm' : 'text-text-subtle hover:text-text-base' }}">
                        Time Clock
                    </button>
                    <button type="button" data-tab-button="pto"
                        class="inline-flex items-center rounded-full px-3 py-1.5 text-xs transition {{ $tab === 'pto' ? 'bg-white font-semibold text-text-base shadow-sm' : 'text-text-subtle hover:text-text-base' }}">
                        PTO Request
                    </button>
                </div>
            </div>
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-text-base">Today’s schedule</div>
                    <div class="text-xs text-text-subtle mt-1">
                        @if ($openShift && $clockedInAt)
                            Clocked in at {{ $clockedInAt->timezone($tz)->format('g:i A') }}.
                        @else
                            Not clocked in.
                        @endif
                    </div>
                </div>
            </div>

            <div data-tab-panel="time" class="{{ $tab === 'time' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                        <div class="font-semibold text-text-base">Shift controls</div>
                        <div class="text-xs text-text-subtle mt-1">
                            @if ($openShift)
                                You are clocked in.
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
                                <form method="POST"
                                    action="{{ route('tenant.trades.field.clock-in', ['tenant' => $tenantKey]) }}">
                                    @csrf
                                    <button class="oh-btn oh-btn--primary" type="submit">Clock in</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                        <div class="font-semibold text-text-base">Recent shifts</div>
                        @if (($shiftHistory ?? collect())->isEmpty())
                            <div class="text-xs text-text-subtle mt-2">No recent shifts yet.</div>
                        @else
                            <ul class="mt-2 space-y-2 text-xs text-text-subtle">
                                @foreach ($shiftHistory as $shift)
                                    <li class="flex items-center justify-between">
                                        <span>
                                            {{ $shift->clock_in_at?->timezone($tz)->format('D, M j g:i A') }}
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

            <div data-tab-panel="jobs" class="{{ $tab === 'jobs' ? '' : 'hidden' }}">
                <div class="text-sm text-text-subtle">Your assigned appointments for today.</div>

                <div class="hidden md:block">
                    <div class="overflow-hidden rounded-xl border border-border-default/70">
                        <table class="min-w-full text-sm">
                            <thead class="bg-surface-muted/60 text-text-subtle">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Job</th>
                                    <th class="px-3 py-2 text-left font-medium">Location</th>
                                    <th class="px-3 py-2 text-left font-medium">Time</th>
                                    <th class="px-3 py-2 text-left font-medium">Status</th>
                                    <th class="px-3 py-2 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($appointments as $appointment)
                                    @php
                                        $job = $appointment->job;
                                        $summary = $job?->summary ?? 'Job';
                                        $clientName =
                                            trim(
                                                ($job?->client?->firstName ?? '') .
                                                    ' ' .
                                                    ($job?->client?->lastName ?? ''),
                                            ) ?:
                                            'Client';
                                        $location = $job?->serviceLocation;
                                        $address = collect([
                                            $location?->address_line1,
                                            $location?->address_line2,
                                            trim(
                                                implode(
                                                    ' ',
                                                    array_filter([
                                                        $location?->city,
                                                        $location?->state,
                                                        $location?->postal_code,
                                                    ]),
                                                ),
                                            ),
                                        ])
                                            ->filter()
                                            ->implode(', ');
                                        $statusLabel = ucfirst($appointment->status ?? 'scheduled');
                                        $isTimerForThis =
                                            $openTimer && (int) $openTimer->appointment_id === (int) $appointment->id;
                                        $hasOtherTimer = $openTimer && !$isTimerForThis;
                                        $canStart = (bool) $openShift && !$hasOtherTimer;
                                        $techCount = (int) ($appointment->assignments_count ?? 0);
                                    @endphp
                                    <tr class="border-t border-border-default/60">
                                        <td class="px-3 py-2">
                                            <div class="font-semibold text-text-base">{{ $summary }}</div>
                                            <div class="text-xs text-text-subtle">{{ $clientName }}</div>
                                            @if ($techCount > 0)
                                                <div class="text-[11px] text-text-subtle mt-1">{{ $techCount }} tech(s)
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-xs text-text-subtle">
                                            {{ $address ?: 'No address listed' }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-text-subtle">
                                            {{ $appointment->start_at->timezone($tz)->format('g:i A') }} –
                                            {{ $appointment->end_at->timezone($tz)->format('g:i A') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="inline-flex flex-row items-center gap-5">
                                                <a class="text-xs text-text-primary hover:text-text-base oh-btn oh-btn--ghost"
                                                    href="{{ route('tenant.trades.field.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                                    View
                                                </a>

                                                @if ($isTimerForThis)
                                                    <form method="POST"
                                                        action="{{ route('tenant.trades.field.stop', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                                        @csrf
                                                        <button class="oh-btn oh-btn--danger text-xs"
                                                            type="submit">Stop</button>
                                                    </form>
                                                @else
                                                    <form method="POST"
                                                        action="{{ route('tenant.trades.field.start', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                                        @csrf
                                                        <button class="oh-btn oh-btn--primary text-xs" type="submit"
                                                            @disabled(!$canStart)>
                                                            Start
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (!$openShift)
                                                    <span class="text-[11px] text-text-subtle">Clock in to start</span>
                                                @elseif ($hasOtherTimer)
                                                    <span class="text-[11px] text-text-subtle">Finish current job
                                                        first</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-border-default/60">
                                        <td class="px-3 py-3 text-text-subtle" colspan="5">
                                            No jobs assigned for today. Check My Jobs or request PTO if you’re off.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4 md:hidden">
                    @forelse ($appointments as $appointment)
                        @php
                            $job = $appointment->job;
                            $summary = $job?->summary ?? 'Job';
                            $clientName =
                                trim(($job?->client?->firstName ?? '') . ' ' . ($job?->client?->lastName ?? '')) ?:
                                'Client';
                            $location = $job?->serviceLocation;
                            $address = collect([
                                $location?->address_line1,
                                $location?->address_line2,
                                trim(
                                    implode(
                                        ' ',
                                        array_filter([$location?->city, $location?->state, $location?->postal_code]),
                                    ),
                                ),
                            ])
                                ->filter()
                                ->implode(', ');
                            $statusLabel = ucfirst($appointment->status ?? 'scheduled');
                            $isTimerForThis = $openTimer && (int) $openTimer->appointment_id === (int) $appointment->id;
                            $hasOtherTimer = $openTimer && !$isTimerForThis;
                            $canStart = (bool) $openShift && !$hasOtherTimer;
                            $techCount = (int) ($appointment->assignments_count ?? 0);
                        @endphp
                        <div class="rounded-xl border border-border-default bg-surface-accent/40 px-4 py-4 space-y-3">
                            <div>
                                <div class="text-sm font-semibold text-text-base">{{ $summary }}</div>
                                <div class="text-xs text-text-subtle mt-1">{{ $clientName }}</div>
                                <div class="text-xs text-text-subtle mt-1">{{ $address ?: 'No address listed' }}</div>
                                <div class="text-xs text-text-subtle mt-2">
                                    {{ $appointment->start_at->timezone($tz)->format('g:i A') }} –
                                    {{ $appointment->end_at->timezone($tz)->format('g:i A') }}
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                                @if ($techCount > 0)
                                    <span class="oh-pill oh-pill--muted text-[11px]">{{ $techCount }} tech(s)</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($isTimerForThis)
                                    <form method="POST"
                                        action="{{ route('tenant.trades.field.stop', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                        @csrf
                                        <button class="oh-btn oh-btn--danger text-xs" type="submit">Stop</button>
                                    </form>
                                @else
                                    <form method="POST"
                                        action="{{ route('tenant.trades.field.start', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                        @csrf
                                        <button class="oh-btn oh-btn--primary text-xs" type="submit"
                                            @disabled(!$canStart)>
                                            Start
                                        </button>
                                    </form>
                                @endif
                                <a class="oh-btn text-xs"
                                    href="{{ route('tenant.trades.field.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                    View
                                </a>
                            </div>
                            @if (!$openShift)
                                <div class="text-[11px] text-text-subtle">Clock in to start.</div>
                            @elseif ($hasOtherTimer)
                                <div class="text-[11px] text-text-subtle">Finish current job first.</div>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                            No jobs assigned for today. Check My Jobs or request PTO if you’re off.
                        </div>
                    @endforelse
                </div>
            </div>

            <div data-tab-panel="pto" class="{{ $tab === 'pto' ? '' : 'hidden' }}">
                <div
                    class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                    Request time off and view your balances.
                </div>
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-border-default bg-white px-4 py-3">
                        <div class="text-sm font-semibold text-text-base">PTO balances</div>
                        @if (($ptoTypes ?? collect())->isEmpty())
                            <div class="text-xs text-text-subtle mt-2">PTO types are not configured yet.</div>
                        @else
                            <div class="mt-3 space-y-2 text-xs text-text-subtle">
                                @foreach ($ptoTypes as $type)
                                    @php $balance = $ptoBalances[$type->id]->balance_hours ?? 0; @endphp
                                    <div class="flex items-center justify-between">
                                        <span>{{ $type->name }}</span>
                                        <span class="text-text-base font-semibold">{{ number_format($balance, 2) }}
                                            hrs</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-border-default bg-white px-4 py-3">
                        <div class="text-sm font-semibold text-text-base">Request PTO</div>
                        <form method="POST"
                            action="{{ route('tenant.trades.field.pto-requests.store', ['tenant' => $tenantKey]) }}"
                            class="mt-3 space-y-3 text-sm">
                            @csrf
                            <div>
                                <label class="text-xs text-text-subtle">Type</label>
                                <select name="trade_pto_type_id" class="oh-select h-10 w-full">
                                    @foreach ($ptoTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-text-subtle">Start date</label>
                                    <input type="date" name="start_date" class="oh-input h-10 w-full" required>
                                </div>
                                <div>
                                    <label class="text-xs text-text-subtle">End date</label>
                                    <input type="date" name="end_date" class="oh-input h-10 w-full" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-text-subtle">Hours requested</label>
                                <input type="number" name="hours_requested" class="oh-input h-10 w-full" step="0.25"
                                    min="0.5" required>
                            </div>
                            <div>
                                <label class="text-xs text-text-subtle">Notes (optional)</label>
                                <input type="text" name="notes" class="oh-input h-10 w-full"
                                    placeholder="Add a note">
                            </div>
                            <div class="flex justify-end">
                                <button class="oh-btn oh-btn--primary" type="submit">Submit request</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-border-default bg-white px-4 py-3">
                    <div class="text-sm font-semibold text-text-base">Recent PTO requests</div>
                    @forelse (($ptoRequests ?? collect()) as $request)
                        <div
                            class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-text-subtle">
                            <div>
                                <div class="text-sm font-semibold text-text-base">{{ $request->type?->name }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $request->start_date->format('M j') }} to {{ $request->end_date->format('M j') }}
                                    · {{ $request->hours_requested }} hrs
                                </div>
                            </div>
                            <span
                                class="text-xs uppercase tracking-wide text-text-subtle">{{ ucfirst($request->status) }}</span>
                        </div>
                    @empty
                        <div class="mt-2 text-xs text-text-subtle">No PTO requests yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('[data-tab-button]');
            const panels = document.querySelectorAll('[data-tab-panel]');
            if (!buttons.length || !panels.length) return;

            const setActive = (tab) => {
                buttons.forEach(btn => {
                    const isActive = btn.dataset.tabButton === tab;
                    btn.classList.toggle('bg-white', isActive);
                    btn.classList.toggle('font-semibold', isActive);
                    btn.classList.toggle('text-text-base', isActive);
                    btn.classList.toggle('shadow-sm', isActive);
                });
                panels.forEach(panel => {
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
                });

                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url.toString());
            };

            buttons.forEach(btn => {
                btn.addEventListener('click', () => setActive(btn.dataset.tabButton));
            });

            const initial = new URL(window.location.href).searchParams.get('tab') || '{{ $tab }}';
            setActive(initial);
        });
    </script>
@endsection
