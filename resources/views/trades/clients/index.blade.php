@extends('layouts.trades')

@section('title', 'Clients')

@section('trades-content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Clients</h1>
                <p class="text-sm text-text-subtle mt-1">Manage customers, job history, and service locations.</p>
            </div>
            <a href="{{ route('tenant.trades.clients.create', ['tenant' => $tenant->id]) }}"
                class="oh-btn oh-btn--primary">
                New client
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="oh-card p-4 border border-border-default/60">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Total clients</div>
                <div class="text-2xl font-semibold text-text-base mt-2">{{ $totalClients }}</div>
            </div>
            <div class="oh-card p-4 border border-border-default/60">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Added last 30 days</div>
                <div class="text-2xl font-semibold text-text-base mt-2">{{ $clientsAdded30d }}</div>
            </div>
            <div class="oh-card p-4 border border-border-default/60">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Clients with jobs</div>
                <div class="text-2xl font-semibold text-text-base mt-2">{{ $clientsWithJobs }}</div>
            </div>
            <div class="oh-card p-4 border border-border-default/60">
                <div class="text-xs text-text-subtle uppercase tracking-wide">Clients with service plans</div>
                <div class="text-2xl font-semibold text-text-base mt-2">
                    @if ($clientsWithPlans === null)
                        —
                        {{-- TODO: enable service plans to populate this metric --}}
                    @else
                        {{ $clientsWithPlans }}
                    @endif
                </div>
            </div>
        </div>

        <div class="oh-card p-4 space-y-4">
            <form method="GET" class="flex flex-col lg:flex-row gap-3">
                <input type="text" name="q" value="{{ $search }}"
                    class="oh-input h-10 flex-1" placeholder="Search name, email, phone…">
                <select name="scope" class="oh-select h-10 lg:w-56">
                    <option value="" @selected($scope === '')>All clients</option>
                    <option value="with_jobs" @selected($scope === 'with_jobs')>With jobs</option>
                    <option value="no_jobs" @selected($scope === 'no_jobs')>No jobs</option>
                    <option value="recent" @selected($scope === 'recent')>Recently updated</option>
                </select>
                <button type="submit" class="oh-btn">Apply</button>
            </form>

            <div class="hidden lg:block">
                <div class="overflow-hidden rounded-xl border border-border-default/70">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Client</th>
                                <th class="px-3 py-2 text-left font-medium">Contact</th>
                                <th class="px-3 py-2 text-left font-medium">Company</th>
                                <th class="px-3 py-2 text-left font-medium">Activity</th>
                                <th class="px-3 py-2 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $client)
                                @php
                                    $fullName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: 'Client';
                                    $companyName = $client->company?->company_name ?? '—';
                                    $jobsCount = $client->trade_jobs_count ?? null;
                                    $locationsCount = $client->service_locations_count ?? null;
                                    $plansCount = $client->trade_service_plans_count ?? null;
                                    $updatedAt = $client->updated_at?->diffForHumans();
                                    $lastJobAt = $client->last_job_at ? \Carbon\Carbon::parse($client->last_job_at)->diffForHumans() : null;
                                @endphp
                                <tr class="border-t border-border-default/60">
                                    <td class="px-3 py-3 align-top">
                                        <div class="font-semibold text-text-base">{{ $fullName }}</div>
                                        <div class="text-xs text-text-subtle">
                                            @if (!empty($client->email))
                                                {{ $client->email }}
                                            @else
                                                No email
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">
                                        {{ $client->phone ?? 'No phone' }}
                                    </td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">
                                        {{ $companyName }}
                                    </td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">
                                        <div>Last updated {{ $updatedAt ?? '—' }}</div>
                                        @if ($lastJobAt)
                                            <div>Last job {{ $lastJobAt }}</div>
                                        @endif
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @if ($jobsCount !== null)
                                                <span class="oh-pill oh-pill--muted">Jobs: {{ $jobsCount }}</span>
                                            @endif
                                            @if ($locationsCount !== null)
                                                <span class="oh-pill oh-pill--muted">Locations: {{ $locationsCount }}</span>
                                            @endif
                                            @if ($plansCount !== null)
                                                <span class="oh-pill oh-pill--muted">Plans: {{ $plansCount }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                                                class="oh-btn text-sm">View</a>
                                            <a href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                                                class="oh-btn text-sm">New job</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-border-default/60">
                                    <td class="px-3 py-4 text-text-subtle" colspan="5">No clients yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3 lg:hidden">
                @forelse ($clients as $client)
                    @php
                        $fullName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: 'Client';
                        $companyName = $client->company?->company_name ?? '—';
                        $jobsCount = $client->trade_jobs_count ?? null;
                        $locationsCount = $client->service_locations_count ?? null;
                        $plansCount = $client->trade_service_plans_count ?? null;
                        $updatedAt = $client->updated_at?->diffForHumans();
                        $lastJobAt = $client->last_job_at ? \Carbon\Carbon::parse($client->last_job_at)->diffForHumans() : null;
                    @endphp
                    <div class="rounded-xl border border-border-default px-4 py-3 space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-text-base">{{ $fullName }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $client->email ?? 'No email' }}
                                    @if (!empty($client->phone))
                                        · {{ $client->phone }}
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                                class="oh-btn text-xs">View</a>
                        </div>
                        <div class="text-xs text-text-subtle">Company: {{ $companyName }}</div>
                        <div class="text-xs text-text-subtle">
                            Last updated {{ $updatedAt ?? '—' }}
                            @if ($lastJobAt)
                                · Last job {{ $lastJobAt }}
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($jobsCount !== null)
                                <span class="oh-pill oh-pill--muted">Jobs: {{ $jobsCount }}</span>
                            @endif
                            @if ($locationsCount !== null)
                                <span class="oh-pill oh-pill--muted">Locations: {{ $locationsCount }}</span>
                            @endif
                            @if ($plansCount !== null)
                                <span class="oh-pill oh-pill--muted">Plans: {{ $plansCount }}</span>
                            @endif
                        </div>
                        <a href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                            class="oh-btn text-xs w-full">New job</a>
                    </div>
                @empty
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No clients yet.
                    </div>
                @endforelse
            </div>

            {{ $clients->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
