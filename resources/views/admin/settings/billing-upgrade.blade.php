@extends('layouts.app')
@section('title', 'Upgrade Plan')

@section('content')
    @php($tenantId = auth()->user()->tenant_id ?? null)

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Billing &amp; Subscription</h1>
                    <p class="text-sm text-text-subtle mt-1">Choose the plan that fits your workspace and manage payments.</p>
                </div>
                <a class="oh-btn" href="{{ route('tenant.settings.billing', ['tenant' => $tenantId]) }}">Back</a>
            </div>
        </div>

        @if (isset($plans) && is_array($plans))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($plans as $plan)
                    <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-text-base">{{ $plan['name'] ?? 'Plan' }}</h2>
                                <p class="text-sm text-text-subtle">{{ $plan['code'] ?? '' }}</p>
                            </div>
                            @if(isset($plan['price']))
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-text-base">${{ number_format($plan['price'] / 100, 2) }}</div>
                                    <div class="text-xs text-text-subtle">per month</div>
                                </div>
                            @endif
                        </div>
                        <ul class="text-sm text-text-subtle space-y-1.5">
                            @foreach ($plan['features'] ?? [] as $feat)
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                                    <span>{{ $feat }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="flex items-center justify-end">
                            <form method="POST" action="#">
                                @csrf
                                <button class="oh-btn oh-btn--primary">Select</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5">
                <p class="text-sm text-text-subtle">Plan details coming soon.</p>
            </div>
        @endif

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-2">
            <h2 class="text-sm font-semibold text-text-base">Invoices &amp; history</h2>
            <p class="text-sm text-text-subtle">Billing records for your Renlo subscription.</p>
            <a class="oh-btn" href="{{ route('tenant.subscription.invoices.index', ['tenant' => $tenantId]) }}">
                View subscription invoices
            </a>
        </div>
    </div>
@endsection
