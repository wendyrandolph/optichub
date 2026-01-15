@extends('layouts.trades')

@section('title', 'Edit Client')

@section('trades-content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit client</h1>
                <p class="text-sm text-text-subtle mt-1">Update contact details for this client.</p>
            </div>
            <a href="{{ route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id]) }}" class="oh-btn">Back</a>
        </div>

        <div class="oh-card p-6 space-y-5">
            <form method="POST" action="{{ route('tenant.trades.clients.update', ['tenant' => $tenant->id, 'client' => $client->id]) }}"
                class="space-y-5">
                @csrf
                @method('PUT')
                @include('trades.clients._form', ['client' => $client])

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
