@extends('layouts.trades')

@section('title', 'Service Location')

@section('trades-content')
    @php
        $clientName =
            trim(($location->client->firstName ?? '') . ' ' . ($location->client->lastName ?? '')) ?: 'Client';
        $companyName = $location->company?->company_name;
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

        $tenantKey = $tenant->getRouteKey();

    @endphp
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $location->label ?: 'Service location' }}</h1>
                <p class="text-sm text-text-subtle mt-1">{{ $clientName }}@if ($companyName)
                        · {{ $companyName }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.locations.edit', ['tenant' => $tenantKey, 'location' => $location->id]) }}"
                    class="oh-btn">Edit</a>
                <a href="{{ route('tenant.trades.locations.index', ['tenant' => $tenantKey]) }}" class="oh-btn">All
                    locations</a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="oh-card p-5 lg:col-span-2 space-y-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Address</div>
                    <div class="text-sm text-text-base mt-1">{{ $address ?: 'Address not set' }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Access notes</div>
                    <div class="text-sm text-text-base mt-1">
                        {{ $location->access_notes ?: 'No access notes.' }}
                    </div>
                </div>
                <div class="space-y-2 pt-2">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Service history</div>
                    @if (empty($jobs) || $jobs->isEmpty())
                        <div class="text-sm text-text-subtle">No jobs logged at this location yet.</div>
                    @else
                        <div class="space-y-2">
                            @foreach ($jobs as $job)
                                <a href="{{ route('tenant.trades.jobs.show', ['tenant' => $tenantKey, 'job' => $job->id]) }}"
                                    class="block rounded-lg border border-border-default/60 px-3 py-2 text-sm">
                                    <div class="font-medium text-text-base">{{ $job->summary }}</div>
                                    <div class="text-xs text-text-subtle">
                                        {{ ucfirst($job->status) }} · {{ $job->updated_at->format('M j, Y') }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="oh-card p-5 space-y-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Client</div>
                    <div class="text-sm text-text-base mt-1">{{ $clientName }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Company</div>
                    <div class="text-sm text-text-base mt-1">{{ $companyName ?: 'Unassigned' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
