@extends('layouts.app')

@section('title', 'Tenant Subscription')

@section('content')
    @php
        $name = $tenant->name ?? 'Untitled tenant';
        $plan = $subscription?->plan_code ?? $tenant->plan_name ?? '—';
        $status = $subscription?->status ?? $tenant->subscription_status ?? '—';
        $amount = $subscription?->amount;
        $periodEnd = optional($subscription?->current_period_end)->format('M j, Y') ?? '—';
        $autoRenew = $subscription?->auto_renew ? 'Yes' : 'No';
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenant</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Subscription details for this workspace.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn oh-btn--primary">Tenant overview</a>
                <a href="{{ route('admin.tenants.subscriptions.index') }}" class="oh-btn">All subscriptions</a>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'subscriptions', 'tenant' => $tenant])

        <div class="oh-card border border-border-default/70 rounded-2xl p-5 space-y-4">
            @php
                $planKey = strtolower((string) $plan);
                $planPill = match ($planKey) {
                    'starter', 'basic' => 'oh-pill oh-pill--muted',
                    'pro', 'professional' => 'oh-pill oh-pill--brand',
                    'studio', 'business' => 'oh-pill oh-pill--secondary',
                    'enterprise', 'agency' => 'oh-pill oh-pill--accent',
                    default => 'oh-pill',
                };

                $statusKey = strtolower((string) $status);
                $statusPill = match ($statusKey) {
                    'active' => 'oh-pill oh-pill--success',
                    'trialing' => 'oh-pill oh-pill--info',
                    'beta' => 'oh-pill oh-pill--beta',
                    'paused' => 'oh-pill oh-pill--warning',
                    'canceled', 'cancelled' => 'oh-pill oh-pill--danger',
                    default => 'oh-pill oh-pill--muted',
                };
            @endphp
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-text-subtle">Subscription summary</p>
                    <p class="text-sm text-text-subtle">Billing and renewal details for this workspace.</p>
                </div>
            </div>
            @php
                $autoRenewPill = $autoRenew === 'Yes' ? 'oh-pill oh-pill--success' : 'oh-pill oh-pill--muted';
            @endphp
            <div class="flex items-center gap-2 flex-wrap">
                <span class="{{ $planPill }}">Plan: {{ $plan }}</span>
                <span class="{{ $statusPill }}">Status: {{ ucfirst($status) }}</span>
                <span class="{{ $autoRenewPill }}">Auto renew: {{ $autoRenew }}</span>
            </div>
            @php
                $since = ($subscription?->created_at ?? $tenant->created_at)?->format('M j, Y') ?? '—';
            @endphp
            @php
                $periodStart = $subscription?->current_period_start?->format('M j, Y') ?? $since;
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="oh-stat">
                    <div class="oh-stat__label">Amount</div>
                    <div class="oh-stat__value">{{ $amount !== null ? '$' . number_format($amount, 2) : '—' }}</div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Period start</div>
                    <div class="oh-stat__value">{{ $periodStart }}</div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Period end</div>
                    <div class="oh-stat__value">{{ $periodEnd }}</div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Since</div>
                    <div class="oh-stat__value">{{ $since }}</div>
                </div>
            </div>
        </div>

        @php
            $planOptions = [
                'starter' => 'Starter',
                'pro' => 'Pro',
                'studio' => 'Studio',
                'enterprise' => 'Enterprise',
            ];
            $statusOptions = [
                'active' => 'Active',
                'trialing' => 'Trialing',
                'beta' => 'Beta',
                'paused' => 'Paused',
                'canceled' => 'Canceled',
            ];
        @endphp

        <div class="oh-card border border-border-default/70 rounded-2xl p-5 space-y-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Update plan</p>
                <p class="text-sm text-text-subtle">Change plan and status without requiring tenant login.</p>
            </div>
            <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="oh-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ $tenant->name }}">
                <input type="hidden" name="website" value="{{ $tenant->website }}">
                <input type="hidden" name="timezone" value="{{ $tenant->timezone }}">
                <input type="hidden" name="primary_color" value="{{ $tenant->primary_color }}">
                <input type="hidden" name="secondary_color" value="{{ $tenant->secondary_color }}">
                <input type="hidden" name="accent_color" value="{{ $tenant->accent_color }}">

                <div class="oh-form-grid">
                    <div class="oh-field">
                        <label class="oh-label" for="plan_name">Plan</label>
                        <select id="plan_name" name="plan_name" class="oh-select">
                            <option value="">Select plan</option>
                            @foreach ($planOptions as $value => $label)
                                <option value="{{ $value }}" @selected($value === $planKey)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="oh-field">
                        <label class="oh-label" for="subscription_status">Status</label>
                        <select id="subscription_status" name="subscription_status" class="oh-select">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($value === $statusKey)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end">
                    <button type="submit" class="oh-btn oh-btn--primary">Update plan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
