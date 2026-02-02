@extends('layouts.app')

@section('title', 'Tenants — Subscriptions')

@section('content')
    @php
        $stats = $stats ?? ['total' => 0, 'active' => 0, 'trialing' => 0];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenants</p>
                <h1 class="text-2xl font-semibold text-text-base">Subscriptions</h1>
                <p class="text-sm text-text-subtle mt-1">Overview of plans and subscription status across tenants.</p>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'subscriptions'])

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Total</p>
                <p class="text-xl font-semibold text-text-base">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Active</p>
                <p class="text-xl font-semibold text-text-base">{{ $stats['active'] ?? 0 }}</p>
            </div>
            <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Trialing</p>
                <p class="text-xl font-semibold text-text-base">{{ $stats['trialing'] ?? 0 }}</p>
            </div>
        </div>

        <div class="oh-card border border-border-default/70 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/70 text-sm font-semibold text-text-base">
                Subscription status
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted/50 text-text-subtle">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Plan</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Days on plan</th>
                            <th class="px-4 py-3 text-left font-medium">Amount</th>
                            <th class="px-4 py-3 text-left font-medium">Period end</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/60">
                        @forelse ($tenants as $tenant)
                            @php
                                $subscription = $tenant->currentSubscription;
                                $plan = $subscription?->plan_code ?? $tenant->plan_name ?? '—';
                                $planKey = strtolower((string) $plan);
                                $planPill = match ($planKey) {
                                    'starter', 'basic' => 'oh-pill oh-pill--muted',
                                    'pro', 'professional' => 'oh-pill oh-pill--brand',
                                    'studio', 'business' => 'oh-pill oh-pill--secondary',
                                    'enterprise', 'agency' => 'oh-pill oh-pill--accent',
                                    default => 'oh-pill',
                                };
                                $status = $subscription?->status ?? $tenant->subscription_status ?? '—';
                                $statusKey = strtolower((string) $status);
                                $statusPill = match ($statusKey) {
                                    'active' => 'oh-pill oh-pill--success',
                                    'trialing' => 'oh-pill oh-pill--info',
                                    'beta' => 'oh-pill oh-pill--beta',
                                    'paused' => 'oh-pill oh-pill--warning',
                                    'canceled', 'cancelled' => 'oh-pill oh-pill--danger',
                                    default => 'oh-pill oh-pill--muted',
                                };
                                $amount = $subscription?->amount;
                                $planStart = $subscription?->created_at ?? $tenant->created_at;
                                $daysOnPlan = $planStart ? $planStart->diffInDays(now()) : null;
                                $periodEnd = optional($subscription?->current_period_end)->format('M j, Y') ?? '—';
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.tenants.subscriptions.show', $tenant) }}"
                                        class="font-semibold text-text-base hover:text-[rgb(var(--ui-primary))]">
                                        {{ $tenant->name ?? 'Untitled tenant' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $planPill }}">{{ $plan }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $statusPill }}">{{ ucfirst($status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-text-base">
                                    {{ $daysOnPlan !== null ? number_format($daysOnPlan) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-text-base">
                                    {{ $amount !== null ? '$' . number_format($amount, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-text-base">{{ $periodEnd }}</td>
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
