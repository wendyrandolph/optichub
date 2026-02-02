@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
                <h1 class="text-2xl font-semibold text-text-base">Knowledge Base</h1>
                <p class="text-sm text-text-subtle">Manage articles shown to tenant workspaces.</p>
            </div>
            <a href="{{ route('admin.support.kb.create') }}" class="oh-btn oh-btn--primary">New article</a>
        </header>

        <form method="POST" action="{{ route('admin.support.kb.categories.store') }}" class="oh-card p-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            @csrf
            <input type="text" name="name" class="oh-input h-10 flex-1" placeholder="New category name" required>
            <button type="submit" class="oh-btn">Add category</button>
        </form>

        <div class="space-y-4">
            @forelse ($categories as $category)
                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-text-base">{{ $category->name }}</h2>
                        <form method="POST" action="{{ route('admin.support.kb.categories.destroy', $category) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="oh-btn">Delete</button>
                        </form>
                    </div>
                    @if ($category->articles->isEmpty())
                        <p class="text-sm text-text-subtle">No articles yet.</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($category->articles as $article)
                                <li class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-text-base">{{ $article->title }}</div>
                                        <div class="text-xs text-text-subtle">
                                            {{ $article->audience }} · {{ $article->is_published ? 'Published' : 'Draft' }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.support.kb.edit', $article) }}" class="oh-btn">Edit</a>
                                        <form method="POST" action="{{ route('admin.support.kb.destroy', $article) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="oh-btn">Delete</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div class="oh-card p-6 text-sm text-text-subtle">No knowledge base categories yet.</div>
            @endforelse
        </div>
    </div>
@endsection
