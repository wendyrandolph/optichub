@extends('layouts.app')

@section('title', 'New Contract Template')

@section('content')
    @php
        $routePrefix = request()->routeIs('admin.tenants.contracts.templates.*')
            ? 'admin.tenants.contracts.templates.'
            : 'tenant.contracts.templates.';
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-text-base">New contract template</h1>
            <p class="text-sm text-text-subtle mt-1">Choose upload or manual builder mode.</p>
        </div>

        <form method="POST" action="{{ route($routePrefix . 'store', ['tenant' => $tenant->id]) }}" enctype="multipart/form-data"
            class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 space-y-4">
            @csrf
            <div>
                <label class="oh-label" for="title">Title</label>
                <input id="title" name="title" class="oh-input h-10 w-full" required>
            </div>
            <div>
                <label class="oh-label">Mode</label>
                <div class="mt-2 flex flex-wrap gap-3">
                    <label class="flex items-center gap-2 text-sm text-text-base">
                        <input type="radio" name="source_type" value="upload" checked>
                        Upload file
                    </label>
                    <label class="flex items-center gap-2 text-sm text-text-base">
                        <input type="radio" name="source_type" value="builder">
                        Manual builder
                    </label>
                </div>
            </div>
            <div>
                <label class="oh-label" for="contract_file">Contract file (PDF/DOCX)</label>
                <input id="contract_file" type="file" name="contract_file" class="oh-input h-10 w-full">
            </div>
            <div>
                <label class="oh-label" for="builder_json">Builder content (JSON)</label>
                <textarea id="builder_json" name="builder_json" class="oh-textarea w-full" rows="4"></textarea>
                <p class="text-xs text-text-subtle mt-1">
                    Renlo provides templates for convenience and does not provide legal advice. Consider having an attorney review your agreement.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="oh-btn oh-btn--primary">Save template</button>
                <a href="{{ route($routePrefix . 'index', ['tenant' => $tenant->id]) }}" class="oh-btn">Cancel</a>
            </div>
        </form>
    </div>
@endsection
