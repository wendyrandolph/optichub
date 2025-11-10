@extends('layouts.app')
@section('title', 'Upgrade Plan')
@section('content')
    @php($tenantId = auth()->user()->tenant_id ?? null)
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Billing & Subscription</h1>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="p-4 rounded-xl border">
                <h2 class="font-medium mb-2">Current Plan</h2>
                <p class="text-gray-600 text-sm">Plan details coming soon.</p>


            </div>

            <div class="p-4 rounded-xl border">
                <h2 class="font-medium mb-2">Payment Provider</h2>
                <p class="text-gray-600 text-sm">
                    Configure Stripe, Authorize.net, or your preferred provider in <a
                        href="{{ route('tenant.settings.api.index', ['tenant' => auth()->user()->tenant_id]) }}"
                        class="text-blue-600 underline">API Keys</a>.
                </p>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="font-medium mb-2">Invoices & History</h2>
            <a class="btn btn--ghost" href="{{ route('tenant.invoices.index', ['tenant' => auth()->user()->tenant_id]) }}">
                View Invoices
            </a>
        </div>
    </div>
@endsection
