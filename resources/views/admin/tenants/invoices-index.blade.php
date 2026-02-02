@extends('layouts.app')

@section('title', 'Tenants — Invoices')

@section('content')
    @php
        $subsCollection = method_exists($subscriptions, 'getCollection') ? $subscriptions->getCollection() : $subscriptions;
        $autoRenewCount = $subsCollection?->filter(fn($sub) => (bool) ($sub?->auto_renew ?? false))->count() ?? 0;
        $upcomingRenewals = $subsCollection?->filter(fn($sub) => !empty($sub?->current_period_end))->count() ?? 0;
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <h1 class="text-2xl font-semibold text-text-base">Subscription Invoices</h1>
                <p class="text-sm text-text-subtle mt-1">Upcoming renewals and subscription billing context.</p>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'invoices'])

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Subscriptions</p>
                <p class="text-xl font-semibold text-text-base">{{ $subsCollection?->count() ?? 0 }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Auto renew on</p>
                <p class="text-xl font-semibold text-text-base">{{ $autoRenewCount }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Upcoming renewals</p>
                <p class="text-xl font-semibold text-text-base">{{ $upcomingRenewals }}</p>
            </div>
        </div>

        <div class="oh-card border border-border-default/70 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/70 text-sm font-semibold text-text-base">
                Subscription billing
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted/50 text-text-subtle">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Plan</th>
                            <th class="px-4 py-3 text-left font-medium">Amount</th>
                            <th class="px-4 py-3 text-left font-medium">Next renewal</th>
                            <th class="px-4 py-3 text-left font-medium">Auto renew</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/60">
                        @forelse ($subscriptions as $subscription)
                            @php
                                $tenant = $subscription->tenant;
                                $plan = $subscription->plan_code ?? '—';
                                $planKey = strtolower((string) $plan);
                                $planPill = match ($planKey) {
                                    'starter', 'basic' => 'oh-pill oh-pill--muted',
                                    'pro', 'professional' => 'oh-pill oh-pill--brand',
                                    'studio', 'business' => 'oh-pill oh-pill--secondary',
                                    'enterprise', 'agency' => 'oh-pill oh-pill--accent',
                                    default => 'oh-pill',
                                };
                                $amount = $subscription->amount;
                                $next = optional($subscription->current_period_end)->format('M j, Y') ?? '—';
                                $auto = $subscription->auto_renew ? 'Yes' : 'No';
                                $autoPill = $subscription->auto_renew ? 'oh-pill oh-pill--success' : 'oh-pill oh-pill--muted';
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($tenant)
                                        <a href="{{ route('admin.tenants.invoices.show', $tenant) }}"
                                            class="font-semibold text-text-base hover:text-[rgb(var(--ui-primary))]">
                                            {{ $tenant->name ?? 'Untitled tenant' }}
                                        </a>
                                    @else
                                        <span class="text-text-subtle">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $planPill }}">{{ $plan }}</span>
                                </td>
                                <td class="px-4 py-3 text-text-base">
                                    {{ $amount !== null ? '$' . number_format($amount, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-text-base">{{ $next }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $autoPill }}">{{ $auto }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-text-subtle">
                                    No subscription records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($subscriptions, 'links'))
            <div>{{ $subscriptions->links() }}</div>
        @endif
    </div>
@endsection
