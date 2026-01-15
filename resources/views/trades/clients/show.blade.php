@extends('layouts.trades')

@section('title', 'Client')

@section('trades-content')
    @php
        $clientName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: 'Client';
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $clientName }}</h1>
                <p class="text-sm text-text-subtle mt-1">{{ $client->email ?? 'No email' }} · {{ $client->phone ?? 'No phone' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                    class="oh-btn oh-btn--primary">
                    New job
                </a>
                <a href="{{ route('tenant.trades.locations.create', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                    class="oh-btn">
                    Add service location
                </a>
                <a href="{{ route('tenant.trades.clients.edit', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                    class="oh-btn">
                    Edit
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="oh-card p-5 space-y-3">
                <h2 class="text-base font-semibold text-text-base">Service locations</h2>
                @forelse ($locations as $location)
                    <div class="rounded-lg border border-border-default px-3 py-2">
                        <div class="text-sm font-semibold text-text-base">{{ $location->label }}</div>
                        <div class="text-xs text-text-subtle">
                            {{ $location->address_line1 ?? '' }}
                            @if ($location->city || $location->state || $location->postal_code)
                                · {{ trim(($location->city ?? '') . ' ' . ($location->state ?? '') . ' ' . ($location->postal_code ?? '')) }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-text-subtle">No service locations yet.</div>
                @endforelse
            </div>

            <div class="oh-card p-5 space-y-3">
                <h2 class="text-base font-semibold text-text-base">Recent jobs</h2>
                @forelse ($jobs as $job)
                    <a href="{{ route('tenant.trades.jobs.show', ['tenant' => $tenant->id, 'job' => $job->id]) }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-border-default px-3 py-2 hover:bg-surface-accent/40 transition">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-text-base truncate">{{ $job->summary }}</div>
                            <div class="text-xs text-text-subtle">{{ ucfirst($job->status) }}</div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-text-subtle"></i>
                    </a>
                @empty
                    <div class="text-sm text-text-subtle">No jobs yet.</div>
                @endforelse
            </div>
        </div>

        @if ($tenant->trades_recurring_enabled)
            <div class="oh-card p-5 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-text-base">Service plans</h2>
                    <a href="{{ route('tenant.trades.service-plans.create', ['tenant' => $tenant->id, 'client_id' => $client->id]) }}"
                        class="oh-btn">New plan</a>
                </div>
                @forelse ($servicePlans as $plan)
                    <a href="{{ route('tenant.trades.service-plans.show', ['tenant' => $tenant->id, 'service_plan' => $plan->id]) }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-border-default px-3 py-2 hover:bg-surface-accent/40 transition">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-text-base truncate">{{ $plan->title }}</div>
                            <div class="text-xs text-text-subtle">
                                Next: {{ $plan->next_occurrence?->format('M j, Y') ?? 'Not scheduled' }}
                                @if ($plan->serviceLocation)
                                    · {{ $plan->serviceLocation->label ?? $plan->serviceLocation->address_line1 }}
                                @endif
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-text-subtle"></i>
                    </a>
                @empty
                    <div class="text-sm text-text-subtle">No service plans yet.</div>
                @endforelse
            </div>
        @endif
    </div>
@endsection
