@extends('layouts.trades')

@section('title', 'New Lead')

@section('trades-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">New lead</h1>
                <p class="text-sm text-text-subtle mt-1">Capture the details and assign follow-up.</p>
            </div>
            <a href="{{ route('tenant.trades.leads.index', ['tenant' => $tenant->id]) }}" class="oh-btn">Back</a>
        </div>

        <div class="oh-card p-6">
            <form method="POST" action="{{ route('tenant.trades.leads.store', ['tenant' => $tenant->id]) }}" class="space-y-5">
                @csrf
                @include('trades.leads._form')
                <div class="flex justify-end gap-2">
                    <a href="{{ route('tenant.trades.leads.index', ['tenant' => $tenant->id]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save lead</button>
                </div>
            </form>
        </div>
    </div>
@endsection
