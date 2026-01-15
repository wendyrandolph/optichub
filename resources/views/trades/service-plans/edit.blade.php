@extends('layouts.trades')

@section('title', 'Edit Service Plan')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3">
            <a href="{{ route('tenant.trades.service-plans.show', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                Back to plan
            </a>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit Service Plan</h1>
                <p class="text-sm text-text-subtle mt-1">Adjust cadence, scope, and line items.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST"
                action="{{ route('tenant.trades.service-plans.update', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}"
                class="space-y-5">
                @csrf
                @method('PUT')

                @include('trades.service-plans._form', [
                    'plan' => $plan,
                    'clients' => $clients,
                    'companies' => $companies,
                    'locations' => $locations,
                ])

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ route('tenant.trades.service-plans.show', ['tenant' => $tenantKey, 'service_plan' => $plan->id]) }}" class="oh-btn">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
