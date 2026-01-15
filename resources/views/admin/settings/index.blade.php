@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    @php
        $tenantId = auth()->user()->tenant_id ?? null;
        // Fallback status hints; ideally passed from controller
        $status = $settingsStatus ?? [
            'branding_configured' => false,
            'api_keys_count' => 0,
            'tax_configured' => false,
            'subscription_active' => false,
        ];

        $gmailEnabled = (bool) (config('services.google.enable_sync') ?? false);
        $gmailEnvReady = !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')) && !empty(config('services.google.redirect'));
        $mailboxStatus = $gmailEnabled
            ? ($gmailEnvReady ? ['label' => 'Ready', 'variant' => 'oh-pill--success'] : ['label' => 'Not configured', 'variant' => 'oh-pill'])
            : ['label' => 'Disabled', 'variant' => 'oh-pill'];

        $cards = [
            [
                'title' => 'Profile & Branding',
                'description' => 'Update logo, colors, and company info.',
                'href' => $tenantId ? route('tenant.settings.profile', ['tenant' => $tenantId]) : '#',
                'pill' => $status['branding_configured'] ? 'Configured' : 'Not set',
                'pill_variant' => $status['branding_configured'] ? 'oh-pill--success' : 'oh-pill',
            ],
            [
                'title' => 'Clients',
                'description' => 'Set default client type and when to ask.',
                'href' => $tenantId ? route('tenant.settings.clients', ['tenant' => $tenantId]) : '#',
                'pill' => 'Defaults',
                'pill_variant' => 'oh-pill',
            ],
            [
                'title' => 'API Keys',
                'description' => 'Generate keys for integrations.',
                'href' => $tenantId ? route('tenant.settings.api.index', ['tenant' => $tenantId]) : '#',
                'pill' => ($status['api_keys_count'] ?? 0) > 0 ? $status['api_keys_count'] . ' keys active' : 'Not set',
                'pill_variant' => ($status['api_keys_count'] ?? 0) > 0 ? 'oh-pill--info' : 'oh-pill',
            ],
            [
                'title' => 'Subscription Billing',
                'description' => 'Manage plan, billing, and invoices.',
                'href' => $tenantId ? route('tenant.settings.billing', ['tenant' => $tenantId]) : '#',
                'pill' => $status['subscription_active'] ? 'Active' : 'Needs setup',
                'pill_variant' => $status['subscription_active'] ? 'oh-pill--success' : 'oh-pill',
            ],
            [
                'title' => 'Taxes',
                'description' => 'Configure tax rates and collection rules.',
                'href' => $tenantId ? route('tenant.settings.tax.index', ['tenant' => $tenantId]) : '#',
                'pill' => $status['tax_configured'] ? 'Configured' : 'Not set',
                'pill_variant' => $status['tax_configured'] ? 'oh-pill--success' : 'oh-pill',
            ],
            [
                'title' => 'Pinned shortcuts',
                'description' => 'Pick your quick-access links for the sidebar.',
                'href' => $tenantId ? route('tenant.settings.pins', ['tenant' => $tenantId]) : '#',
                'pill' => 'Customize',
                'pill_variant' => 'oh-pill',
            ],
            [
                'title' => 'Mailbox Sync',
                'description' => 'Connect Gmail to log team communications in Renlo.',
                'href' => $tenantId ? route('tenant.settings.mailbox', ['tenant' => $tenantId]) : '#',
                'pill' => $mailboxStatus['label'],
                'pill_variant' => $mailboxStatus['variant'],
            ],
        ];
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Settings</h1>
                    <p class="text-sm text-text-subtle mt-1">Manage workspace profile, billing, API access, and taxes.</p>
                </div>
            </div>
        </div>

        @if (!$tenantId)
            <div class="oh-card border border-amber-200 bg-amber-50 text-amber-800 p-3 text-sm">
                No workspace selected. Please choose a tenant to manage settings.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                @foreach ($cards as $card)
                    <a href="{{ $card['href'] }}"
                        class="oh-card border border-border-default/60 rounded-xl p-5 hover:shadow-card transition focus:outline-none focus:ring-2 focus:ring-brand-primary/50 flex items-start justify-between gap-3">
                        <div class="space-y-2">
                            <div class="text-sm font-semibold text-text-base">{{ $card['title'] }}</div>
                            <div class="text-sm text-text-subtle">{{ $card['description'] }}</div>
                            @if (!empty($card['pill']))
                                <span class="oh-pill {{ $card['pill_variant'] ?? '' }}">{{ $card['pill'] }}</span>
                            @endif
                        </div>
                        <i class="fa-solid fa-chevron-right text-text-subtle text-xs mt-1"></i>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
