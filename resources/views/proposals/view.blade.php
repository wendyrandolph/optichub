@extends('layouts.app')

@section('title', 'Proposal templates')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (int) (request()->route('tenant') ?? auth()->user()?->tenant_id);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Sales</p>
                <h1 class="text-2xl font-semibold text-text-base">Proposal templates</h1>
                <p class="text-sm text-text-subtle mt-1">Reuse structured proposal layouts across projects.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.proposal-templates.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">New template</a>
                <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back to proposals</a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($templates as $template)
                <div class="oh-card border border-border-default/70 rounded-2xl p-5 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-sm font-semibold text-text-base">{{ $template->name }}</div>
                            <div class="text-xs text-text-subtle mt-1">{{ $template->title ?: 'Proposal structure' }}</div>
                        </div>
                        <a href="{{ route('tenant.proposals.create', ['tenant' => $tenantId, 'template_id' => $template->id]) }}" class="oh-btn">Create from template</a>
                    </div>
                    @if ($template->intro_text)
                        <p class="text-sm text-text-subtle">{{ \Illuminate\Support\Str::limit($template->intro_text, 140) }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        <a href="{{ route('tenant.proposal-templates.edit', ['tenant' => $tenantId, 'proposal_template' => $template->id]) }}" class="oh-btn">Edit</a>
                        <form method="POST" action="{{ route('tenant.proposal-templates.destroy', ['tenant' => $tenantId, 'proposal_template' => $template->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="oh-btn">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="oh-card border border-border-default/70 rounded-2xl p-6 text-sm text-text-subtle">
                    No templates yet. Save a proposal as a template or create one from scratch.
                </div>
            @endforelse
        </div>
    </div>
@endsection
