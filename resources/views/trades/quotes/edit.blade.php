@extends('layouts.trades')

@section('title', 'Edit Trade Quote')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit quote</h1>
                <p class="text-sm text-text-subtle mt-1">Update the draft before sending.</p>
            </div>
            <a class="oh-btn"
                href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">Back</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                Please fix the highlighted fields.
            </div>
        @endif

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST"
                action="{{ route('tenant.trades.quotes.update', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}"
                class="space-y-6">
                @csrf
                @method('PUT')

                @include('trades.quotes._form', [
                    'quote' => $quote,
                    'clients' => $clients,
                    'companies' => $companies,
                    'jobs' => $jobs,
                    'itemRows' => $itemRows,
                ])

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a class="oh-btn"
                        href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
