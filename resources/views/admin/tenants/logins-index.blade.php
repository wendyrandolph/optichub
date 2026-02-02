@extends('layouts.app')

@section('title', 'Tenants — Last Login')

@section('content')
    @php
        $tenantCollection = method_exists($tenants, 'getCollection') ? $tenants->getCollection() : $tenants;
        $trackedUsers = $tenantCollection?->sum(fn($tenant) => $tenant->users?->count() ?? 0) ?? 0;
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <h1 class="text-2xl font-semibold text-text-base">Tenant Last Login</h1>
                <p class="text-sm text-text-subtle mt-1">Latest known activity for each workspace.</p>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'logins'])

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <p class="text-xl font-semibold text-text-base">{{ $tenantCollection?->count() ?? 0 }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Users tracked</p>
                <p class="text-xl font-semibold text-text-base">{{ $trackedUsers }}</p>
            </div>
        </div>

        <div class="oh-card border border-border-default/70 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/70 text-sm font-semibold text-text-base">
                Latest activity
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted/50 text-text-subtle">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Last login</th>
                            <th class="px-4 py-3 text-left font-medium">Users tracked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/60">
                        @forelse ($tenants as $tenant)
                            @php
                                $lastActivity = $tenant->users?->max('updated_at');
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.tenants.logins.show', $tenant) }}"
                                        class="font-semibold text-text-base hover:text-[rgb(var(--ui-primary))]">
                                        {{ $tenant->name ?? 'Untitled tenant' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-text-base">
                                    {{ $lastActivity ? $lastActivity->format('M j, Y g:i A') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-text-base font-semibold">{{ $tenant->users?->count() ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-text-subtle">
                                    No tenants found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-border-default/70 text-xs text-text-subtle">
                Note: login tracking is not yet enabled; this uses the most recent user activity timestamp.
            </div>
        </div>

        @if (method_exists($tenants, 'links'))
            <div>{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
