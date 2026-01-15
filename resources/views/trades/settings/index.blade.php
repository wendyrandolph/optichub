@extends('layouts.trades')

@section('title', 'Settings')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $attrs = method_exists($tenant, 'getAttributes') ? $tenant->getAttributes() : [];
        $showProfileBadge = (array_key_exists('name', $attrs) || array_key_exists('logo_path', $attrs))
            && (empty($tenant?->name) || empty($tenant?->logo_path));
        $showTimeBadge = array_key_exists('timezone', $attrs) && empty($tenant?->timezone);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="space-y-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Settings</h1>
            <p class="text-sm text-text-subtle">Configure your business profile, operations, and time policies.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('tenant.trades.settings.profile', ['tenant' => $tenantKey]) }}"
                class="oh-card border border-border-default/70 rounded-2xl p-5 group hover:border-border-default hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-surface-accent flex items-center justify-center text-text-subtle">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-text-base">Business Profile</h2>
                                @if ($showProfileBadge)
                                    <span
                                        class="text-[10px] uppercase tracking-wide rounded-full bg-amber-100 text-amber-700 px-2 py-0.5">Recommended</span>
                                @endif
                            </div>
                            <p class="text-sm text-text-subtle mt-1">
                                Branding, business info, and customer-facing details.
                            </p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-text-subtle group-hover:text-text-base"></i>
                </div>
            </a>

            <a href="{{ route('tenant.trades.settings.operations', ['tenant' => $tenantKey]) }}"
                class="oh-card border border-border-default/70 rounded-2xl p-5 group hover:border-border-default hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-surface-accent flex items-center justify-center text-text-subtle">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-text-base">Operations</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                Templates, service plans, defaults, operational rules.
                            </p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-text-subtle group-hover:text-text-base"></i>
                </div>
            </a>

            <a href="{{ route('tenant.trades.settings.leads', ['tenant' => $tenantKey]) }}"
                class="oh-card border border-border-default/70 rounded-2xl p-5 group hover:border-border-default hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-surface-accent flex items-center justify-center text-text-subtle">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-text-base">Lead Intake</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                Web form mapping, inbox setup, notifications.
                            </p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-text-subtle group-hover:text-text-base"></i>
                </div>
            </a>

            <a href="{{ route('tenant.trades.settings.time', ['tenant' => $tenantKey]) }}"
                class="oh-card border border-border-default/70 rounded-2xl p-5 group hover:border-border-default hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-surface-accent flex items-center justify-center text-text-subtle">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-text-base">Time &amp; Team</h2>
                                @if ($showTimeBadge)
                                    <span
                                        class="text-[10px] uppercase tracking-wide rounded-full bg-amber-100 text-amber-700 px-2 py-0.5">Recommended</span>
                                @endif
                            </div>
                            <p class="text-sm text-text-subtle mt-1">
                                Timezone, shifts, PTO, overtime rules.
                            </p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-text-subtle group-hover:text-text-base"></i>
                </div>
            </a>
        </div>
    </div>
@endsection
