@extends('layouts.trades')

@section('title', 'Service Plans')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Service Plans</h1>
                <p class="text-sm text-text-subtle mt-1">Recurring visits and maintenance agreements.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.service-plans.create', ['tenant' => $tenantKey]) }}"
                    class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus text-[12px] mr-2"></i> New plan
                </a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <input type="text" name="q" value="{{ request('q') }}" class="oh-input h-10 w-full"
                    placeholder="Search plans or clients">
            </div>
            <select name="status" class="oh-select h-10">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="paused" @selected(request('status') === 'paused')>Paused</option>
            </select>
            <button class="oh-btn" type="submit">Filter</button>
        </form>

        <div class="oh-card p-0 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-muted/60 text-text-subtle">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Plan</th>
                        <th class="px-4 py-3 text-left font-medium">Client</th>
                        <th class="px-4 py-3 text-left font-medium">Next visit</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $clientName = trim(($plan->client?->firstName ?? '') . ' ' . ($plan->client?->lastName ?? '')) ?: 'Client';
                            $next = $plan->next_occurrence?->format('M j, Y') ?? '—';
                        @endphp
                        <tr class="border-t border-border-default/60">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-text-base">{{ $plan->title }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ ucfirst($plan->cadence_unit) }} · Every {{ $plan->cadence_interval }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-text-base">{{ $clientName }}</td>
                            <td class="px-4 py-3 text-text-base">{{ $next }}</td>
                            <td class="px-4 py-3">
                                <span class="oh-pill oh-pill--muted">{{ ucfirst($plan->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('tenant.trades.service-plans.show', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}"
                                    class="oh-btn text-xs">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-border-default/60">
                            <td class="px-4 py-6 text-text-subtle" colspan="5">No service plans yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($plans->hasPages())
            <div>
                {{ $plans->links() }}
            </div>
        @endif
    </div>
@endsection
