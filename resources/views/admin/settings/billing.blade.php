@extends('layouts.app')
@section('title', 'Billing')

@section('content')
    @php
        $tenantId = auth()->user()->tenant_id ?? null;
        $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Subscription Billing</h1>
                    <p class="text-sm text-text-subtle mt-1">Manage your plan, payments, and subscription invoices.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a class="oh-btn" href="{{ route('tenant.settings.index', ['tenant' => $tenantId]) }}">Back</a>
                </div>
            </div>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Current Plan --}}
            <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Current plan</h2>
                        <p class="text-sm text-text-subtle">You’re on a trial or current plan.</p>
                    </div>
                    <span class="oh-pill oh-pill--info">Active</span>
                </div>
                <div class="flex items-center justify-end pt-2">
                    <a class="oh-btn oh-btn--primary"
                        href="{{ route('tenant.settings.billing-upgrade', ['tenant' => $tenantId]) }}">
                        Upgrade
                    </a>
                </div>
            </div>

            {{-- Payment Provider --}}
            <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-2">
                <h2 class="text-sm font-semibold text-text-base">Payment provider</h2>
                <p class="text-sm text-text-subtle">
                    Configure Stripe / Authorize.net via your API Keys.
                </p>
                <a class="oh-btn" href="{{ route('tenant.settings.api.index', ['tenant' => $tenantId]) }}">Go to API Keys</a>
            </div>
        </div>

        {{-- Invoices & History --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Invoices & history</h2>
                    <p class="text-sm text-text-subtle">Billing records for your Renlo subscription.</p>
                </div>
                <a class="oh-btn" href="{{ route('tenant.subscription.invoices.index', ['tenant' => $tenantId]) }}">
                    View subscription invoices
                </a>
            </div>
        </div>

        {{-- Invoice Preferences --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-3">
            <div>
                <h2 class="text-sm font-semibold text-text-base">Invoice preferences</h2>
                <p class="text-sm text-text-subtle">Control payment behavior for client invoices.</p>
            </div>
            <form method="POST" action="{{ route('tenant.settings.billing.update', ['tenant' => $tenantId]) }}"
                class="flex items-center justify-between gap-3">
                @csrf
                @method('PUT')
                <label class="inline-flex items-center gap-2 text-sm text-text-base">
                    <input type="checkbox" name="allow_partial_payments" value="1"
                        class="rounded border-[rgb(var(--border-default))] text-[rgb(var(--brand-primary))]"
                        @checked($tenant?->allow_partial_payments ?? true)>
                    Allow partial payments
                </label>
                <button type="submit" class="oh-btn oh-btn--primary">Save</button>
            </form>
        </div>
    </div>
@endsection
