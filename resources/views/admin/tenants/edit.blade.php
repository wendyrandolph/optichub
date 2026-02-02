@extends('layouts.app')

@section('title', 'Edit Tenant: ' . ($tenant->name ?? 'Workspace'))

@section('content')
    @php
        $name = old('name', $tenant->name ?? 'Untitled workspace');
        $domain = old('domain', $tenant->website ?? null);
        $plan = old('plan_name', $tenant->plan_name ?? ($tenant->subscription_plan ?? ''));
        $status = old('subscription_status', $tenant->subscription_status ?? 'active');
        $primary = old('primary_color', $tenant->primary_color ?: '#273868');
        $secondary = old('secondary_color', $tenant->secondary_color ?: '#5FB4A8');
        $accent = old('accent_color', $tenant->accent_color ?: '#EA7D51');
        $initials = collect(explode(' ', $name))->filter()->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'OH';
        $tenantTz = old('timezone', $tenant->timezone ?? 'America/Denver');
        $timezoneOptions = \DateTimeZone::listIdentifiers();
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-text-subtle">Settings</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit tenant</h1>
                <p class="text-sm text-text-subtle mt-1">Update workspace details, subscription labels, and branding.</p>
            </div>
            <div>
                <a href="{{ route('admin.tenants.index') }}" class="oh-btn">Back to Tenants</a>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-800 p-3 text-sm space-y-1">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="tenantEditForm" method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Workspace details --}}
            <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Workspace details</h2>
                    <p class="text-xs text-text-subtle">Core identity and routing information for this tenant.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Workspace name</span>
                        <input type="text" name="name" value="{{ old('name', $tenant->name) }}" class="oh-input h-10" required>
                        <span class="text-[11px] text-text-subtle">How this tenant appears in your admin tools.</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Public website (optional)</span>
                        <input type="text" name="website" value="{{ $domain }}" placeholder="https://example.com" class="oh-input h-10">
                        <span class="text-[11px] text-text-subtle">Context only. Leave blank if not needed.</span>
                    </label>
                    <label class="grid gap-1 text-sm sm:col-span-2">
                        <span class="text-text-subtle">Timezone</span>
                        <select name="timezone" class="oh-select h-10">
                            <option value="">Use default ({{ config('app.timezone') }})</option>
                            @foreach ($timezoneOptions as $tz)
                                <option value="{{ $tz }}" @selected($tenantTz === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-text-subtle">Used for date/time displays and reminders.</span>
                    </label>
                </div>
            </div>

            {{-- Subscription --}}
            <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Subscription</h2>
                    <p class="text-xs text-text-subtle">Updates admin labels and current subscription metadata.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Status</span>
                        <select name="subscription_status" class="oh-select h-10">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-text-subtle">Used for admin reporting and access checks.</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Plan</span>
                        <select name="plan_name" class="oh-select h-10">
                            <option value="">Not set</option>
                            @foreach ($planOptions as $value => $label)
                                <option value="{{ $value }}" @selected($plan === $value || $plan === $label)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-text-subtle">Friendly label used in admin views.</span>
                    </label>
                    <div class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Internal ID</span>
                        <div class="oh-input h-10 flex items-center bg-surface-card border border-border-default text-text-base">
                            #{{ $tenant->id }}
                        </div>
                        <span class="text-[11px] text-text-subtle">Auto-generated identifier.</span>
                    </div>
                </div>
            </div>

            {{-- Branding --}}
            <div class="oh-card border border-border-default/70 rounded-2xl p-4 md:p-5 space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Branding</h2>
                    <p class="text-xs text-text-subtle">Colors used inside their Renlo workspace.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ([['label' => 'Primary color', 'name' => 'primary_color', 'val' => $primary], ['label' => 'Secondary color', 'name' => 'secondary_color', 'val' => $secondary], ['label' => 'Accent color', 'name' => 'accent_color', 'val' => $accent]] as $color)
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">{{ $color['label'] }}</span>
                            <div class="flex items-center gap-2">
                                <span class="h-8 w-8 rounded-full ring-1 ring-border-default/60" style="background: {{ $color['val'] }}"></span>
                                <input type="text" name="{{ $color['name'] }}" value="{{ $color['val'] }}" class="oh-input h-10">
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border-default/60">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
