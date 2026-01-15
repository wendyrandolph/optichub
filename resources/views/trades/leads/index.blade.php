@extends('layouts.trades')

@section('title', 'Leads')

@section('trades-content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Leads</h1>
                <p class="text-sm text-text-subtle mt-1">Capture new opportunities and follow up fast.</p>
            </div>
            <a href="{{ route('tenant.trades.leads.create', ['tenant' => $tenant->id]) }}"
                class="oh-btn oh-btn--primary">
                New lead
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @foreach ($statusOptions as $status)
                @php
                    $count = (int) ($statusCounts[$status] ?? 0);
                @endphp
                <a href="{{ route('tenant.trades.leads.index', ['tenant' => $tenant->id, 'status' => $status]) }}"
                    class="oh-card p-3 border border-border-default/60 hover:border-border-default transition">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">{{ ucfirst($status) }}</div>
                    <div class="text-xl font-semibold text-text-base mt-1">{{ $count }}</div>
                </a>
            @endforeach
        </div>

        <div class="oh-card p-4 space-y-4">
            <form method="GET" class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                <input type="text" name="q" value="{{ request('q') }}"
                    class="oh-input h-10" placeholder="Search name, email, phone…">
                <select name="status" class="oh-select h-10">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                <select name="source" class="oh-select h-10">
                    <option value="">All sources</option>
                    @foreach ($sourceOptions as $source)
                        <option value="{{ $source }}" @selected(request('source') === $source)>
                            {{ ucfirst($source) }}
                        </option>
                    @endforeach
                </select>
                <select name="assigned_to" class="oh-select h-10">
                    <option value="">Any assignee</option>
                    @foreach ($assignees as $assignee)
                        @php
                            $assigneeName = trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? '')) ?: $assignee->email;
                        @endphp
                        <option value="{{ $assignee->id }}" @selected((string) request('assigned_to') === (string) $assignee->id)>
                            {{ $assigneeName }}
                        </option>
                    @endforeach
                </select>
                <div class="lg:col-span-4 flex gap-2">
                    <button type="submit" class="oh-btn">Apply</button>
                    <a href="{{ route('tenant.trades.leads.index', ['tenant' => $tenant->id]) }}" class="oh-btn">Reset</a>
                </div>
            </form>

            <div class="hidden lg:block">
                <div class="overflow-hidden rounded-xl border border-border-default/70">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">Lead</th>
                                <th class="px-3 py-2 text-left font-medium">Status</th>
                                <th class="px-3 py-2 text-left font-medium">Source</th>
                                <th class="px-3 py-2 text-left font-medium">Assigned</th>
                                <th class="px-3 py-2 text-left font-medium">Age</th>
                                <th class="px-3 py-2 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leads as $lead)
                                @php
                                    $assignee = $lead->assignedTo ?? $lead->owner ?? null;
                                    $assigneeName = $assignee
                                        ? trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? ''))
                                        : null;
                                    $responseTime = $lead->first_contacted_at && $lead->created_at
                                        ? $lead->created_at->diffForHumans($lead->first_contacted_at, [
                                            'parts' => 2,
                                            'short' => true,
                                            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                                        ])
                                        : null;
                                @endphp
                                <tr class="border-t border-border-default/60">
                                    <td class="px-3 py-3 align-top">
                                        <div class="font-semibold text-text-base">{{ $lead->name }}</div>
                                        <div class="text-xs text-text-subtle">
                                            {{ $lead->email ?? 'No email' }}
                                            @if ($lead->phone)
                                                · {{ $lead->phone }}
                                            @endif
                                        </div>
                                        @if (!$lead->first_contacted_at)
                                            <span class="inline-flex mt-2 rounded-full border border-border-default/60 bg-surface-muted px-2 py-0.5 text-[10px] uppercase tracking-wide text-text-subtle">
                                                Uncontacted
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">{{ ucfirst($lead->status ?? 'new') }}</td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">{{ ucfirst($lead->source ?? 'manual') }}</td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">{{ $assigneeName ?: 'Unassigned' }}</td>
                                    <td class="px-3 py-3 align-top text-xs text-text-subtle">
                                        {{ $lead->created_at?->diffForHumans() }}
                                        @if ($responseTime)
                                            <div class="mt-1 text-[10px] text-text-subtle">Responded {{ $responseTime }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 align-top text-right">
                                        <a href="{{ route('tenant.trades.leads.show', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}"
                                            class="oh-btn text-sm">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-border-default/60">
                                    <td class="px-3 py-4 text-text-subtle" colspan="6">No leads yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3 lg:hidden">
                @forelse ($leads as $lead)
                    <div class="rounded-xl border border-border-default px-4 py-3 space-y-2">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-text-base">{{ $lead->name }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $lead->email ?? 'No email' }}
                                    @if ($lead->phone)
                                        · {{ $lead->phone }}
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('tenant.trades.leads.show', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}"
                                class="oh-btn text-xs">View</a>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs text-text-subtle">
                            <span>Status: {{ ucfirst($lead->status ?? 'new') }}</span>
                            <span>Source: {{ ucfirst($lead->source ?? 'manual') }}</span>
                        </div>
                        <div class="text-xs text-text-subtle">Added {{ $lead->created_at?->diffForHumans() }}</div>
                        @if (!$lead->first_contacted_at)
                            <span class="inline-flex rounded-full border border-border-default/60 bg-surface-muted px-2 py-0.5 text-[10px] uppercase tracking-wide text-text-subtle">
                                Uncontacted
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No leads yet.
                    </div>
                @endforelse
            </div>

            {{ $leads->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
