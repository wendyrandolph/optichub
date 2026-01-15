@extends('layouts.trades')

@section('title', 'PTO Requests')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">PTO Request</h1>
                <p class="text-sm text-text-subtle mt-1">Request time off and track balances.</p>
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

        <div class="oh-card p-6 space-y-4">
            <div class="flex flex-col gap-1">
                <div class="text-sm font-semibold text-text-base">PTO balances</div>
                <div class="text-xs text-text-subtle">Your current PTO hours by type.</div>
            </div>

            @if ($ptoTypes->isEmpty())
                <div class="text-sm text-text-subtle">PTO types are not configured yet.</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($ptoTypes as $type)
                        @php
                            $balance = $ptoBalances[$type->id]->balance_hours ?? 0;
                        @endphp
                        <div class="rounded-lg border border-border-default px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $type->name }}</div>
                            <div class="text-lg font-semibold text-text-base">{{ number_format($balance, 2) }} hrs</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="border-t border-border-default/60 pt-4 space-y-3">
                <div class="text-sm font-semibold text-text-base">Request PTO</div>
                <form method="POST" action="{{ route('tenant.trades.field.pto-requests.store', ['tenant' => $tenantKey]) }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <div class="space-y-1.5 md:col-span-1">
                        <label class="text-xs text-text-subtle">Type</label>
                        <select name="trade_pto_type_id" class="oh-select h-10">
                            @foreach ($ptoTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">Start date</label>
                        <input type="date" name="start_date" class="oh-input h-10" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">End date</label>
                        <input type="date" name="end_date" class="oh-input h-10" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-text-subtle">Hours requested</label>
                        <input type="number" name="hours_requested" class="oh-input h-10" step="0.25" min="0.5" required>
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs text-text-subtle">Notes (optional)</label>
                        <input type="text" name="notes" class="oh-input h-10" placeholder="Add a note for approver">
                    </div>
                    <div class="flex items-end md:col-span-3 justify-end">
                        <button class="oh-btn oh-btn--primary" type="submit">Submit request</button>
                    </div>
                </form>
            </div>

            <div class="border-t border-border-default/60 pt-4 space-y-3">
                <div class="text-sm font-semibold text-text-base">Recent PTO requests</div>
                @forelse ($ptoRequests as $request)
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 rounded-lg border border-border-default px-4 py-3">
                        <div>
                            <div class="text-sm font-semibold text-text-base">{{ $request->type?->name }}</div>
                            <div class="text-xs text-text-subtle">
                                {{ $request->start_date->format('M j') }} to {{ $request->end_date->format('M j') }}
                                · {{ $request->hours_requested }} hrs
                            </div>
                        </div>
                        <span class="text-xs uppercase tracking-wide text-text-subtle">{{ ucfirst($request->status) }}</span>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No PTO requests yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
