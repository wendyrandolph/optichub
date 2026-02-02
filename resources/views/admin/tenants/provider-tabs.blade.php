@php
    $tenantId = $tenant?->id ?? null;
    $items = [
        [
            'label' => 'Tenants',
            'route' => 'admin.tenants.index',
            'active' => $active === 'tenants',
        ],
        [
            'label' => 'Subscriptions',
            'route' => $tenantId ? 'admin.tenants.subscriptions.show' : 'admin.tenants.subscriptions.index',
            'params' => $tenantId ? ['tenant' => $tenantId] : [],
            'active' => $active === 'subscriptions',
        ],
        [
            'label' => 'Usage',
            'route' => $tenantId ? 'admin.tenants.usage.show' : 'admin.tenants.usage.index',
            'params' => $tenantId ? ['tenant' => $tenantId] : [],
            'active' => $active === 'usage',
        ],
        [
            'label' => 'Last Login',
            'route' => $tenantId ? 'admin.tenants.logins.show' : 'admin.tenants.logins.index',
            'params' => $tenantId ? ['tenant' => $tenantId] : [],
            'active' => $active === 'logins',
        ],
        [
            'label' => 'Invoices',
            'route' => $tenantId ? 'admin.tenants.invoices.show' : 'admin.tenants.invoices.index',
            'params' => $tenantId ? ['tenant' => $tenantId] : [],
            'active' => $active === 'invoices',
        ],
    ];
@endphp

<div class="flex flex-wrap gap-2">
    @foreach ($items as $item)
        @php
            $params = $item['params'] ?? [];
        @endphp
        <a href="{{ route($item['route'], $params) }}"
            class="oh-pill text-xs font-semibold transition {{ $item['active'] ? 'is-active' : '' }} hover:bg-[rgb(var(--surface))] hover:text-[rgb(var(--text))] hover:ring-[rgb(var(--brand-primary)/0.35)]"
            aria-current="{{ $item['active'] ? 'page' : 'false' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</div>
