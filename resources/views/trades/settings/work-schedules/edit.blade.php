@extends('layouts.trades')

@section('title', 'Trades Settings — Work Schedule')

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
        $userLabel =
            trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?:
            $user->email ??
            $user->email;
        $cadence = old('cadence', $schedule?->cadence ?? 'weekly');
        $timezone = old('timezone', $schedule?->timezone ?? '');
        $startsOn = old('starts_on', optional($schedule?->starts_on)->toDateString());
        $isActive = old('is_active', $schedule?->is_active ?? true);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <a href="{{ route('tenant.trades.settings.time.work-schedules', ['tenant' => $tenantKey]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                Back to work schedules
            </a>
            <h1 class="text-2xl font-semibold text-text-base mt-2">Schedule for {{ $userLabel }}</h1>
            <p class="text-sm text-text-subtle mt-1">Set recurring availability blocks.</p>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ route('tenant.trades.settings.time.work-schedules.user', ['tenant' => $tenantKey, 'user' => $user->id]) }}"
            class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="oh-card p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base">Cadence</label>
                        <select name="cadence" class="oh-select h-10">
                            <option value="weekly" @selected($cadence === 'weekly')>Weekly</option>
                            <option value="biweekly" @selected($cadence === 'biweekly')>Biweekly</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base">Timezone</label>
                        <input type="text" name="timezone" class="oh-input h-10" placeholder="Use tenant timezone"
                            value="{{ $timezone }}">
                        <p class="text-xs text-text-subtle mt-1">Leave blank to use tenant timezone.</p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base">Starts on</label>
                        <input type="date" name="starts_on" class="oh-input h-10" value="{{ $startsOn }}">
                        <p class="text-xs text-text-subtle mt-1">Used for week A/B alignment.</p>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-border-default"
                        @checked($isActive)>
                    Active schedule
                </label>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ([0 => 'Week A', 1 => 'Week B'] as $weekIndex => $weekLabel)
                    <div class="oh-card p-5 space-y-3 {{ $cadence === 'weekly' && $weekIndex === 1 ? 'opacity-50' : '' }}">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-text-base">{{ $weekLabel }}</div>
                            @if ($cadence === 'weekly' && $weekIndex === 1)
                                <span class="text-[11px] text-text-subtle">Ignored for weekly cadence</span>
                            @endif
                        </div>
                        <div class="space-y-3">
                            @foreach ($days as $dayIndex => $dayLabel)
                                @php
                                    $block = $blocks[$weekIndex][$dayIndex] ?? ['start_time' => null, 'end_time' => null];
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                    <div class="md:col-span-4 text-sm font-medium text-text-base">{{ $dayLabel }}</div>
                                    <div class="md:col-span-4">
                                        <input type="time"
                                            name="blocks[{{ $weekIndex }}][{{ $dayIndex }}][start_time]"
                                            value="{{ old("blocks.{$weekIndex}.{$dayIndex}.start_time", $block['start_time']) }}"
                                            class="oh-input h-9 w-full">
                                    </div>
                                    <div class="md:col-span-4">
                                        <input type="time"
                                            name="blocks[{{ $weekIndex }}][{{ $dayIndex }}][end_time]"
                                            value="{{ old("blocks.{$weekIndex}.{$dayIndex}.end_time", $block['end_time']) }}"
                                            class="oh-input h-9 w-full">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button class="oh-btn oh-btn--primary" type="submit">Save schedule</button>
            </div>
        </form>
    </div>
@endsection
