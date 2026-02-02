@extends('layouts.app')

@section('title', 'Tenant Last Login')

@section('content')
    @php
        $name = $tenant->name ?? 'Untitled tenant';
        $lastActivity = $tenant->users?->max('updated_at');
        $trackedUsers = $tenant->users?->count() ?? 0;
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenant</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Latest known activity for this workspace.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn oh-btn--primary">Tenant overview</a>
                <a href="{{ route('admin.tenants.logins.index') }}" class="oh-btn">All logins</a>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'logins', 'tenant' => $tenant])

        <div class="oh-card border border-border-default/70 rounded-xl p-5 space-y-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Latest activity</p>
                <p class="text-sm text-text-subtle">Based on user activity timestamps.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="oh-stat">
                    <div class="oh-stat__label">Last recorded activity</div>
                    <div class="oh-stat__value">
                        {{ $lastActivity ? $lastActivity->format('M j, Y g:i A') : '—' }}
                    </div>
                </div>
                <div class="oh-stat">
                    <div class="oh-stat__label">Users tracked</div>
                    <div class="oh-stat__value">{{ $trackedUsers }}</div>
                </div>
            </div>
            <p class="text-[11px] text-text-subtle/80">
                Login tracking is not yet enabled; this uses the most recent user activity timestamp.
            </p>
        </div>
    </div>
@endsection
