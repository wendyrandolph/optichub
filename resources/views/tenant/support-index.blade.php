@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Support Center')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
                <h1 class="text-2xl font-semibold text-text-base">Support Center</h1>
                <p class="text-sm text-text-subtle">Find answers or submit a ticket to the Renlo team.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tenant.support.tickets.create', ['tenant' => $tenant->id]) }}" class="oh-btn oh-btn--primary">Submit ticket</a>
                <a href="{{ route('tenant.support.tickets.index', ['tenant' => $tenant->id]) }}" class="oh-btn">My tickets</a>
            </div>
        </header>

        <div class="oh-card p-5">
            <form method="GET" action="{{ route('tenant.support.kb.index', ['tenant' => $tenant->id]) }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="text" name="q" class="oh-input h-10 flex-1" placeholder="Search the knowledge base">
                <button type="submit" class="oh-btn oh-btn--primary h-10">Search</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse ($categories as $category)
                <div class="oh-card p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-text-base">{{ $category->name }}</h2>
                    @if ($category->articles->isEmpty())
                        <p class="text-sm text-text-subtle">No articles yet.</p>
                    @else
                        <ul class="space-y-2 text-sm text-text-subtle">
                            @foreach ($category->articles as $article)
                                <li>
                                    <a href="{{ route('tenant.support.kb.show', ['tenant' => $tenant->id, 'article' => $article->slug]) }}" class="text-text-base hover:underline">
                                        {{ $article->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('tenant.support.kb.index', ['tenant' => $tenant->id]) }}" class="text-xs text-text-subtle hover:text-text-base">Browse all articles</a>
                </div>
            @empty
                <div class="oh-card p-6 text-sm text-text-subtle">No knowledge base content yet.</div>
            @endforelse
        </div>

        <div class="oh-card p-5 space-y-3">
            <h2 class="text-sm font-semibold text-text-base">Recent tickets</h2>
            @if ($tickets->isEmpty())
                <p class="text-sm text-text-subtle">No tickets submitted yet.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($tickets as $ticket)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('tenant.support.tickets.show', ['tenant' => $tenant->id, 'ticket' => $ticket->id]) }}" class="text-text-base hover:underline">
                                {{ $ticket->subject }}
                            </a>
                            <span class="text-xs text-text-subtle">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
