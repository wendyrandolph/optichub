@extends('layouts.app')

@section('title', 'Tenant: ' . ($tenant->name ?? 'Workspace'))

@section('content')
    @php
        $name = $tenant->name ?? 'Untitled workspace';
        $domain = $tenant->website ?? ($tenant->domain ?? null);
        $plan = $tenant->plan_name ?? ($tenant->subscription_plan ?? 'Not set');
        $created = optional($tenant->created_at)->format('M j, Y') ?? '—';
        $status = strtolower($tenant->subscription_status ?? 'inactive');
        $statusLabel = match ($status) {
            'active' => 'Active',
            'trialing' => 'Trialing',
            'paused' => 'Paused',
            'canceled' => 'Canceled',
            default => ucfirst($status),
        };
        $primary = $tenant->primary_color ?: '#273868';
        $secondary = $tenant->secondary_color ?: '#5FB4A8';
        $accent = $tenant->accent_color ?: '#EA7D51';
        $initials = collect(explode(' ', $name))->filter()->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'OH';
        $hasBranding = !empty($tenant->primary_color) || !empty($tenant->secondary_color) || !empty($tenant->accent_color) || !empty($tenant->logo_path);
        $stats = $stats ?? ['clients' => 0, 'projects' => 0, 'invoices' => 0, 'users' => 0];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Workspace details, subscription status, and branding.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.index') }}" class="oh-btn">Back</a>
                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="oh-btn oh-btn--primary">Edit tenant</a>
            </div>
        </div>

        {{-- Summary --}}
        <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl grid place-items-center font-semibold text-white"
                        style="background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }});">
                        {{ $initials }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="oh-pill">{{ $statusLabel }}</span>
                            <span class="oh-pill">Plan: {{ $plan }}</span>
                            <span class="oh-pill">Since {{ $created }}</span>
                        </div>
                        @if ($domain)
                            <p class="text-sm text-text-subtle truncate">{{ $domain }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Metrics & Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-3 lg:col-span-2">
                <h2 class="text-sm font-semibold text-text-base">Activity</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="oh-card border border-border-default/60 rounded-xl p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Clients</p>
                        <p class="text-xl font-semibold text-text-base">{{ $stats['clients'] ?? 0 }}</p>
                    </div>
                    <div class="oh-card border border-border-default/60 rounded-xl p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Projects</p>
                        <p class="text-xl font-semibold text-text-base">{{ $stats['projects'] ?? 0 }}</p>
                    </div>
                    <div class="oh-card border border-border-default/60 rounded-xl p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Invoices</p>
                        <p class="text-xl font-semibold text-text-base">{{ $stats['invoices'] ?? 0 }}</p>
                    </div>
                    <div class="oh-card border border-border-default/60 rounded-xl p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">Users</p>
                        <p class="text-xl font-semibold text-text-base">{{ $stats['users'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-2">
                <h2 class="text-sm font-semibold text-text-base">Details</h2>
                <dl class="text-sm text-text-subtle space-y-1">
                    <div class="flex items-center justify-between">
                        <dt>Workspace name</dt>
                        <dd class="text-text-base font-semibold">{{ $name }}</dd>
                    </div>
                    @if ($domain)
                        <div class="flex items-center justify-between">
                            <dt>Domain</dt>
                            <dd class="text-text-base">{{ $domain }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt>Status</dt>
                        <dd><span class="oh-pill">{{ $statusLabel }}</span></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Plan</dt>
                        <dd class="text-text-base">{{ $plan }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Created</dt>
                        <dd class="text-text-base">{{ $created }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Branding --}}
        <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Branding &amp; Theme</h2>
                    <p class="text-xs text-text-subtle">How this workspace appears to their team and clients.</p>
                </div>
                <span class="oh-pill">{{ $hasBranding ? 'Custom branding' : 'Default branding' }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Colors --}}
                <div class="space-y-3">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Colors</p>
                    <div class="flex items-center gap-3">
                        @foreach ([['label' => 'Primary', 'val' => $primary], ['label' => 'Secondary', 'val' => $secondary], ['label' => 'Accent', 'val' => $accent]] as $s)
                            <div class="flex flex-col items-center gap-1">
                                <span class="h-8 w-8 rounded-full ring-1 ring-border-default/60" style="background: {{ $s['val'] }};"></span>
                                <span class="text-[11px] text-text-subtle">{{ $s['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Logo / Identity --}}
                <div class="space-y-3">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Identity</p>
                    <div class="flex items-center gap-3">
                        @if (!empty($tenant->logo_path))
                            <div class="p-2">
                                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $name }} logo"
                                    class="h-10 w-auto object-contain">
                            </div>
                        @else
                            <div class="h-10 w-10 rounded-lg grid place-items-center font-semibold text-white"
                                style="background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }});">
                                {{ $initials }}
                            </div>
                        @endif
                        <div class="text-xs">
                            <p class="font-medium text-text-base">{{ $name }}</p>
                            @if ($domain)
                                <p class="text-text-subtle">{{ $domain }}</p>
                            @else
                                <p class="italic text-text-subtle">No public site on file</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Mini preview --}}
                <div class="space-y-2">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">UI preview</p>
                    <div class="rounded-xl ring-1 ring-border-default/60 overflow-hidden shadow-sm">
                        <div class="h-8 px-3 flex items-center justify-between text-[11px]"
                            style="background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }}); color:white;">
                            <span class="font-medium truncate">{{ $name }} · Portal</span>
                            <span class="opacity-80">Client view</span>
                        </div>
                        <div class="p-3 space-y-2 bg-surface-card">
                            <div class="h-2.5 w-24 rounded-full" style="background-color: {{ $primary }};"></div>
                            <div class="h-1.5 w-40 rounded-full ring-1 ring-border-default/70 bg-surface-muted"></div>
                            <div class="grid grid-cols-3 gap-2 mt-2">
                                <div class="h-8 rounded-lg" style="background-color: {{ $secondary }};"></div>
                                <div class="h-8 rounded-lg ring-1 ring-border-default/60 bg-surface-card"></div>
                                <div class="h-8 rounded-lg" style="background-color: {{ $accent }};"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
