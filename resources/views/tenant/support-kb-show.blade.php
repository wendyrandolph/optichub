@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', $article->title)

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <a href="{{ route('tenant.support.kb.index', ['tenant' => $tenant->id]) }}" class="text-xs text-text-subtle hover:text-text-base">Back to knowledge base</a>

        <div class="oh-card p-6 space-y-4">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Knowledge Base</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $article->title }}</h1>
                @if ($article->excerpt)
                    <p class="text-sm text-text-subtle">{{ $article->excerpt }}</p>
                @endif
            </div>

            <div class="text-sm text-text-base space-y-3">
                {!! nl2br(e($article->body)) !!}
            </div>
        </div>
    </div>
@endsection
