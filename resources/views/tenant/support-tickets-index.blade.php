@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'My Support Tickets')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
                <h1 class="text-2xl font-semibold text-text-base">My Tickets</h1>
                <p class="text-sm text-text-subtle">Track your requests and responses from the Renlo team.</p>
            </div>
            <a href="{{ route('tenant.support.tickets.create', ['tenant' => $tenant->id]) }}" class="oh-btn oh-btn--primary">Submit ticket</a>
        </header>

        <div class="space-y-3">
            @forelse ($tickets as $ticket)
                <a href="{{ route('tenant.support.tickets.show', ['tenant' => $tenant->id, 'ticket' => $ticket->id]) }}" class="oh-card p-4 flex flex-col gap-2 hover:bg-surface-accent">
                    <div class="flex items-center justify-between text-xs text-text-subtle">
                        <span>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        <span>{{ $ticket->updated_at->format('M j, Y') }}</span>
                    </div>
                    <div class="text-sm font-semibold text-text-base">{{ $ticket->subject }}</div>
                    <div class="text-xs text-text-subtle">{{ ucfirst($ticket->category) }} · {{ $ticket->created_at->format('M j, Y g:ia') }}</div>
                </a>
            @empty
                <div class="oh-card p-6 text-sm text-text-subtle">No tickets yet.</div>
            @endforelse
        </div>

        {{ $tickets->links() }}
    </div>
@endsection
