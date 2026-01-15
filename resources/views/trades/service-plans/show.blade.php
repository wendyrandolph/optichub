@extends('layouts.trades')

@section('title', 'Service Plan')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $clientName = trim(($plan->client?->firstName ?? '') . ' ' . ($plan->client?->lastName ?? '')) ?: 'Client';
        $locationLabel = $plan->serviceLocation?->label ?: $plan->serviceLocation?->address_line1;
        $next = $plan->next_occurrence?->format('M j, Y') ?? 'Not scheduled';
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $plan->title }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $clientName }} · {{ ucfirst($plan->cadence_unit) }} cadence
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenantKey, 'client' => $plan->client_id, 'location' => $plan->service_location_id]) }}"
                    class="oh-btn">Create job</a>
                <a href="{{ route('tenant.trades.service-plans.edit', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}"
                    class="oh-btn">Edit</a>
                <a href="{{ route('tenant.trades.service-plans.index', ['tenant' => $tenantKey]) }}" class="oh-btn">All plans</a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="oh-card p-5 space-y-4 lg:col-span-2">
                <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                    <span class="oh-pill oh-pill--muted">{{ ucfirst($plan->status) }}</span>
                    <span>Next visit: {{ $next }}</span>
                    @if ($locationLabel)
                        <span>Location: {{ $locationLabel }}</span>
                    @endif
                </div>

                <div class="text-sm text-text-base">
                    {{ $plan->notes ?: 'No notes yet.' }}
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-text-base">Plan line items</div>
                    @forelse ($plan->items as $item)
                        <div class="flex items-center justify-between rounded-lg border border-border-default px-3 py-2 text-sm">
                            <div class="min-w-0">
                                <div class="text-text-base">{{ $item->description }}</div>
                                <div class="text-xs text-text-subtle">Qty {{ $item->quantity }} · ${{ number_format($item->unit_price, 2) }}</div>
                            </div>
                            <div class="text-text-base font-semibold">${{ number_format($item->line_total, 2) }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-text-subtle">No line items yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-4">
                <div class="oh-card p-5 space-y-2">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Cadence</div>
                    <div class="text-sm text-text-base">
                        Every {{ $plan->cadence_interval }} {{ $plan->cadence_unit }}
                    </div>
                    <div class="text-xs text-text-subtle">
                        Starts {{ $plan->starts_on?->format('M j, Y') }}
                    </div>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Override dates</div>
                    <form method="POST"
                        action="{{ route('tenant.trades.service-plans.overrides.store', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}"
                        class="flex flex-wrap gap-2 items-center">
                        @csrf
                        <input type="date" name="override_date" class="oh-input h-9">
                        <input type="text" name="note" class="oh-input h-9 flex-1 min-w-[140px]" placeholder="Note (optional)">
                        <button class="oh-btn" type="submit">Add</button>
                    </form>
                    <div class="space-y-2">
                        @forelse ($plan->overrides as $override)
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-border-default px-3 py-2 text-xs">
                                <div class="text-text-base">
                                    {{ $override->override_date?->format('M j, Y') }}
                                    @if ($override->note)
                                        <span class="text-text-subtle">· {{ $override->note }}</span>
                                    @endif
                                </div>
                                <form method="POST"
                                    action="{{ route('tenant.trades.service-plans.overrides.destroy', ['tenant' => $tenantKey, 'service_plan' => $plan->id, 'override' => $override->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="oh-btn text-xs" type="submit">Remove</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-sm text-text-subtle">No override dates yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
