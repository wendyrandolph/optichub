@extends('layouts.app')

@section('title', 'Settings — Profile & Branding')

@section('content')
    @php
        $org = $organization ?? [
            'name' => '',
            'website' => '',
            'phone' => '',
            'support_email' => '',
            'primary_color' => '',
            'secondary_color' => '',
            'logo_url' => '',
        ];
        $tenantId = $tenant->id ?? (auth()->user()->tenant_id ?? request()->route('tenant'));
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page header --}}
        <div class="mb-6">
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Profile & Branding</h1>
            <p class="text-sm text-text-subtle mt-1">Update your organization details and how Optic Hub appears to your team.
            </p>
        </div>

        {{-- Alerts --}}
        @if (session('flash_success'))
            <div class="mb-4 rounded-lg border border-green-300/60 bg-green-50 text-green-800 px-4 py-3 text-sm">
                {{ session('flash_success') }}
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="mb-4 rounded-lg border border-rose-300/60 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                {{ $errors->first('general') }}
            </div>
        @endif

        {{-- Card --}}
        <form method="POST" action="{{ route('tenant.settings.profile.update', ['tenant' => $tenantId]) }}"
            enctype="multipart/form-data" class="rounded-2xl border border-border-default bg-surface-card shadow-card">
            @csrf

            {{-- Organization --}}
            <div class="px-6 py-5 border-b border-border-default/70">
                <h2 class="text-base font-semibold text-text-base">Organization</h2>
                <p class="text-sm text-text-subtle">Basic information shown across your workspace.</p>
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Name</span>
                        <input id="org_name" name="name" required value="{{ old('name', $org['name'] ?? '') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 @error('name') ring-2 ring-rose-300 @enderror">
                        @error('name')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Website</span>
                        <input id="org_website" name="website" placeholder="https://example.com"
                            value="{{ old('website', $org['website'] ?? '') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 @error('website') ring-2 ring-rose-300 @enderror">
                        @error('website')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Phone</span>
                        <input id="org_phone" name="phone" placeholder="(555) 123-4567"
                            value="{{ old('phone', $org['phone'] ?? '') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 @error('phone') ring-2 ring-rose-300 @enderror">
                        @error('phone')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Support Email</span>
                        <input id="org_support_email" name="support_email" placeholder="support@company.com"
                            value="{{ old('support_email', $org['support_email'] ?? '') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 @error('support_email') ring-2 ring-rose-300 @enderror">
                        @error('support_email')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>
                </div>
            </div>

            {{-- Branding --}}
            <div class="px-6 py-5 border-t border-border-default/70">
                <h2 class="text-base font-semibold text-text-base">Branding</h2>
                <p class="text-sm text-text-subtle">Customize the workspace appearance. (More options coming soon.)</p>
            </div>

            <div class="px-6 pb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Logo upload --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Logo</span>
                        <input type="file" name="logo" accept="image/*"
                            class="block w-full text-text-base file:mr-3 file:rounded-md file:border file:border-border-default file:bg-surface-accent file:px-3 file:py-1.5 file:text-sm file:text-text-base">
                        @if (!empty($org['logo_url']))
                            <span class="text-xs text-text-subtle mt-1">Current:</span>
                            <img src="{{ $org['logo_url'] }}" alt="Current logo"
                                class="mt-1 h-10 w-auto rounded-md border border-border-default bg-surface-card">
                        @endif
                    </label>

                    {{-- (Optional) brand colors – keep inputs but not required --}}
                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Primary color</span>
                            <input type="color" name="primary_color"
                                value="{{ old('primary_color', $org['primary_color'] ?: '#2E5D95') }}"
                                class="h-10 w-16 rounded-md border border-border-default bg-surface-card">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Secondary color</span>
                            <input type="color" name="secondary_color"
                                value="{{ old('secondary_color', $org['secondary_color'] ?: '#679CD5') }}"
                                class="h-10 w-16 rounded-md border border-border-default bg-surface-card">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div
                class="px-6 py-4 border-t border-border-default/70 flex items-center justify-end gap-3 rounded-b-2xl bg-surface-accent">
                <a href="{{ route('tenant.settings.index', ['tenant' => $tenantId]) }}"
                    class="inline-flex items-center h-10 px-4 rounded-lg text-sm font-medium border border-border-default bg-surface-card text-text-base hover:brightness-110">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center h-10 px-4 rounded-lg text-sm font-medium text-white bg-gradient-to-b from-brand-primary to-blue-700 shadow-card hover:brightness-110">
                    Save changes
                </button>
            </div>
        </form>
    </div>
@endsection
