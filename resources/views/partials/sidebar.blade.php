@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;
    use App\Models\Tenant;
    use App\Models\UserPreference;

    /**
     * -----------------------------
     * 1) Identity (GUARD FIRST)
     * -----------------------------
     */
    $isAdminGuard = auth('admin')->check(); // provider/platform users
    $isWebGuard = auth()->check(); // tenant workspace users

    $currentUser = auth('admin')->user() ?? auth()->user();

    // Platform owner = Renlo app owner (your method)
    $isPlatformOwner = method_exists($currentUser, 'isPlatformOwner') ? $currentUser->isPlatformOwner() : false;
    // Provider team access to platform tenant tools
    $isProviderAdmin = method_exists($currentUser, 'isProviderAdmin') ? $currentUser->isProviderAdmin() : false;
    $canManageTenants = $isAdminGuard && ($isPlatformOwner || $isProviderAdmin);
    $canManageTenantsWeb = $isWebGuard && ($isPlatformOwner || $isProviderAdmin);

    /**
     * -----------------------------
     * 2) Tenant param resolution
     * -----------------------------
     */
    $routeTenant = request()->route('tenant');
    $tenantId = null;

    if ($routeTenant instanceof Tenant) {
        $tenantId = $routeTenant->getKey();
    } elseif (is_numeric($routeTenant)) {
        $tenantId = (int) $routeTenant;
    } elseif (is_string($routeTenant) && $routeTenant !== '') {
        $tenantId = (int) preg_replace('/\D+/', '', $routeTenant) ?: null;
    }

    if (!$tenantId && $isWebGuard) {
        $tenantId = $currentUser?->tenant_id ?? session('tenant_id');
    }

    $tenantParam = $tenantId ?: null;

    /**
     * -----------------------------
     * 3) URL resolver (FIXED)
     * -----------------------------
     * Inject {tenant} automatically for ANY route that needs it,
     * not only routes that start with "tenant."
     */
    $resolveHref = function (array $item, $tenantParam) {
        if (!empty($item['href'])) {
            return $item['href'];
        }

        // allow raw paths or absolute urls in 'route'
        if (
            !empty($item['route']) &&
            (str_starts_with($item['route'], '/') || str_starts_with($item['route'], 'http'))
        ) {
            return $item['route'];
        }

        if (!empty($item['route']) && Route::has($item['route'])) {
            $params = $item['params'] ?? [];

            try {
                $routeObj = Route::getRoutes()->getByName($item['route']);
                $needsTenant = $routeObj && in_array('tenant', $routeObj->parameterNames(), true);

                if ($needsTenant && !array_key_exists('tenant', $params)) {
                    // If we don't have tenant context, fail gracefully
                if (!$tenantParam) {
                    return '#';
                }
                $params['tenant'] = $tenantParam;
            }

            return route($item['route'], $params);
        } catch (\Throwable $e) {
            return '#';
        }
    }

    return '#';
};

/**
 * -----------------------------
 * 4) Role flags (simple)
 * -----------------------------
 */
$role = Str::of($currentUser?->role ?? '')
    ->lower()
    ->toString();

// Provider-side sidebar only for admin guard users
$isProviderAdmin = $isAdminGuard;
$isOperationsAdmin = $isAdminGuard && in_array($role, ['admin', 'super_admin', 'superadmin'], true);

// Tenant-side sidebar only for web guard users
$isTenantAdmin = $isWebGuard && $role === 'admin';
$isTenantEmployee = $isWebGuard && $role === 'employee';
$isTenantClient = $isWebGuard && $role === 'client';

$registeredUsersEnabled = null;
if ($tenantId) {
    $tenantModel = Tenant::find($tenantId);
    $registeredUsersEnabled = $tenantModel?->registered_users_enabled;
}
$registeredUsersEnabled = $registeredUsersEnabled === null ? true : (bool) $registeredUsersEnabled;
$canViewRegisteredUsers = (bool) ($currentUser?->can_view_registered_users ?? false);

/**
 * -----------------------------
 * 5) Active helpers
 * -----------------------------
 */
$currentPath = request()->path();

$isItemActive = function (array $item, string $currentPath, callable $resolveHref, ?string $tenantParam): bool {
    if (!empty($item['match']) && request()->routeIs($item['match'])) {
        return true;
    }
    if (!empty($item['route']) && request()->routeIs($item['route'])) {
        return true;
    }

    $hasNamedRoute = !empty($item['route']) && Route::has($item['route']);
    if ($hasNamedRoute) {
        return false;
    }

    $href = $item['__href'] ?? $resolveHref($item, $tenantParam);
    $current = trim($currentPath, '/');
    $target = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');

    return $target !== '' && ($target === $current || str_starts_with($current, $target));
};

