@extends('layouts.trades')

@section('title', 'Team Chat')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Team</p>
                <h1 class="text-2xl font-semibold text-text-base">Team Chat</h1>
                <p class="text-sm text-text-subtle mt-1">Keep conversations in one place.</p>
            </div>
        </div>

        @if (!empty($defaultChannel))
            <div class="oh-card p-5">
                <p class="text-sm text-text-subtle">Opening chat…</p>
            </div>

            <script>
                window.location = @json(route('tenant.trades.chat.show', ['tenant' => $tenantKey, 'channel' => $defaultChannel->id]));
            </script>
        @else
            <div class="oh-card p-5">
                <div class="text-sm font-semibold text-text-base">No channels yet</div>
                <p class="text-sm text-text-subtle mt-1">Create a channel to start chatting.</p>
            </div>
        @endif
    </div>
@endsection
