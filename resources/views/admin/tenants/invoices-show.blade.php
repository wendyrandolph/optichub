@extends('layouts.app')

@section('title', 'Tenant Invoice')

@section('content')
    @php
        $name = $tenant->name ?? 'Untitled tenant';
        $plan = $subscription?->plan_code ?? $tenant->plan_name ?? '—';
        $amount = $subscription?->amount;
        $next = optional($subscription?->current_period_end)->format('M j, Y') ?? '—';
        $auto = $subscription?->auto_renew ? 'Yes' : 'No';
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenant</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Subscription summary and billing overview.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn oh-btn--primary">Tenant overview</a>
                <a href="{{ route('admin.tenants.invoices.index') }}" class="oh-btn">All invoices</a>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'invoices', 'tenant' => $tenant])

        <div class="oh-card border border-border-default/70 rounded-2xl p-5 space-y-4">
            <div class="flex items-start gap-3">
                <div class="h-9 w-9 rounded-lg bg-surface-muted text-text-subtle flex items-center justify-center">
                    <i class="fa-regular fa-file-lines text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Subscription details</h2>
                    <p class="text-xs text-text-subtle mt-1">Billing status and renewal context for this tenant.</p>
                </div>
            </div>

            @php
                $planKey = strtolower((string) $plan);
                $planPill = match ($planKey) {
                    'starter', 'basic' => 'oh-pill oh-pill--muted',
                    'pro', 'professional' => 'oh-pill oh-pill--brand',
                    'studio', 'business' => 'oh-pill oh-pill--secondary',
                    'enterprise', 'agency' => 'oh-pill oh-pill--accent',
                    default => 'oh-pill',
                };
                $autoPill = $auto === 'Yes' ? 'oh-pill oh-pill--success' : 'oh-pill oh-pill--muted';
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="oh-stat">
                    <div class="oh-stat__label">Plan</div>
                    <div class="oh-stat__value">
                        <span class="{{ $planPill }}">{{ $plan }}</span>
                    </div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Amount</div>
                    <div class="oh-stat__value">
                        {{ $amount !== null ? '$' . number_format($amount, 2) : '—' }}
                    </div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Next renewal</div>
                    <div class="oh-stat__value">{{ $next }}</div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Auto renew</div>
                    <div class="oh-stat__value">
                        <span class="{{ $autoPill }}">{{ $auto }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-border-default pt-3 text-xs text-text-subtle">
                Subscription invoice history appears once billing data is connected.
            </div>
        </div>
    </div>
@endsection