$isSectionActive = function (array $sectionItems) use (
    $isItemActive,
    $currentPath,
    $resolveHref,
    $tenantParam,
): bool {
    foreach ($sectionItems as $it) {
        if ($isItemActive($it, $currentPath, $resolveHref, $tenantParam)) {
            return true;
        }
    }
    return false;
};

$sectionIcons = [
    'Operations' => 'fa-gauge-high',
    'Work' => 'fa-list-check',
    'Sales' => 'fa-chart-line',
    'Communication' => 'fa-envelope',
    'Team' => 'fa-user-tag',
    'Billing & Scheduling' => 'fa-file-invoice',
    'Billing' => 'fa-file-invoice',
    'Settings' => 'fa-sliders',
];

/**
 * -----------------------------
 * 6) Pinned items (UPDATED ROUTE NAMES)
 * -----------------------------
 * Match your current route:list:
 * projects.index, tasks.index, calendar.index, time.index, dashboards.index, etc.
 */
$pinnedOptions = [
    'tenant.tasks.index' => ['icon' => 'fa-list-check', 'label' => 'Tasks', 'match' => 'tenant.tasks.*'],
    'tenant.projects.index' => ['icon' => 'fa-lightbulb', 'label' => 'Projects', 'match' => 'tenant.projects.*'],
    'tenant.calendar.index' => [
        'icon' => 'fa-calendar-days',
        'label' => 'Calendar',
        'match' => 'tenant.calendar.*',
    ],
    'tenant.time.index' => ['icon' => 'fa-clock', 'label' => 'Time', 'match' => 'tenant.time.*'],
    'tenant.invoices.index' => [
        'icon' => 'fa-file-invoice',
        'label' => 'Client Invoices',
        'match' => 'tenant.invoices.*',
    ],
    'tenant.contacts.index' => ['icon' => 'fa-user-group', 'label' => 'Contacts', 'match' => 'tenant.contacts.*'],
    'tenant.companies.index' => [
        'icon' => 'fa-building',
        'label' => 'Client Companies',
        'match' => 'tenant.companies.*',
    ],
    'tenant.opportunities.index' => [
        'icon' => 'fa-chart-line',
        'label' => 'Opportunities',
        'match' => 'tenant.opportunities.*',
    ],
    'tenant.leads.index' => ['icon' => 'fa-user-plus', 'label' => 'Lead Manager', 'match' => 'tenant.leads.*'],
    'tenant.emails.index' => [
        'icon' => 'fa-envelope',
        'label' => 'Emails',
        'match' => ['tenant.emails.index', 'tenant.emails.show', 'tenant.emails.edit'],
    ],
    'tenant.emails.create' => [
        'icon' => 'fa-paper-plane',
        'label' => 'Compose Email',
        'match' => ['tenant.emails.create', 'tenant.emails.store'],
    ],
    'tenant.dashboards.index' => [
        'icon' => 'fa-chart-simple',
        'label' => 'Dashboard',
        'match' => 'tenant.dashboards.*',
    ],
];

$defaultPinned = [
    'tenant.dashboards.index',
    'tenant.tasks.index',
    'tenant.projects.index',
    'tenant.invoices.index',
];

$pinnedPref = $currentUser
    ? UserPreference::query()
        ->where('user_id', $currentUser->id)
        ->when($tenantParam, fn($q) => $q->where('tenant_id', $tenantParam))
        ->first()
    : null;

$pinnedRoutes = (array) ($pinnedPref?->pinned_nav ?? []);
if (empty($pinnedRoutes)) {
    $pinnedRoutes = $defaultPinned;
}
$pinnedRoutes = array_values(array_unique(array_filter($pinnedRoutes, fn($r) => isset($pinnedOptions[$r]))));
if (count($pinnedRoutes) < 4) {
    foreach ($defaultPinned as $routeName) {
        if (count($pinnedRoutes) >= 4) {
            break;
        }
        if (!in_array($routeName, $pinnedRoutes, true)) {
            $pinnedRoutes[] = $routeName;
        }
    }
}
$pinnedRoutes = array_slice($pinnedRoutes, 0, 4);

$pinnedItems = [];
foreach ($pinnedRoutes as $routeName) {
    $pinnedItems[] = [
        'route' => $routeName,
        'icon' => $pinnedOptions[$routeName]['icon'],
        'label' => $pinnedOptions[$routeName]['label'],
    ];
}

