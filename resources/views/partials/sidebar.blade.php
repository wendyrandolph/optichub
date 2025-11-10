@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;
    use App\Models\Tenant;

    $routeTenant = request()->route('tenant');
    $tenantId = null;

    if ($routeTenant instanceof Tenant) {
        $tenantId = $routeTenant->getKey();
    } elseif (is_numeric($routeTenant)) {
        $tenantId = (int) $routeTenant;
    } elseif (is_string($routeTenant) && $routeTenant !== '') {
        // if a slug ever slips in, this keeps us from breaking
        $tenantId = (int) preg_replace('/\D+/', '', $routeTenant) ?: null;
    }

    if (!$tenantId) {
        $tenantId = auth()->user()->tenant_id ?? (session('tenant_id') ?? null);
    }
    $tenantParam = $tenantId ?: null;

    $resolveHref = function (array $item, $tenantParam) {
        if (!empty($item['href'])) {
            return $item['href'];
        }
        if (
            !empty($item['route']) &&
            (str_starts_with($item['route'], '/') || str_starts_with($item['route'], 'http'))
        ) {
            return $item['route'];
        }
        if (!empty($item['route']) && Route::has($item['route'])) {
            $params = $item['params'] ?? [];
            if (str_starts_with($item['route'], 'tenant.') && !array_key_exists('tenant', $params) && $tenantParam) {
                $params['tenant'] = $tenantParam;
            }
            try {
                return route($item['route'], $params);
            } catch (\Throwable $e) {
                return '#';
            }
        }
        return '#';
    };

    /**
     * Org/role context (based on how you’re storing it)
     */
    $organizationType = auth()->user()->organization_type ?? session('organization_type');
    $userRole = auth()->user()->role ?? session('role');

    $organizationType = $organizationType ? Str::of($organizationType)->lower()->toString() : null;
    $userRole = $userRole ? Str::of($userRole)->lower()->toString() : null;

    $isProviderAdmin =
        $organizationType === 'provider' &&
        in_array($userRole, ['admin', 'provider', 'super_admin', 'superadmin'], true);
    $isProviderEmployee = $organizationType === 'provider' && $userRole === 'employee';
    $isProviderClient = $organizationType === 'provider' && $userRole === 'client';
    $isTenantAdmin = $organizationType === 'saas_tenant' && $userRole === 'admin';
    $isTenantEmployee = $organizationType === 'saas_tenant' && $userRole === 'employee';

    $currentPath = request()->path();

    /**
     * Active helpers
     */
    $isItemActive = function (array $item, string $currentPath, callable $resolveHref, ?string $tenantParam): bool {
        if (!empty($item['route']) && request()->routeIs($item['route'])) {
            return true;
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

    /**
     * Build sections
     */
    $navSections = [];
    if ($isProviderAdmin) {
        $tenantItems = $tenantParam
            ? [
                ['route' => 'tenant.projects.index', 'icon' => 'fa-lightbulb', 'label' => 'Projects'],
                ['route' => 'tenant.tasks.index', 'icon' => 'fa-list-check', 'label' => 'Tasks'],
                ['route' => 'tenant.leads.index', 'icon' => 'fa-user-plus', 'label' => 'Leads'],
                ['route' => 'tenant.organizations.index', 'icon' => 'fa-building', 'label' => 'Organizations'],
                ['route' => 'tenant.opportunities.index', 'icon' => 'fa-chart-line', 'label' => 'Opportunities'],
                ['route' => 'tenant.search', 'icon' => 'fa-magnifying-glass', 'label' => 'Search'],
            ]
            : [];

        $navSections = [
            [
                'label' => 'Operations',
                'items' => [
                    ['route' => 'admin.dashboard', 'icon' => 'fa-gauge-high', 'label' => 'Admin Dashboard'],
                    ['route' => 'tenant.dashboards.index', 'icon' => 'fa-lightbulb', 'label' => 'Lead Insights'],
                    ['route' => 'tenant.admin.reports.index', 'icon' => 'fa-file-lines', 'label' => 'Reports'],
                    ['route' => 'admin.activity.index', 'icon' => 'fa-rectangle-list', 'label' => 'Activity Logs'],
                ],
            ],
            [
                'label' => 'Customer Success',
                'items' => [
                    ...$tenantItems,
                    ['route' => 'tenant.emails.index', 'icon' => 'fa-envelope', 'label' => 'Emails'],
                    ['route' => 'tenant.emails.create', 'icon' => 'fa-paper-plane', 'label' => 'Compose Email'],

                    ['route' => 'tenant.contacts.index', 'icon' => 'fa-user-group', 'label' => 'Clients'],
                    ['route' => 'tenant.team-members.index', 'icon' => 'fa-user-tag', 'label' => 'Team Members'],
                ],
            ],
            [
                'label' => 'Billing & Scheduling',
                'items' => [
                    ['route' => 'tenant.invoices.index', 'icon' => 'fa-file-invoice', 'label' => 'Invoices'],
                    ['href' => '/time', 'icon' => 'fa-clock', 'label' => 'Time'],
                    ['href' => '/calendar', 'icon' => 'fa-calendar-days', 'label' => 'Calendar'],
                ],
            ],
            [
                'label' => 'Marketing',
                'items' => [
                    ['route' => 'tenant.leads.index', 'icon' => 'fa-user-plus', 'label' => 'Lead Manager'],
                    ['route' => 'tenant.leads.create', 'icon' => 'fa-circle-plus', 'label' => 'Create Lead'],
                    ['href' => '/', 'icon' => 'fa-bullhorn', 'label' => 'Marketing Home'],
                ],
            ],
            [
                'label' => 'Settings',
                'items' => [
                    ['route' => 'tenant.settings.index', 'icon' => 'fa-sliders', 'label' => 'Settings Overview'],
                    ['route' => 'tenant.settings.profile', 'icon' => 'fa-palette', 'label' => 'Profile & Branding'],
                    ['route' => 'tenant.settings.api.index', 'icon' => 'fa-key', 'label' => 'API Keys'],
                    ['route' => 'tenant.settings.billing', 'icon' => 'fa-wallet', 'label' => 'Billing'],
                    ['route' => 'tenant.settings.billing-upgrade', 'icon' => 'fa-arrow-up', 'label' => 'Upgrade'],
                ],
            ],
        ];
    } elseif ($isTenantAdmin) {
        $navSections = [
            [
                'label' => 'Company',
                'items' => [
                    ['href' => '/dashboard', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
                    ['href' => '/clients', 'icon' => 'fa-user-group', 'label' => 'Clients'],
                    ['href' => '/projects', 'icon' => 'fa-lightbulb', 'label' => 'Projects'],
                    ['href' => '/tasks', 'icon' => 'fa-list-check', 'label' => 'Tasks'],
                    ['href' => '/invoices', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Invoices'],
                    ['href' => '/users', 'icon' => 'fa-user-tag', 'label' => 'Team Members'],
                    ['route' => 'tenant.emails.create', 'icon' => 'fa-paper-plane', 'label' => 'Compose Email'],
                ],
            ],
            [
                'label' => 'Configuration',
                'items' => [
                    ['route' => 'tenant.settings.index', 'icon' => 'fa-sliders', 'label' => 'Settings Overview'],
                    ['route' => 'tenant.settings.profile', 'icon' => 'fa-palette', 'label' => 'Profile & Branding'],
                    ['route' => 'tenant.settings.api.index', 'icon' => 'fa-key', 'label' => 'API Keys'],
                    ['route' => 'tenant.settings.billing', 'icon' => 'fa-wallet', 'label' => 'Billing'],
                    ['route' => 'tenant.settings.billing.upgrade', 'icon' => 'fa-arrow-up', 'label' => 'Upgrade'],
                ],
            ],
        ];
    } else {
        $navSections = [
            [
                'label' => 'Dashboard',
                'items' => [
                    ['href' => '/dashboard', 'icon' => 'fa-gauge', 'label' => 'Main Dashboard'],
                    ['href' => '/tasks', 'icon' => 'fa-list-check', 'label' => 'My Tasks'],
                ],
            ],
        ];
    }
@endphp

<aside id="sidebar" class="fixed inset-y-0 left-0 h-screen bg-optic-brand text-white shadow-2xl z-40 w-64">
    <div
        class="sidebar__inner flex flex-col justify-between h-full p-2 overflow-y-auto overflow-x-hidden scrollbar-none">
        <div>
            <div class="p-2 pt-0 flex justify-end items-center h-16">
                <button type="button" id="sidebar-toggle"
                    class="h-10 w-10 flex items-center justify-center p-2 text-white bg-gray-800 rounded-full hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/70"
                    aria-expanded="true">
                    <i class="fas fa-chevron-left w-6 h-6"></i>
                </button>
            </div>

            <nav id="sidebarNav" class="sidebar-nav space-y-1" aria-label="Primary">
                @if (empty($navSections))
                    <div class="p-2 text-xs text-red-400">No menu items are defined.</div>
                @endif

                @foreach ($navSections as $section)
                    @php
                        $sectionLabel = $section['label'] ?? '';
                        $sectionId = 'section-' . Str::of($sectionLabel)->lower()->slug('-');
                        $sectionItems = $section['items'] ?? [];
                        $sectionIsActive = $isSectionActive($sectionItems);
                        $sectionIconMap = [
                            'operations' => 'fa-diagram-project',
                            'customer success' => 'fa-people-group',
                            'billing & scheduling' => 'fa-calendar-days',
                            'marketing' => 'fa-bullhorn',
                            'settings' => 'fa-sliders',
                            'company' => 'fa-building',
                            'configuration' => 'fa-gear',
                            'dashboard' => 'fa-gauge',
                        ];
                        $sectionKey = Str::of($sectionLabel)->lower()->toString();
                        $sectionIcon = $section['icon'] ?? ($sectionIconMap[$sectionKey] ?? 'fa-circle');
                    @endphp

                    <div class="sidebar-section" id="{{ $sectionId }}" data-section-name="{{ $sectionLabel }}"
                        aria-expanded="{{ $sectionIsActive ? 'true' : 'false' }}">
                        <button type="button"
                            class="sidebar-section__button flex items-center justify-between w-full h-10 px-3 rounded-xl hover:bg-gray-700 text-gray-300 text-sm font-semibold transition-colors duration-150"
                            data-collapse-toggle aria-controls="{{ $sectionId }}-content"
                            aria-expanded="{{ $sectionIsActive ? 'true' : 'false' }}" data-label="{{ $sectionLabel }}">
                            <span class="flex items-center gap-3">
                                <i class="fas {{ $sectionIcon }} text-base w-5 text-center leading-none"></i>
                                <span class="nav-text">{{ $sectionLabel }}</span>
                            </span>
                            <i class="fas fa-chevron-right sidebar-chevron flex-shrink-0 text-xs mr-1"></i>
                        </button>

                        <div id="{{ $sectionId }}-content" class="collapse-content space-y-1 py-1"
                            style="{{ $sectionIsActive ? 'display:block;opacity:1;' : 'display:none;height:0;opacity:0;' }}">
                            @foreach ($sectionItems as $item)
                                @php
                                    $resolvedHref = $resolveHref($item, $tenantParam);
                                    $active = $isItemActive($item, $currentPath, $resolveHref, $tenantParam);
                                    $activeClass = $active
                                        ? 'bg-blue-700 text-white shadow-sm'
                                        : 'hover:bg-gray-700 text-gray-300';
                                @endphp




                                <a href="{{ $resolvedHref }}"
                                    class="sidebar-link relative flex items-center gap-2 h-10 px-3 ml-2 rounded-lg text-sm font-medium transition-colors duration-150 {{ $activeClass }}"
                                    data-label="{{ $item['label'] ?? '' }}">
                                    <i
                                        class="fas {{ $item['icon'] ?? 'fa-circle' }} mr-3 text-base w-5 text-center leading-none"></i>
                                    <span class="nav-text truncate opacity-90">{{ $item['label'] ?? '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </div>

        <div class="p-2 border-t border-gray-700/50 mt-auto">
            <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            <button type="button" onclick="document.getElementById('sidebar-logout-form').submit();"
                class="w-full flex items-center justify-start gap-3 p-3 rounded-xl hover:bg-red-600/20 text-red-400 transition"
                data-label="Logout">
                <i class="fas fa-right-from-bracket text-lg w-5 text-center"></i>
                <span class="nav-text whitespace-nowrap overflow-hidden transition-all duration-300">Logout</span>
            </button>
        </div>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggles = document.querySelectorAll('[data-collapse-toggle]');

        const clean = (el) => {
            el.style.removeProperty('height');
            el.style.removeProperty('opacity');
        };

        const collapse = (btn) => {
            const section = btn.closest('.sidebar-section');
            const content = document.getElementById(btn.getAttribute('aria-controls'));
            if (!section || !content || content.style.display === 'none') return;
            content.style.height = content.scrollHeight + 'px';
            content.offsetHeight;
            content.style.height = '0';
            content.style.opacity = '0';
            btn.setAttribute('aria-expanded', 'false');
            section.setAttribute('aria-expanded', 'false');
            content.addEventListener('transitionend', function h() {
                content.style.display = 'none';
                clean(content);
                content.removeEventListener('transitionend', h);
            }, {
                once: true
            });
        };

        const expand = (btn) => {
            const section = btn.closest('.sidebar-section');
            const content = document.getElementById(btn.getAttribute('aria-controls'));
            if (!section || !content) return;
            content.style.display = 'block';
            content.style.height = 'auto';
            const h = content.scrollHeight;
            content.style.height = '0';
            content.style.opacity = '0';
            content.offsetHeight;
            content.style.height = h + 'px';
            content.style.opacity = '1';
            btn.setAttribute('aria-expanded', 'true');
            section.setAttribute('aria-expanded', 'true');
            content.addEventListener('transitionend', function hh() {
                clean(content);
                content.removeEventListener('transitionend', hh);
            }, {
                once: true
            });
        };

        toggles.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const isExpanded = btn.getAttribute('aria-expanded') === 'true';

                // If sidebar is collapsed, expand it first then open the section
                if (sidebar && sidebar.classList.contains('w-20')) {
                    sidebar.classList.remove('w-20');
                    sidebar.classList.add('w-64');
                    const main = document.getElementById('main-content-wrapper');
                    if (main) {
                        main.classList.remove('pl-20');
                        main.classList.add('pl-64');
                    }
                    localStorage.setItem('sidebarExpanded', 'true');
                    expand(btn);
                    return;
                }

                isExpanded ? collapse(btn) : expand(btn);
            });
        });

        // Normalize initial open/closed styles
        document.querySelectorAll('.sidebar-section[aria-expanded="true"] .collapse-content').forEach(c => {
            clean(c);
            c.style.display = 'block';
        });
        document.querySelectorAll('.sidebar-section[aria-expanded="false"] .collapse-content').forEach(c => {
            c.style.display = 'none';
            c.style.height = '0';
            c.style.opacity = '0';
        });
    });
</script>
