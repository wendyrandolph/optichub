@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    @php
        $tenantId = auth()->user()->tenant_id ?? null;
        $sections = [
            [
                'title' => 'Organization',
                'links' => [
                    [
                        'href' => route('tenant.settings.profile', ['tenant' => $tenantId]),
                        'label' => 'Profile & Branding',
                        'meta' => 'Update logo, colors, and company info',
                    ],
                    [
                        'href' => route('tenant.team-members.index', ['tenant' => $tenantId]),
                        'label' => 'Team Members',
                        'meta' => 'Invite teammates, manage roles',
                    ],
                    [
                        'href' => route('tenant.settings.api.index', ['tenant' => $tenantId]),
                        'label' => 'API Keys',
                        'meta' => 'Generate keys for integrations',
                    ],
                ],
            ],
            [
                'title' => 'Billing',
                'links' => [
                    [
                        'href' => route('tenant.settings.billing', ['tenant' => $tenantId] ?? '#'),
                        'label' => 'Subscription',
                        'meta' => 'View plan, renew or upgrade',
                    ],
                    [
                        'href' => route('tenant.invoices.index', ['tenant' => $tenantId]),
                        'label' => 'Payment History',
                        'meta' => 'Receipts & invoices',
                    ],
                ],
            ],
            [
                'title' => 'Support',
                'links' => [
                    [
                        'href' => '/faq',
                        'label' => 'Help Center',
                        'meta' => 'Guides and walkthroughs',
                    ],
                    [
                        'href' => '/contact?topic=support',
                        'label' => 'Contact Support',
                        'meta' => 'Email the Optic Hub team',
                    ],
                ],
            ],
        ];
    @endphp

    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Settings</h1>

        <div class="grid gap-4 md:grid-cols-2">
            <a class="p-4 rounded-xl border hover:bg-gray-50"
                href="{{ route('tenant.settings.profile', ['tenant' => $tenantId]) }}">
                <div class="font-medium">Profile & Branding</div>
                <div class="text-sm text-gray-600">Logo, colors, company info</div>
            </a>

            <a class="p-4 rounded-xl border hover:bg-gray-50"
                href="{{ route('tenant.settings.billing', ['tenant' => $tenantId]) }}">
                <div class="font-medium">Billing</div>
                <div class="text-sm text-gray-600">Plan & invoices</div>
            </a>

            <a class="p-4 rounded-xl border hover:bg-gray-50"
                href="{{ route('tenant.settings.api.index', ['tenant' => $tenantId]) }}">
                <div class="font-medium">API Keys</div>
                <div class="text-sm text-gray-600">Integrations & webhooks</div>
            </a>
        </div>
    </div>
@endsection
