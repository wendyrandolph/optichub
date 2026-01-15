@extends('layouts.trades')

@section('title', 'Edit Lead')

@section('trades-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit lead</h1>
                <p class="text-sm text-text-subtle mt-1">Update status, notes, and contact details.</p>
            </div>
            <a href="{{ route('tenant.trades.leads.show', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="oh-btn">Back</a>
        </div>

        <div class="oh-card p-6">
            <form method="POST" action="{{ route('tenant.trades.leads.update', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="space-y-5">
                @csrf
                @method('PATCH')
                @include('trades.leads._form', ['lead' => $lead])
                <div class="flex justify-end gap-2">
                    <a href="{{ route('tenant.trades.leads.show', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
