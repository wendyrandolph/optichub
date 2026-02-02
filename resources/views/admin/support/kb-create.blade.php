@extends('layouts.app')

@section('title', 'New Knowledge Base Article')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="space-y-1">
            <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
            <h1 class="text-2xl font-semibold text-text-base">New Article</h1>
        </header>

        <form method="POST" action="{{ route('admin.support.kb.store') }}" class="oh-card p-5 space-y-4">
            @csrf

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Category</span>
                <select name="category_id" class="oh-select h-10" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Title</span>
                <input type="text" name="title" class="oh-input h-10" required value="{{ old('title') }}">
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Excerpt</span>
                <input type="text" name="excerpt" class="oh-input h-10" value="{{ old('excerpt') }}">
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Audience</span>
                <select name="audience" class="oh-select h-10" required>
                    <option value="all">All workspaces</option>
                    <option value="creative">Creative</option>
                    <option value="trades">Trades</option>
                </select>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Body</span>
                <textarea name="body" rows="8" class="oh-textarea" required>{{ old('body') }}</textarea>
            </label>

            <label class="flex items-center gap-2 text-sm text-text-subtle">
                <input type="checkbox" name="is_published" value="1" class="h-4 w-4">
                Publish now
            </label>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.support.kb.index') }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Save article</button>
            </div>
        </form>
    </div>
@endsection
