@extends('layouts.app')

@section('title', 'Tenant Usage')

@section('content')
    @php
        $name = $tenant->name ?? 'Untitled tenant';
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Tenant</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Usage metrics for this workspace.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn oh-btn--primary">Tenant overview</a>
                <a href="{{ route('admin.tenants.usage.index') }}" class="oh-btn">All usage</a>
            </div>
        </div>

        @include('admin.tenants.provider-tabs', ['active' => 'usage', 'tenant' => $tenant])

        <div class="space-y-3">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Usage totals</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Users</p>
                <p class="text-xl font-semibold text-text-base">{{ $tenant->users_count ?? 0 }}</p>
                </div>
                <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Projects</p>
                <p class="text-xl font-semibold text-text-base">{{ $tenant->projects_count ?? 0 }}</p>
                </div>
                <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Clients</p>
                <p class="text-xl font-semibold text-text-base">{{ $tenant->clients_count ?? 0 }}</p>
                </div>
                <div class="oh-card border border-border-default/70 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Invoices</p>
                <p class="text-xl font-semibold text-text-base">{{ $tenant->invoices_count ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
