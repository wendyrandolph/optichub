@extends('layouts.trades')

@section('title', 'New Job Template')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
            <h1 class="text-2xl font-semibold text-text-base">New Job Template</h1>
            <p class="text-sm text-text-subtle mt-1">Create a reusable template for repeatable jobs.</p>
        </div>

        <form method="POST" action="{{ route('tenant.trades.job-templates.store', ['tenant' => $tenantKey]) }}"
            class="oh-card p-5 space-y-6">
            @csrf

            @include('trades.job-templates._form', ['tenant' => $tenant])

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('tenant.trades.job-templates.index', ['tenant' => $tenantKey]) }}" class="oh-btn">Cancel</a>
                <button class="oh-btn oh-btn--primary" type="submit">Create template</button>
            </div>
        </form>
    </div>
@endsection
