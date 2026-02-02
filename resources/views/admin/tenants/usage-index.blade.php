@extends('layouts.app')

@section('title', 'Tenants — Usage')

@section('content')
    @php
        $tenantCollection = method_exists($tenants, 'getCollection') ? $tenants->getCollection() : $tenants;
        $usageTotals = [
            'tenants' => $tenantCollection?->count() ?? 0,
            'users' => $tenantCollection?->sum('users_count') ?? 0,
            'projects' => $tenantCollection?->sum('projects_count') ?? 0,
        ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <h1 class="text-2xl font-semibold text-text-base">Tenant Usage</h1>
                <p class="text-sm text-text-subtle mt-1">Core usage metrics across each workspace.</p>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'usage'])

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <p class="text-xl font-semibold text-text-base">{{ $usageTotals['tenants'] }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Users</p>
                <p class="text-xl font-semibold text-text-base">{{ $usageTotals['users'] }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Projects</p>
                <p class="text-xl font-semibold text-text-base">{{ $usageTotals['projects'] }}</p>
            </div>
        </div>

        <div class="oh-card border border-border-default/70 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/70 text-sm font-semibold text-text-base">
                Usage snapshot
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted/50 text-text-subtle">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Users</th>
                            <th class="px-4 py-3 text-left font-medium">Projects</th>
                            <th class="px-4 py-3 text-left font-medium">Clients</th>
                            <th class="px-4 py-3 text-left font-medium">Invoices</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/60">
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.tenants.usage.show', $tenant) }}"
                                        class="font-semibold text-text-base hover:text-[rgb(var(--ui-primary))]">
                                        {{ $tenant->name ?? 'Untitled tenant' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-text-base font-semibold">{{ $tenant->users_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-text-base font-semibold">{{ $tenant->projects_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-text-base font-semibold">{{ $tenant->clients_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-text-base font-semibold">{{ $tenant->invoices_count ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-text-subtle">
                                    No tenants found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($tenants, 'links'))
            <div>{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
