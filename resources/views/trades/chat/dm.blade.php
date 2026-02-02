@extends('layouts.trades')

@section('title', 'Message a Teammate')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Team</p>
                <h1 class="text-2xl font-semibold text-text-base">Message a teammate</h1>
                <p class="text-sm text-text-subtle mt-1">Start a direct conversation with someone on your team.</p>
            </div>
            <a class="oh-btn" href="{{ route('tenant.trades.chat.index', ['tenant' => $tenantKey]) }}">Back to chat</a>
        </div>

        <div class="oh-card p-5">
            <form method="GET" action="{{ route('tenant.trades.chat.dm.index', ['tenant' => $tenantKey]) }}"
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                <div class="flex-1">
                    <label class="text-xs font-semibold text-text-subtle" for="q">Search teammates</label>
                    <input id="q" name="q" value="{{ $search }}" class="oh-input mt-1 w-full"
                        placeholder="Name or email">
                </div>
                <div class="pt-6 sm:pt-0">
                    <button type="submit" class="oh-btn oh-btn--primary">Search</button>
                </div>
            </form>

            <div class="mt-4 space-y-2">
                @forelse ($teammates as $teammate)
                    @php
                        $name = trim(($teammate->first_name ?? '') . ' ' . ($teammate->last_name ?? ''));
                        $label = $name !== '' ? $name : ($teammate->username ?? $teammate->email ?? 'Teammate');
                    @endphp
                    <a href="{{ route('tenant.trades.chat.dm.start', ['tenant' => $tenantKey, 'user' => $teammate->id]) }}"
                        class="flex items-center justify-between rounded-lg border border-border-default/70 px-3 py-2 text-sm hover:bg-surface-accent/30">
                        <div class="min-w-0">
                            <div class="font-semibold text-text-base truncate">{{ $label }}</div>
                            <div class="text-xs text-text-subtle truncate">{{ $teammate->email }}</div>
                        </div>
                        <span class="oh-btn oh-btn--sm">Message</span>
                    </a>
                @empty
                    <div class="text-sm text-text-subtle">No teammates found.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
