@extends('layouts.app')

@section('title', 'New Support Ticket')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="space-y-1">
            <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Provider</p>
            <h1 class="text-2xl font-semibold text-text-base">New Ticket</h1>
            <p class="text-sm text-text-subtle">Capture what’s needed and keep the context with it.</p>
        </header>

        <form method="POST" action="{{ route('admin.support.store') }}" enctype="multipart/form-data"
            class="oh-card p-5 space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Category</span>
                    <select name="category" class="oh-select h-10" required>
                        <option value="bug" @selected(old('category') === 'bug')>Bug</option>
                        <option value="question" @selected(old('category', 'question') === 'question')>Question</option>
                        <option value="feature" @selected(old('category') === 'feature')>Feature</option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Tenant (optional)</span>
                    <select name="tenant_id" class="oh-select h-10">
                        <option value="">No tenant</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Priority</span>
                <select name="priority" class="oh-select h-10">
                    <option value="" @selected(!old('priority'))>Normal</option>
                    <option value="low" @selected(old('priority') === 'low')>Low</option>
                    <option value="normal" @selected(old('priority') === 'normal')>Normal</option>
                    <option value="high" @selected(old('priority') === 'high')>High</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
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

            <input type="hidden" name="context_route" id="context_route">
            <input type="hidden" name="context_url" id="context_url">
            <input type="hidden" name="context_viewport" id="context_viewport">

            <div class="flex items-center gap-2 justify-end">
                <a href="{{ route('admin.support.index') }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Create ticket</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const routeField = document.getElementById('context_route');
                const urlField = document.getElementById('context_url');
                const viewportField = document.getElementById('context_viewport');
                if (routeField) routeField.value = @json(request()->route()?->getName());
                if (urlField) urlField.value = window.location.href;
                if (viewportField) viewportField.value = `${window.innerWidth}x${window.innerHeight}`;
            });
        </script>
    @endpush
@endsection