/**
 * -----------------------------
 * 7) Build nav sections (NO DUPES)
 * -----------------------------
 */
$providerTenantSection = [];
if ($canManageTenants || $canManageTenantsWeb) {
    $providerTenantSection = [
        'label' => 'Tenants',
        'items' => [
            ['route' => 'admin.tenants.index', 'icon' => 'fa-building-user', 'label' => 'Renlo Tenants'],
            [
                'route' => 'admin.tenants.subscriptions.index',
                'icon' => 'fa-credit-card',
                'label' => 'Subscriptions',
            ],
            ['route' => 'admin.tenants.usage.index', 'icon' => 'fa-chart-line', 'label' => 'Usage'],
            ['route' => 'admin.tenants.logins.index', 'icon' => 'fa-user-clock', 'label' => 'Last Login'],
            [
                'route' => 'admin.tenants.invoices.index',
                'icon' => 'fa-file-invoice-dollar',
                'label' => 'Invoices',
            ],
            ['route' => 'admin.tenants.users.index', 'icon' => 'fa-users', 'label' => 'Registered Users'],
        ],
    ];
}

$navSections = [];

if ($isProviderAdmin && !$isWebGuard) {
    $canManageSupport = $isProviderAdmin || (bool) ($currentUser?->can_manage_support ?? false);
    $operationsItems = [
        ['route' => 'admin.dashboard', 'icon' => 'fa-chart-simple', 'label' => 'Admin Dashboard'],
        ['route' => 'admin.activity.index', 'icon' => 'fa-rectangle-list', 'label' => 'Activity Logs'],
    ];
    if ($tenantParam) {
        $operationsItems[] = [
            'route' => 'tenant.reports.index',
            'icon' => 'fa-chart-line',
            'label' => 'Reports',
            'match' => 'tenant.reports.*',
        ];
        $operationsItems[] = [
            'route' => 'tenant.dashboards.index',
            'icon' => 'fa-chart-area',
            'label' => 'Lead Insights',
            'match' => 'tenant.dashboards.index',
        ];
    }
    $operationsItems = array_values(array_filter($operationsItems));

    $supportSection = null;
    if ($canManageSupport) {
        $supportSection = [
            'label' => 'Support',
            'items' => [
                [
                    'route' => 'admin.support.index',
                    'icon' => 'fa-life-ring',
                    'label' => 'Support Inbox',
                    'match' => 'admin.support.*',
                ],
                [
                    'route' => 'admin.support.kb.index',
                    'icon' => 'fa-book',
                    'label' => 'Knowledge Base',
                    'match' => 'admin.support.kb.*',
                ],
            ],
        ];
    }

    // Provider can show tenant tools ONLY if tenant context exists
    $tenantWorkSections = [];
    if ($tenantParam) {
        $tenantWorkSections = [
            [
                'label' => 'Work',
                'items' => [
                    ['route' => 'tenant.projects.index', 'icon' => 'fa-lightbulb', 'label' => 'Projects'],
                    [
                        'route' => 'tenant.tasks.index',
                        'icon' => 'fa-list-check',
                        'label' => 'Tasks',
                        'match' => 'tenant.tasks.*',
                    ],
                    [
                        'route' => 'tenant.schedule.index',
                        'icon' => 'fa-calendar-check',
                        'label' => 'My Schedule',
                        'match' => 'tenant.schedule.*',
                    ],
                    [
                        'route' => 'tenant.calendar.index',
                        'icon' => 'fa-calendar-days',
                        'label' => 'Calendar',
                        'match' => 'tenant.calendar.*',
                    ],
                    [
                        'route' => 'tenant.time.index',
                        'icon' => 'fa-clock',
                        'label' => 'Time',
                        'match' => 'tenant.time.*',
                    ],
                ],
            ],
            [
                'label' => 'Sales',
                'items' => [
                    [
                        'route' => 'tenant.leads.index',
                        'icon' => 'fa-user-plus',
                        'label' => 'Lead Manager',
                        'match' => 'tenant.leads.*',
                    ],
                    [
                        'route' => 'tenant.opportunities.index',
                        'icon' => 'fa-chart-line',
                        'label' => 'Opportunities',
                        'match' => 'tenant.opportunities.*',
                    ],
                    [
                        'route' => 'tenant.proposals.index',
                        'icon' => 'fa-file-signature',
                        'label' => 'Proposals',
                        'match' => 'tenant.proposals.*',
                    ],
                    [
                        'route' => 'tenant.companies.index',
                        'icon' => 'fa-building',
                        'label' => 'Client Companies',
                        'match' => 'tenant.companies.*',
                    ],
                    [
                        'route' => 'tenant.contacts.index',
                        'icon' => 'fa-user-group',
                        'label' => 'Contacts',
                        'match' => 'tenant.contacts.*',
                    ],
                ],
            ],
            [
                'label' => 'Templates',
                'items' => [
                    [
                        'route' => 'tenant.proposal-templates.index',
                        'icon' => 'fa-file-lines',
                        'label' => 'Proposal Templates',
                        'match' => 'tenant.proposal-templates.*',
                    ],
                    [
                        'route' => 'tenant.contracts.templates.index',
                        'icon' => 'fa-file-signature',
                        'label' => 'Contract Templates',
                        'match' => 'tenant.contracts.templates.*',
                    ],
                ],
            ],
            [
                'label' => 'Communication',
                'items' => [
                    [
                        'route' => 'tenant.chat.index',
                        'icon' => 'fa-comments',
                        'label' => 'Team Chat',
                        'match' => 'tenant.chat.*',
                    ],
                    [
                        'route' => 'tenant.emails.index',
                        'icon' => 'fa-envelope',
                        'label' => 'Emails',
                        'match' => ['tenant.emails.index', 'tenant.emails.show', 'tenant.emails.edit'],
                    ],
                    [
                        'route' => 'tenant.emails.create',
                        'icon' => 'fa-paper-plane',
                        'label' => 'Compose Email',
                        'match' => ['tenant.emails.create', 'tenant.emails.store'],
                    ],
                ],
            ],
            [
                'label' => 'Team',
                'items' => [
                    [
                        'route' => 'tenant.team-members.index',
                        'icon' => 'fa-user-tag',
                        'label' => 'Team Members',
                        'match' => 'tenant.team-members.*',
                    ],
                ],
            ],
            [
                'label' => 'Billing & Scheduling',
                'items' => [
                    [
                        'route' => 'tenant.invoices.index',
                        'icon' => 'fa-file-invoice',
                        'label' => 'Client Invoices',
                        'match' => 'tenant.invoices.*',
                    ],
                ],
            ],
            [
                'label' => 'Settings',
                'items' => [
                    [
                        'route' => 'tenant.settings.index',
                        'icon' => 'fa-sliders',
                        'label' => 'Settings Overview',
                        'match' => 'tenant.settings.index',
                    ],
                ],
            ],
        ];
    }

    $navSections = array_merge(
        [['label' => 'Operations', 'items' => $operationsItems]],
        $supportSection ? [$supportSection] : [],
        $providerTenantSection ? [$providerTenantSection] : [],
        $tenantWorkSections,
    );
} elseif ($isWebGuard && !$isTenantClient) {
    // Tenant workspace sidebar
    $navSections = array_values(array_filter([
        $providerTenantSection ?: null,
        [
            'label' => 'Operations',
            'items' => [
                [
                    'route' => 'tenant.dashboards.index',
                    'icon' => 'fa-gauge-high',
                    'label' => 'Dashboard',
                    'match' => 'tenant.dashboards.*',
                ],
                [
                    'route' => 'tenant.reports.index',
                    'icon' => 'fa-chart-column',
                    'label' => 'Reports',
                    'match' => 'tenant.reports.*',
                ],
                [
                    'route' => 'tenant.dashboards.leads',
                    'icon' => 'fa-magnifying-glass-chart',
                    'label' => 'Lead Insights',
                    'match' => 'tenant.dashboards.leads',
                ],
                Route::has('admin.activity.index')
                    ? [
                        'route' => 'admin.activity.index',
                        'icon' => 'fa-rectangle-list',
                        'label' => 'Activity Logs',
                    ]
                    : null,
                // Removed "Reports" because your reports are currently admin/{tenant}/reports...
            ],
        ],
        [
            'label' => 'Support',
            'items' => [
                [
                    'route' => 'tenant.support.index',
                    'icon' => 'fa-life-ring',
                    'label' => 'Support Center',
                    'match' => 'tenant.support.*',
                ],
            ],
        ],
        [
            'label' => 'Work',
            'items' => [
                [
                    'route' => 'tenant.projects.index',
                    'icon' => 'fa-lightbulb',
                    'label' => 'Projects',
                    'match' => 'tenant.projects.*',
                ],
                [
                    'route' => 'tenant.tasks.index',
                    'icon' => 'fa-list-check',
                    'label' => 'Tasks',
                    'match' => 'tenant.tasks.*',
                ],
                [
                    'route' => 'tenant.schedule.index',
                    'icon' => 'fa-calendar-check',
                    'label' => 'My Schedule',
                    'match' => 'tenant.schedule.*',
                ],
                [
                    'route' => 'tenant.calendar.index',
                    'icon' => 'fa-calendar-days',
                    'label' => 'Calendar',
                    'match' => 'tenant.calendar.*',
                ],
                [
                    'route' => 'tenant.time.index',
                    'icon' => 'fa-clock',
                    'label' => 'Time',
                    'match' => 'tenant.time.*',
                ],
            ],
        ],
        [
            'label' => 'Sales',
            'items' => [
                [
                    'route' => 'tenant.companies.index',
                    'icon' => 'fa-building',
                    'label' => 'Client Companies',
                    'match' => 'tenant.companies.*',
                ],
                [
                    'route' => 'tenant.contacts.index',
                    'icon' => 'fa-user-group',
                    'label' => 'Contacts',
                    'match' => 'tenant.contacts.*',
                ],
                [
                    'route' => 'tenant.opportunities.index',
                    'icon' => 'fa-chart-line',
                    'label' => 'Opportunities',
                    'match' => 'tenant.opportunities.*',
                ],
                [
                    'route' => 'tenant.proposals.index',
                    'icon' => 'fa-file-signature',
                    'label' => 'Proposals',
                    'match' => 'tenant.proposals.*',
                ],
                [
                    'route' => 'tenant.leads.index',
                    'icon' => 'fa-user-plus',
                    'label' => 'Lead Manager',
                    'match' => 'tenant.leads.*',
                ],
            ],
        ],
        [
            'label' => 'Templates',
            'items' => [
                [
                    'route' => 'tenant.proposal-templates.index',
                    'icon' => 'fa-file-lines',
                    'label' => 'Proposal Templates',
                    'match' => 'tenant.proposal-templates.*',
                ],
                [
                    'route' => 'tenant.contracts.templates.index',
                    'icon' => 'fa-file-signature',
                    'label' => 'Contract Templates',
                    'match' => 'tenant.contracts.templates.*',
                ],
            ],
        ],
        [
            'label' => 'Communication',
            'items' => [
                [
                    'route' => 'tenant.chat.index',
                    'icon' => 'fa-comments',
                    'label' => 'Team Chat',
                    'match' => 'tenant.chat.*',
                ],
                [
                    'route' => 'tenant.emails.index',
                    'icon' => 'fa-envelope',
                    'label' => 'Emails',
                    'match' => ['tenant.emails.index', 'tenant.emails.show', 'tenant.emails.edit'],
                ],
                [
                    'route' => 'tenant.emails.create',
                    'icon' => 'fa-paper-plane',
                    'label' => 'Compose Email',
                    'match' => ['tenant.emails.create', 'tenant.emails.store'],
                ],
            ],
        ],
        [
            'label' => 'Team',
            'items' => [
                [
                    'route' => 'tenant.team-members.index',
                    'icon' => 'fa-user-tag',
                    'label' => 'Team Members',
                    'match' => 'tenant.team-members.*',
                ],
            ],
        ],
        [
            'label' => 'Billing',
            'items' => [
                [
                    'route' => 'tenant.invoices.index',
                    'icon' => 'fa-file-invoice',
                    'label' => 'Client Invoices',
                    'match' => 'tenant.invoices.*',
                ],
            ],
        ],
        [
            'label' => 'Settings',
            'items' => [
                [
                    'route' => 'tenant.settings.index',
                    'icon' => 'fa-sliders',
                    'label' => 'Settings Overview',
                    'match' => 'tenant.settings.index',
                ],
            ],
        ],
    ]));

    $navSections = array_map(function (array $section) {
        $section['items'] = array_values(array_filter($section['items']));
        return $section;
    }, $navSections);

    if ($isTenantEmployee) {
        $navSections = array_values(
            array_filter($navSections, fn($section) => ($section['label'] ?? '') !== 'Operations'),
        );
    }
} elseif ($isTenantClient) {
    $navSections = [];
}

