@extends('layouts.app')

@section('title', 'Contract Templates')

@section('content')
    @php
        $routePrefix = request()->routeIs('admin.tenants.contracts.templates.*')
            ? 'admin.tenants.contracts.templates.'
            : 'tenant.contracts.templates.';
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Contract templates</h1>
                <p class="text-sm text-text-subtle mt-1">Upload a contract or build a basic template.</p>
            </div>
            <a href="{{ route($routePrefix . 'create', ['tenant' => $tenant->id]) }}"
                class="oh-btn oh-btn--primary">New template</a>
        </div>

        <div class="space-y-4">
            @forelse ($templates as $template)
                <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-text-base">{{ $template->title }}</h2>
                            <p class="text-xs text-text-subtle mt-1">
                                {{ $template->source_type === 'upload' ? 'Uploaded file' : 'Manual builder' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a class="oh-btn" href="{{ route($routePrefix . 'edit', ['tenant' => $tenant->id, 'template' => $template->id]) }}">Edit</a>
                            <form method="POST" action="{{ route($routePrefix . 'destroy', ['tenant' => $tenant->id, 'template' => $template->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="oh-btn oh-btn--ghost">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 text-center">
                    <p class="text-sm text-text-subtle">No contract templates yet.</p>
                    <a href="{{ route($routePrefix . 'create', ['tenant' => $tenant->id]) }}"
                        class="oh-btn oh-btn--primary mt-4">Create your first template</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
