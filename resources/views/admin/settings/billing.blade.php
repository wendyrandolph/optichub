@extends('layouts.app')
@section('title', 'Billing')
@section('content')
    @php($tenantId = auth()->user()->tenant_id ?? null)

    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-2">Billing</h1>
        <p class="text-gray-600 mb-6">Manage your plan and payment settings.</p>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="p-4 rounded-xl border">
                <h2 class="font-medium mb-2">Current Plan</h2>
                <p class="text-gray-600 text-sm">You’re on a trial or current plan.</p>
                <a class="btn btn--brand mt-3" href="{{ route('tenant.settings.billing.upgrade', ['tenant' => $tenantId]) }}">
                    Upgrade
                </a>
            </div>

            <div class="p-4 rounded-xl border">
                <h2 class="font-medium mb-2">Payment Provider</h2>
                <p class="text-gray-600 text-sm">
                    Configure Stripe / Authorize.net in
                    <a class="text-blue-600 underline"
                        href="{{ route('tenant.settings.api.index', ['tenant' => $tenantId]) }}">API Keys</a>.
                </p>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="font-medium mb-2">Invoices & History</h2>
            <a class="btn btn--ghost" href="{{ route('tenant.invoices.index', ['tenant' => $tenantId]) }}">
                View Invoices
            </a>
        </div>
    </div>
@endsection
