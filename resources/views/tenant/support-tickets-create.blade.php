@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', 'Submit Support Ticket')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="space-y-1">
            <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Support</p>
            <h1 class="text-2xl font-semibold text-text-base">Submit a Ticket</h1>
            <p class="text-sm text-text-subtle">Share the details and we’ll follow up soon.</p>
        </header>

        <form method="POST" action="{{ route('tenant.support.tickets.store', ['tenant' => $tenant->id]) }}" enctype="multipart/form-data" class="oh-card p-5 space-y-4">
            @csrf

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Category</span>
                <select name="category" class="oh-select h-10" required>
                    <option value="bug" @selected(old('category') === 'bug')>Bug</option>
                    <option value="question" @selected(old('category', 'question') === 'question')>Question</option>
                    <option value="feature" @selected(old('category') === 'feature')>Feature request</option>
                </select>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Subject</span>
                <input type="text" name="subject" class="oh-input h-10" required value="{{ old('subject') }}">
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Message</span>
                <textarea name="body" rows="5" class="oh-textarea" required>{{ old('body') }}</textarea>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Attachments (optional)</span>
                <input type="file" name="attachments[]" multiple class="oh-input h-10">
            </label>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('tenant.support.tickets.index', ['tenant' => $tenant->id]) }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Submit ticket</button>
            </div>
        </form>
    </div>
@endsection
