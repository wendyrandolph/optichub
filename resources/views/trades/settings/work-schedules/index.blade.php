@extends('layouts.trades')

@section('title', 'Trades Settings — Work Schedules')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $timezone = $tenant->timezone ?? config('app.timezone');
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Work schedules</h1>
            <p class="text-sm text-text-subtle mt-1">Define company hours and team availability for dispatching.</p>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="oh-card p-5 space-y-4 lg:col-span-2">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Company hours</h2>
                    <p class="text-xs text-text-subtle mt-1">Weekly business hours shown in {{ $timezone }}.</p>
                </div>
                <form method="POST"
                    action="{{ route('tenant.trades.settings.time.work-schedules.company', ['tenant' => $tenantKey]) }}"
                    class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-3">
                        @foreach ($days as $dayIndex => $dayLabel)
                            @php
                                $hour = $weekHours[$dayIndex] ?? null;
                                $isClosed = $hour['is_closed'] ?? true;
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                <div class="md:col-span-3 text-sm font-medium text-text-base">{{ $dayLabel }}</div>
                                <div class="md:col-span-3">
                                    <input type="time" name="hours[{{ $dayIndex }}][start_time]"
                                        value="{{ old("hours.{$dayIndex}.start_time", $hour['start_time'] ?? '') }}"
                                        class="oh-input h-9 w-full" @disabled($isClosed)>
                                </div>
                                <div class="md:col-span-3">
                                    <input type="time" name="hours[{{ $dayIndex }}][end_time]"
                                        value="{{ old("hours.{$dayIndex}.end_time", $hour['end_time'] ?? '') }}"
                                        class="oh-input h-9 w-full" @disabled($isClosed)>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
                                        <input type="checkbox" name="hours[{{ $dayIndex }}][is_closed]" value="1"
                                            class="rounded border-border-default"
                                            @checked(old("hours.{$dayIndex}.is_closed", $isClosed))>
                                        Closed
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button class="oh-btn oh-btn--primary" type="submit">Save company hours</button>
                    </div>
                </form>
            </div>

            <div class="oh-card p-5 space-y-3">
                <h2 class="text-base font-semibold text-text-base">Schedule overview</h2>
                <p class="text-sm text-text-subtle">View appointments alongside your staffing plan.</p>
                <a class="oh-btn w-full"
                    href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey, 'view' => 'calendar']) }}">
                    Open schedule calendar
                </a>
            </div>
        </div>

        <div class="oh-card p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Team schedules</h2>
                    <p class="text-xs text-text-subtle mt-1">Set repeating availability per team member.</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-border-default/60">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted text-text-subtle">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Team member</th>
                            <th class="px-4 py-3 text-left font-semibold">Cadence</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        @forelse ($users as $user)
                            @php
                                $label =
                                    trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?:
                                    $user->email ??
                                    $user->email;
                                $schedule = $schedules[$user->id] ?? null;
                                $cadenceLabel = $schedule?->cadence ? ucfirst($schedule->cadence) : '—';
                                $statusLabel = $schedule?->is_active ? 'Active' : 'Inactive';
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-text-base">{{ $label }}</div>
                                    <div class="text-xs text-text-subtle">{{ $user->role }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-text-subtle">{{ $cadenceLabel }}</td>
                                <td class="px-4 py-3">
                                    <span class="oh-pill oh-pill--muted text-[11px]">{{ $schedule ? $statusLabel : 'No schedule' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a class="oh-btn text-xs"
                                        href="{{ route('tenant.trades.settings.time.work-schedules.edit', ['tenant' => $tenantKey, 'user' => $user->id]) }}">
                                        Edit schedule
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-text-subtle">
                                    No team members found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
