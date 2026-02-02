@extends('layouts.app')

@section('title', 'Message a Teammate')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Communication</p>
                <h1 class="text-2xl font-semibold text-text-base">Message a teammate</h1>
                <p class="text-sm text-text-subtle mt-1">Start a direct conversation with someone on your team.</p>
            </div>
            <a class="oh-btn" href="{{ route('tenant.chat.index', ['tenant' => $tenantId]) }}">Back to chat</a>
        </div>

        <div class="oh-card border border-border-default/70 shadow-card p-5">
            <form method="GET" action="{{ route('tenant.chat.dm.index', ['tenant' => $tenantId]) }}"
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
                        $label = $name !== '' ? $name : ($teammate->email ?? 'Teammate');
                    @endphp
                    <a href="{{ route('tenant.chat.dm.start', ['tenant' => $tenantId, 'user' => $teammate->id]) }}"
                        class="flex items-center justify-between rounded-lg border border-border-default/70 px-3 py-2 text-sm hover:bg-surface-accent/40">
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
