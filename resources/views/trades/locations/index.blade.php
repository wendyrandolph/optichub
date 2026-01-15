@extends('layouts.trades')

@section('title', 'Service Locations')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Service Locations</h1>
                <p class="text-sm text-text-subtle mt-1">Track where services are delivered for each client.</p>
            </div>
            <a href="{{ route('tenant.trades.locations.create', ['tenant' => $tenantKey]) }}" class="oh-btn oh-btn--primary">
                New location
            </a>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="oh-card p-5 space-y-3">
            @forelse ($locations as $location)
                @php
                    $clientName =
                        trim(($location->client->firstName ?? '') . ' ' . ($location->client->lastName ?? '')) ?:
                        'Client';
                    $companyName = $location->company?->company_name;
                    $label = $location->label ?: 'Service location';
                    $address = trim(
                        implode(
                            ', ',
                            array_filter([
                                $location->address_line1,
                                $location->address_line2,
                                $location->city,
                                $location->state,
                                $location->postal_code,
                                $location->country,
                            ]),
                        ),
                    );
                @endphp
                <div class="flex flex-col gap-2 rounded-xl border border-border-default bg-surface-accent/40 px-4 py-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-text-base truncate">{{ $label }}</div>
                            <div class="text-xs text-text-subtle">
                                {{ $clientName }}@if ($companyName)
                                    · {{ $companyName }}
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a class="oh-btn text-xs"
                                href="{{ route('tenant.trades.locations.show', ['tenant' => $tenantKey, 'location' => $location->id]) }}">
                                View
                            </a>
                            <a class="oh-btn text-xs"
                                href="{{ route('tenant.trades.locations.edit', ['tenant' => $tenantKey, 'location' => $location->id]) }}">
                                Edit
                            </a>
                        </div>
                    </div>
                    <div class="text-xs text-text-subtle">
                        {{ $address ?: 'Address not set' }}
                    </div>
                </div>
                @empty
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No service locations yet. Add the first location for a client.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $locations->links() }}
            </div>
        </div>
    @endsection
