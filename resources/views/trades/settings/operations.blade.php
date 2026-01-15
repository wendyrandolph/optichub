@extends('layouts.trades')

@section('title', 'Trades Settings — Operations')

@section('trades-content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Operations</h1>
            <p class="text-sm text-text-subtle mt-1">Defaults that shape how jobs and schedules are created.</p>
        </div>

        <div class="oh-card p-6 space-y-3">
            <div>
                <h2 class="text-base font-semibold text-text-base">Operations shortcuts</h2>
                <p class="text-sm text-text-subtle">Manage templates and recurring service plans.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @if (Route::has('tenant.trades.job-templates.index'))
                    <a href="{{ route('tenant.trades.job-templates.index', ['tenant' => $tenant->id]) }}"
                        class="rounded-xl border border-border-default bg-surface-muted/40 p-4 text-sm text-text-base hover:bg-surface-muted/70">
                        <div class="font-semibold">Job templates</div>
                        <div class="text-xs text-text-subtle mt-1">Prefill jobs with default steps.</div>
                    </a>
                @endif
                @if (Route::has('tenant.trades.service-plans.index'))
                    <a href="{{ route('tenant.trades.service-plans.index', ['tenant' => $tenant->id]) }}"
                        class="rounded-xl border border-border-default bg-surface-muted/40 p-4 text-sm text-text-base hover:bg-surface-muted/70">
                        <div class="font-semibold">Service plans</div>
                        <div class="text-xs text-text-subtle mt-1">Recurring service schedules.</div>
                    </a>
                @endif
                <a href="{{ route('tenant.trades.settings.time', ['tenant' => $tenant->id]) }}"
                    class="rounded-xl border border-border-default bg-surface-muted/40 p-4 text-sm text-text-base hover:bg-surface-muted/70">
                    <div class="font-semibold">PTO & holidays</div>
                    <div class="text-xs text-text-subtle mt-1">Approvals, accruals, and calendars.</div>
                </a>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div class="oh-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-text-base">Work type</h2>
                <p class="text-sm text-text-subtle">Control whether jobs are residential, commercial, or both.</p>
                <form method="POST"
                    action="{{ route('tenant.trades.settings.operations.update', ['tenant' => $tenant->id]) }}"
                    class="flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PATCH')
                    @php $workType = old('trades_work_type', $tenant->trades_work_type ?? 'both'); @endphp
                    <select name="trades_work_type" class="oh-select h-10 min-w-[220px]">
                        <option value="residential" @selected($workType === 'residential')>Residential only</option>
                        <option value="commercial" @selected($workType === 'commercial')>Commercial only</option>
                        <option value="both" @selected($workType === 'both')>Residential + Commercial</option>
                    </select>
                    <button class="oh-btn oh-btn--primary ml-auto" type="submit">Save</button>
                </form>
            </div>

            <div class="oh-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-text-base">Service plans</h2>
                <p class="text-sm text-text-subtle">Enable recurring service plans if your team offers repeat visits.</p>
                <form method="POST"
                    action="{{ route('tenant.trades.settings.operations.update', ['tenant' => $tenant->id]) }}"
                    class="flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="trades_recurring_enabled" value="1" @checked($tenant->trades_recurring_enabled)
                            class="rounded border-border-default text-brand-primary">
                        <span class="text-text-base">Enable recurring service plans</span>
                    </label>
                    <button class="oh-btn oh-btn--primary ml-auto" type="submit">Save</button>
                </form>
            </div>

            <div class="oh-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-text-base">Warranty tracking</h2>
                <p class="text-sm text-text-subtle">Choose whether warranties are tracked on the job or per line item.</p>
                <form method="POST"
                    action="{{ route('tenant.trades.settings.operations.update', ['tenant' => $tenant->id]) }}"
                    class="flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PATCH')
                    @php $scope = $tenant->trades_warranty_scope ?? 'job'; @endphp
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="trades_warranty_scope" value="job" @checked($scope === 'job')
                            class="rounded-full border-border-default text-brand-primary">
                        <span class="text-text-base">Track warranty per job</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="trades_warranty_scope" value="line_item" @checked($scope === 'line_item')
                            class="rounded-full border-border-default text-brand-primary">
                        <span class="text-text-base">Track warranty per line item</span>
                    </label>
                    <button class="oh-btn oh-btn--primary ml-auto" type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection
