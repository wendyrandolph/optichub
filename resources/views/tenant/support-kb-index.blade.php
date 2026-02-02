@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Knowledge Base')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="space-y-1">
            <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
            <h1 class="text-2xl font-semibold text-text-base">Knowledge Base</h1>
            <p class="text-sm text-text-subtle">Articles to help you answer common questions quickly.</p>
        </header>

        <div class="oh-card p-4">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="text" name="q" value="{{ $search }}" class="oh-input h-10 flex-1" placeholder="Search articles">
                <button type="submit" class="oh-btn oh-btn--primary h-10">Search</button>
            </form>
        </div>

        @if ($articles->isNotEmpty() && $search)
            <div class="oh-card p-4 space-y-2">
                <h2 class="text-sm font-semibold text-text-base">Search results</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($articles as $article)
                        <li>
                            <a href="{{ route('tenant.support.kb.show', ['tenant' => $tenant->id, 'article' => $article->slug]) }}" class="text-text-base hover:underline">
                                {{ $article->title }}
                            </a>
                            @if ($article->excerpt)
                                <p class="text-xs text-text-subtle">{{ $article->excerpt }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ($categories as $category)
                <div class="oh-card p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-text-base">{{ $category->name }}</h2>
                    @if ($category->articles->isEmpty())
                        <p class="text-sm text-text-subtle">No articles yet.</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($category->articles as $article)
                                <li>
                                    <a href="{{ route('tenant.support.kb.show', ['tenant' => $tenant->id, 'article' => $article->slug]) }}" class="text-text-base hover:underline">
                                        {{ $article->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
