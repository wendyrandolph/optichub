@extends('layouts.trades')

@section('title', 'Job Templates')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Job Templates</h1>
                <p class="text-sm text-text-subtle mt-1">Standardize repeatable jobs with items and checklists.</p>
            </div>
            <a href="{{ route('tenant.trades.job-templates.create', ['tenant' => $tenantKey]) }}"
                class="oh-btn oh-btn--primary">
                New template
            </a>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="oh-card p-4 space-y-3">
            @forelse ($templates as $template)
                <div class="flex flex-col gap-2 rounded-xl border border-border-default bg-surface-accent/40 px-4 py-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-text-base truncate">{{ $template->name }}</div>
                            <div class="text-xs text-text-subtle">
                                {{ ucfirst($template->type) }} · {{ $template->default_status }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="oh-pill oh-pill--muted text-[11px]">
                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <a class="oh-btn text-xs"
                                href="{{ route('tenant.trades.job-templates.show', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}">
                                View
                            </a>
                            <a class="oh-btn text-xs"
                                href="{{ route('tenant.trades.job-templates.edit', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}">
                                Edit
                            </a>
                        </div>
                    </div>
                    @if ($template->summary)
                        <div class="text-xs text-text-subtle">{{ $template->summary }}</div>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                    No templates yet. Create one for repeatable work.
                </div>
            @endforelse
        </div>

        <div>
            {{ $templates->links() }}
        </div>
    </div>
@endsection
