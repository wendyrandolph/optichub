@extends('layouts.trades')

@section('title', 'Job Template')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $template->name }}</h1>
                <p class="text-sm text-text-subtle mt-1">{{ ucfirst($template->type) }} · {{ $template->default_status }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.job-templates.edit', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}"
                    class="oh-btn">Edit</a>
                <a href="{{ route('tenant.trades.job-templates.index', ['tenant' => $tenantKey]) }}" class="oh-btn">All
                    templates</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="oh-card p-5 space-y-3 lg:col-span-2">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Summary</div>
                    <div class="text-sm text-text-base mt-1">{{ $template->summary ?: 'No summary provided.' }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Description</div>
                    <div class="text-sm text-text-base mt-1">{{ $template->description ?: 'No description.' }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Line items</div>
                    @if ($template->items->isEmpty())
                        <div class="text-sm text-text-subtle mt-1">No items set.</div>
                    @else
                        <div class="divide-y divide-border-default/60 mt-2">
                            @foreach ($template->items as $item)
                                <div class="flex items-center justify-between py-2 text-sm">
                                    <div class="text-text-base">{{ $item->description }}</div>
                                    <div class="text-text-subtle">
                                        {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Checklist</div>
                    @if ($template->checklistItems->isEmpty())
                        <div class="text-sm text-text-subtle mt-1">No checklist items.</div>
                    @else
                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($template->checklistItems as $item)
                                <li class="flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                    <span class="text-text-base">{{ $item->label }}</span>
                                    @if ($item->is_required)
                                        <span class="text-[11px] text-text-subtle">Required</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="oh-card p-5 space-y-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Default duration</div>
                    <div class="text-sm text-text-base mt-1">
                        {{ $template->default_duration_minutes ? $template->default_duration_minutes . ' min' : 'Not set' }}
                    </div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Suggested tech count</div>
                    <div class="text-sm text-text-base mt-1">
                        {{ $template->suggested_tech_count ? $template->suggested_tech_count : 'Not set' }}
                    </div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Status</div>
                    <div class="text-sm text-text-base mt-1">{{ $template->default_status }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Active</div>
                    <div class="text-sm text-text-base mt-1">{{ $template->is_active ? 'Yes' : 'No' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
