@extends('layouts.trades')

@section('title', 'Trades Settings — Time & Shifts')

@section('trades-content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Time & Shifts</h1>
            <p class="text-sm text-text-subtle mt-1">Timezone, PTO policy, holidays, and overtime tracking.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="oh-card p-6 space-y-4">
                @php
                    $tenantTz = old('timezone', $tenant->timezone ?? '');
                    $defaultTz = config('app.timezone');
                @endphp
                <h2 class="text-base font-semibold text-text-base">Timezone</h2>
                <p class="text-sm text-text-subtle">Used for shift times, schedules, and reports.</p>
                <p class="text-sm text-text-subtle">Current timezone: {{ $tenantTz ?: $defaultTz }}</p>
                <form method="POST" action="{{ route('tenant.trades.settings.time.update', ['tenant' => $tenant->id]) }}"
                    class="flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <select name="timezone" class="oh-select h-10 min-w-[260px]">
                        <option value="">Use default ({{ $defaultTz }})</option>
                        @foreach ($timezoneOptions as $tz)
                            <option value="{{ $tz }}" @selected($tenantTz === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <button class="oh-btn oh-btn--primary ml-auto" type="submit">Save</button>
                </form>

            </div>

            <div class="oh-card p-6 space-y-4" id="overtime">
                <h2 class="text-base font-semibold text-text-base">Overtime rules</h2>
                <p class="text-sm text-text-subtle">Set overtime thresholds for reporting and payroll export.</p>
                <form method="POST" action="{{ route('tenant.trades.settings.time.update', ['tenant' => $tenant->id]) }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    @csrf
                    @method('PATCH')
                    <label class="flex items-center gap-2 text-sm md:col-span-3">
                        <input type="checkbox" name="overtime_enabled" value="1" @checked($tenant->overtime_enabled)
                            class="rounded border-border-default text-brand-primary">
                        <span class="text-text-base">Enable overtime tracking</span>
                    </label>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">Daily hours</label>
                        <input class="oh-input h-9" name="overtime_daily_hours" type="number" step="0.25"
                            value="{{ old('overtime_daily_hours', $tenant->overtime_daily_hours) }}">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">Weekly hours</label>
                        <input class="oh-input h-9" name="overtime_weekly_hours" type="number" step="0.25"
                            value="{{ old('overtime_weekly_hours', $tenant->overtime_weekly_hours) }}">
                    </div>
                    <div class="flex justify-end md:col-span-3">
                        <button class="oh-btn oh-btn--primary" type="submit">Save overtime</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="oh-card p-6 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Work schedules</h2>
                    <p class="text-sm text-text-subtle">Company hours and weekly tech availability.</p>
                </div>
                <a class="oh-btn" href="{{ route('tenant.trades.settings.time.work-schedules', ['tenant' => $tenant->id]) }}">
                    Manage schedules
                </a>
            </div>
        </div>
        <div class="oh-card p-6 space-y-4" id="pto-approvers">
            <h2 class="text-base font-semibold text-text-base">PTO approvers</h2>
            <form method="POST" action="{{ route('tenant.trades.settings.time.update', ['tenant' => $tenant->id]) }}"
                class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PATCH')
                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-text-base" for="pto_approver_id">Primary approver</label>
                    <select id="pto_approver_id" name="pto_approver_id" class="oh-select h-10">
                        <option value="">Select approver</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $tenant->pto_approver_id === (int) $user->id)>
                                {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-text-base" for="pto_backup_approver_id">Backup
                        approver</label>
                    <select id="pto_backup_approver_id" name="pto_backup_approver_id" class="oh-select h-10">
                        <option value="">Select backup</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $tenant->pto_backup_approver_id === (int) $user->id)>
                                {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="oh-btn oh-btn--primary" type="submit">Save approvers</button>
                </div>
            </form>
        </div>

        <div class="oh-card p-6 space-y-4" id="pto-types">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-text-base">PTO types</h2>
            </div>

            <div class="space-y-3">
                @foreach ($ptoTypes as $type)
                    <div class="rounded-xl border border-border-default p-4 space-y-3">
                        <form method="POST"
                            action="{{ route('tenant.trades.settings.pto-types.update', ['tenant' => $tenant->id, 'type' => $type->id]) }}"
                            class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
                            @csrf
                            @method('PUT')
                            <div class="space-y-1.5 lg:col-span-2">
                                <label class="text-xs text-text-subtle">Name</label>
                                <input class="oh-input h-9" name="name" value="{{ old('name', $type->name) }}">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Code</label>
                                <input class="oh-input h-9" name="code" value="{{ old('code', $type->code) }}">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Hours/period</label>
                                <input class="oh-input h-9" name="accrual_rate_hours" type="number" step="0.25"
                                    value="{{ old('accrual_rate_hours', $type->accrual_rate_hours) }}">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Tenure months</label>
                                <input class="oh-input h-9" name="tenure_months" type="number" min="1"
                                    value="{{ old('tenure_months', $type->tenure_months) }}">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Tenure rate</label>
                                <input class="oh-input h-9" name="tenure_accrual_rate_hours" type="number"
                                    step="0.25"
                                    value="{{ old('tenure_accrual_rate_hours', $type->tenure_accrual_rate_hours) }}">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Period</label>
                                <select class="oh-select h-9" name="accrual_period">
                                    <option value="month" @selected($type->accrual_period === 'month')>Monthly</option>
                                    <option value="pay_period" @selected($type->accrual_period === 'pay_period')>Pay period</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs text-text-subtle">Carryover cap</label>
                                <input class="oh-input h-9" name="carryover_cap_hours" type="number" step="0.25"
                                    value="{{ old('carryover_cap_hours', $type->carryover_cap_hours) }}">
                            </div>
                            <div class="flex items-center gap-3 lg:col-span-6">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="carryover_enabled" value="1"
                                        @checked($type->carryover_enabled)
                                        class="rounded border-border-default text-brand-primary">
                                    <span class="text-text-base">Carryover allowed</span>
                                </label>
                                <button class="oh-btn oh-btn--primary ml-auto" type="submit">Save</button>
                            </div>
                        </form>
                        <form method="POST"
                            action="{{ route('tenant.trades.settings.pto-types.toggle', ['tenant' => $tenant->id, 'type' => $type->id]) }}">
                            @csrf
                            @method('PATCH')
                            <button class="oh-btn" type="submit">
                                {{ $type->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            <p class="text-sm text-text-subtle">Approved PTO types that are currently enabled.</p>

        </div>

        <div class="oh-card p-6 space-y-4" id="holidays">
            <h2 class="text-base font-semibold text-text-base">Holiday calendar</h2>
            <p class="text-sm text-text-subtle">Add paid or unpaid holidays for PTO tracking and reporting.</p>
            @if (!$hasHolidays)
                <div class="text-sm text-text-subtle">Holiday settings are not available yet. Run the latest
                    migrations.</div>
            @else
                <form method="POST"
                    action="{{ route('tenant.trades.settings.holidays.store', ['tenant' => $tenant->id]) }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs text-text-subtle">Holiday name</label>
                        <input class="oh-input h-9" name="name" placeholder="New Year's Day" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">Date</label>
                        <input class="oh-input h-9" name="holiday_date" type="date" required>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_paid" value="1" checked
                                class="rounded border-border-default text-brand-primary">
                            <span class="text-text-base">Paid</span>
                        </label>
                        <button class="oh-btn oh-btn--primary ml-auto" type="submit">Add</button>
                    </div>
                </form>

                <div class="space-y-2">
                    @forelse ($holidays ?? [] as $holiday)
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-border-default p-3">
                            <div class="text-sm text-text-base">
                                {{ $holiday->name }} · {{ $holiday->holiday_date->format('M j, Y') }}
                                <span class="text-xs text-text-subtle">{{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}</span>
                            </div>
                            <form method="POST"
                                action="{{ route('tenant.trades.settings.holidays.destroy', ['tenant' => $tenant->id, 'holiday' => $holiday->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="oh-btn text-xs" type="submit">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No holidays added yet.</div>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="oh-card p-6 space-y-4">
            <h2 class="text-base font-semibold text-text-base">Pending PTO requests</h2>
            @forelse ($pendingRequests as $request)
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 rounded-xl border border-border-default p-4">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-text-base">
                            {{ trim(($request->user->first_name ?? '') . ' ' . ($request->user->last_name ?? '')) ?: $request->user->email }}
                        </div>
                        <div class="text-xs text-text-subtle">
                            {{ $request->type?->name }} · {{ $request->start_date->format('M j') }} to
                            {{ $request->end_date->format('M j') }} · {{ $request->hours_requested }} hrs
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST"
                            action="{{ route('tenant.trades.settings.pto-requests.approve', ['tenant' => $tenant->id, 'ptoRequest' => $request->id]) }}">
                            @csrf
                            <button class="oh-btn oh-btn--primary" type="submit">Approve</button>
                        </form>
                        <form method="POST"
                            action="{{ route('tenant.trades.settings.pto-requests.deny', ['tenant' => $tenant->id, 'ptoRequest' => $request->id]) }}">
                            @csrf
                            <button class="oh-btn" type="submit">Deny</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-sm text-text-subtle">No pending PTO requests.</div>
            @endforelse
        </div>
    </div>
@endsection