if (empty($navSections) && !$isTenantClient) {
    $navSections = [
        [
            'label' => 'Dashboard',
            'items' => [['href' => '/dashboard', 'icon' => 'fa-gauge', 'label' => 'Dashboard']],
            ],
        ];
    }
@endphp

@php
    if ($isTenantClient) {
        $pinnedItems = [];
    }

    $pinnedItems = array_values(array_filter($pinnedItems, fn($item) => $resolveHref($item, $tenantParam) !== '#'));
@endphp

@if (!empty($navSections) || !empty($pinnedItems))
    <aside id="sidebar"
        class="oh-sidebar fixed inset-y-0 left-0 z-50 w-64 -translate-x-full transition-transform duration-200 ease-out
               lg:translate-x-0 lg:flex lg:flex-col lg:fixed">
        <div class="oh-sidebar__inner h-full flex flex-col gap-3 p-3">
            <div class="flex items-center justify-end">
                <button id="sidebar-toggle" type="button" class="oh-sidebar__toggle" aria-label="Toggle sidebar">
                    <i class="fas fa-chevron-left text-sm"></i>
                </button>
            </div>

            @if (!empty($pinnedItems))
                <div class="oh-sidebar__pinned">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($pinnedItems as $item)
                            @php
                                $href = $resolveHref($item, $tenantParam);
                                $isActive = $isItemActive($item, $currentPath, $resolveHref, $tenantParam);
                            @endphp
                            <a href="{{ $href }}" data-label="{{ $item['label'] }}" title="{{ $item['label'] }}"
                                class="oh-sidebar__link oh-sidebar__link--icon flex items-center justify-center {{ $isActive ? 'is-active' : '' }}">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span class="sr-only">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <nav class="sidebar-nav flex-1 space-y-3">
                @foreach ($navSections as $sectionIndex => $section)
                    @php
                        $sectionId = 'sidebar-section-' . $sectionIndex;
                        $isOpen = $isSectionActive($section['items'] ?? []);
                        $isOperations = ($section['label'] ?? '') === 'Operations';
                        $sectionIcon = $sectionIcons[$section['label'] ?? ''] ?? 'fa-layer-group';
                        $sectionKey = Str::of($section['label'] ?? 'section')
                            ->slug('-')
                            ->toString();
                    @endphp
                    <section class="oh-sidebar-section space-y-2" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        data-is-operations="{{ $isOperations ? '1' : '0' }}" data-section-key="{{ $sectionKey }}">
                        <button type="button" data-collapse-toggle data-label="{{ $section['label'] }}"
                            class="oh-sidebar__section-btn w-full flex items-center justify-between"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $sectionId }}">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid {{ $sectionIcon }} section-icon"></i>
                                <span class="nav-text">{{ $section['label'] }}</span>
                            </span>
                            <i class="fa-solid fa-chevron-right sidebar-chevron"></i>
                        </button>
                        <div id="{{ $sectionId }}"
                            class="collapse-content sidebar-section-content flex flex-col gap-1">
                            <div class="sidebar-section-header">
                                <div class="sidebar-section-title">{{ $section['label'] }}</div>
                                <button type="button" class="sidebar-section-close" data-section-close
                                    aria-label="Close {{ $section['label'] }}">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            @foreach ($section['items'] ?? [] as $item)
                                @php
                                    $href = $resolveHref($item, $tenantParam);
                                    $isActive = $isItemActive($item, $currentPath, $resolveHref, $tenantParam);
                                @endphp
                                @if ($href !== '#')
                                    <a href="{{ $href }}" data-label="{{ $item['label'] }}"
                                        class="oh-sidebar__link flex items-center gap-3 {{ $isActive ? 'is-active' : '' }}">
                                        <i class="fa-solid {{ $item['icon'] }}"></i>
                                        <span class="nav-text">{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>
        </div>
    </aside>
    <script>
        (function() {
            try {
                const key = 'oh.sidebar';
                const stored = localStorage.getItem(key);
                const expanded = stored ? stored === 'expanded' : window.matchMedia('(min-width: 1280px)').matches;
                const sidebar = document.getElementById('sidebar');
                const mainWrapper = document.getElementById('main-content-wrapper');
                if (!sidebar) return;
                sidebar.classList.toggle('sidebar-collapsed', !expanded);
                sidebar.classList.toggle('w-20', !expanded);
                sidebar.classList.toggle('w-64', expanded);
                if (mainWrapper && window.matchMedia('(min-width: 1024px)').matches) {
                    mainWrapper.classList.toggle('pl-64', expanded);
                    mainWrapper.classList.toggle('pl-20', !expanded);
                    mainWrapper.classList.remove('pl-0');
                }
            } catch (e) {}
        })();
    </script>
@endif
