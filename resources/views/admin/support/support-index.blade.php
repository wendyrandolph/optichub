@extends('layouts.app')

@section('title', 'Support Inbox')

@section('content')
    @php
        $statusPills = [
            'received' => 'oh-pill--info',
            'in_progress' => 'oh-pill--primary',
            'waiting_on_customer' => 'oh-pill--warning',
            'resolved' => 'oh-pill--success',
        ];
        $typePills = [
            'bug' => 'oh-pill--danger',
            'question' => 'oh-pill--muted',
            'feature' => 'oh-pill--info',
        ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Provider</p>
                <h1 class="text-2xl font-semibold text-text-base">Support Inbox</h1>
                <p class="text-sm text-text-subtle">Track internal requests and provider notes in one place.</p>
            </div>
            <a href="{{ route('admin.support.create') }}" class="oh-btn oh-btn--primary">New Ticket</a>
        </header>

        <form method="GET" class="oh-card p-4 space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Status</span>
                    <select name="status" class="oh-select h-10">
                        @foreach (['received' => 'Received', 'in_progress' => 'In progress', 'waiting_on_customer' => 'Waiting on customer', 'resolved' => 'Resolved'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Category</span>
                    <select name="category" class="oh-select h-10">
                        <option value="">All</option>
                        @foreach (['bug' => 'Bug', 'question' => 'Question', 'feature' => 'Feature'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Created by</span>
                    <select name="created_by" class="oh-select h-10">
                        <option value="">All</option>
                        @foreach ($creators as $creator)
                            <option value="{{ $creator->id }}" @selected(($filters['created_by'] ?? '') == $creator->id)>
                                {{ trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? '')) ?: $creator->email }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Tenant (optional)</span>
                    <select name="tenant_id" class="oh-select h-10">
                        <option value="">All</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(($filters['tenant_id'] ?? '') == $tenant->id)>
                                {{ $tenant->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                <a href="{{ route('admin.support.index') }}" class="oh-btn">Reset</a>
            </div>
        </form>

        <div class="space-y-3">
            @forelse ($tickets as $ticket)
                <a href="{{ route('admin.support.show', $ticket) }}" class="oh-card p-4 flex flex-col gap-3 hover:bg-surface-accent">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="oh-pill {{ $statusPills[$ticket->status] ?? 'oh-pill' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        <span class="oh-pill {{ $typePills[$ticket->category] ?? 'oh-pill' }}">{{ ucfirst($ticket->category) }}</span>
                        @if ($ticket->tenant)
                            <span class="oh-pill oh-pill--muted">{{ $ticket->tenant->name }}</span>
                        @endif
                    </div>
                    <div class="text-sm font-semibold text-text-base">{{ $ticket->subject }}</div>
                    <div class="text-xs text-text-subtle flex flex-wrap gap-3">
                        <span>Created {{ $ticket->created_at->format('M j, Y g:ia') }}</span>
                        @if ($ticket->creator)
                            <span>By {{ trim(($ticket->creator->first_name ?? '') . ' ' . ($ticket->creator->last_name ?? '')) }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="oh-card p-6 text-sm text-text-subtle">
                    No tickets yet.
                </div>
            @endforelse
        </div>

        {{ $tickets->links() }}
    </div>
@endsection
