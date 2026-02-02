@php
    $tenant = $tenant ?? (auth()->user()?->tenant ?? null);
    $modules = data_get($tenant, 'modules');
    $moduleEnabled = function (string $key) use ($modules): bool {
        if (empty($modules)) {
            return true;
        }
        if (is_array($modules)) {
            if (array_is_list($modules)) {
                return in_array($key, $modules, true);
            }
            return (bool) ($modules[$key] ?? false);
        }
        return true;
    };
@endphp

<aside id="sidebar"
    class="oh-sidebar fixed inset-y-0 left-0 z-50 w-64 -translate-x-full transition-transform duration-200 ease-out
           lg:translate-x-0 lg:flex lg:flex-col lg:fixed">
    <div class="oh-sidebar__inner h-full flex flex-col gap-3 p-3">
        <div class="flex items-center justify-end">
            <button id="sidebar-toggle" type="button" class="oh-sidebar__toggle" aria-label="Toggle sidebar">
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
        </div>

        <nav class="sidebar-nav flex-1 space-y-2">
            @php
                $chatUnreadCount = 0;
                $chatUser = auth('web')->user();
                if ($chatUser && $tenant?->id && (int) $chatUser->tenant_id === (int) $tenant->id) {
                    $channels = \App\Models\ChatChannel::query()
                        ->where('tenant_id', $tenant->id)
                        ->withMax('messages as last_message_at', 'created_at')
                        ->addSelect([
                            'last_read_at' => \App\Models\ChatRead::query()
                                ->select('last_read_at')
                                ->whereColumn('chat_reads.channel_id', 'chat_channels.id')
                                ->where('chat_reads.user_id', $chatUser->id)
                                ->limit(1),
                        ])
                        ->where(function ($q) use ($chatUser) {
                            $q->where('type', '!=', 'dm')
                                ->orWhere(function ($q) use ($chatUser) {
                                    $q->where('type', 'dm')
                                        ->whereHas('users', fn ($q) => $q->where('users.id', $chatUser->id));
                                });
                        })
                        ->get();

                    $chatUnreadCount = $channels->filter(function ($channel) {
                        $lastMsg = data_get($channel, 'last_message_at');
                        $lastRead = data_get($channel, 'last_read_at');

                        return $lastMsg && (! $lastRead || $lastMsg > $lastRead);
                    })->count();
                }

                $links = [
                    [
                        'label' => 'Overview',
                        'route' => 'tenant.trades.dashboard',
                        'match' => 'tenant.trades.dashboard',
                        'icon' => 'fa-gauge-high',
                        'enabled' => true,
                    ],
                    [
                        'label' => 'Performance',
                        'route' => 'tenant.trades.performance.index',
                        'match' => 'tenant.trades.performance.*',
                        'icon' => 'fa-chart-column',
                        'enabled' => true,
                    ],
                    [
                        'label' => 'Schedule',
                        'route' => 'tenant.trades.schedule.index',
                        'match' => 'tenant.trades.schedule.*',
                        'icon' => 'fa-calendar-days',
                        'enabled' => $moduleEnabled('schedule'),
                    ],

                    [
                        'label' => 'Jobs',
                        'route' => 'tenant.trades.jobs.index',
                        'match' => 'tenant.trades.jobs.*',
                        'icon' => 'fa-briefcase',
                        'enabled' => $moduleEnabled('jobs'),
                    ],

                    [
                        'label' => 'Quotes',
                        'route' => 'tenant.trades.quotes.index',
                        'match' => 'tenant.trades.quotes.*',
                        'icon' => 'fa-file-invoice',
                        'enabled' => $moduleEnabled('quotes'),
                    ],
                    [
                        'label' => 'Invoices',
                        'route' => 'tenant.trades.invoices.index',
                        'match' => 'tenant.trades.invoices.*',
                        'icon' => 'fa-file-invoice-dollar',
                        'enabled' => $moduleEnabled('invoices'),
                    ],
                    [
                        'label' => 'Service Plans',
                        'route' => 'tenant.trades.service-plans.index',
                        'match' => 'tenant.trades.service-plans.*',
                        'icon' => 'fa-arrows-rotate',
                        'enabled' =>
                            (bool) ($tenant?->trades_recurring_enabled ?? false) && $moduleEnabled('service_plans'),
                    ],
                    [
                        'label' => 'Clients',
                        'route' => 'tenant.trades.clients.index',
                        'match' => 'tenant.trades.clients.*',
                        'icon' => 'fa-address-book',
                        'enabled' => $moduleEnabled('clients'),
                    ],
                    [
                        'label' => 'Leads',
                        'route' => 'tenant.trades.leads.index',
                        'match' => 'tenant.trades.leads.*',
                        'icon' => 'fa-user-plus',
                        'enabled' => $moduleEnabled('leads'),
                    ],
                    [
                        'label' => 'Team',
                        'route' => 'tenant.trades.team.index',
                        'match' => 'tenant.trades.team.*',
                        'icon' => 'fa-users',
                        'enabled' => true,
                    ],
                    [
                        'label' => 'Messages',
                        'route' => 'tenant.trades.chat.index',
                        'match' => 'tenant.trades.chat.*',
                        'icon' => 'fa-comments',
                        'enabled' => true,
                        'badge' => $chatUnreadCount,
                    ],
                ];
            @endphp

            @foreach ($links as $link)
                @if ($link['enabled'] && Route::has($link['route']))
                    @php
                        $isActive = request()->routeIs($link['match']);

                        $tenantParam = request()->route('tenant');
                        $tenantKey = is_object($tenantParam)
                            ? $tenantParam->getRouteKey()
                            : $tenant?->getRouteKey() ?? $tenant?->id;
                    @endphp
                    <a href="{{ route($link['route'], ['tenant' => $tenantKey]) }}"
                        class="oh-sidebar__link flex items-center gap-3 {{ $isActive ? 'is-active' : '' }}"
                        @if ($isActive) aria-current="page" @endif>
                        <i class="fa-solid {{ $link['icon'] }}"></i>
                        <span class="nav-text">{{ $link['label'] }}</span>
                        @if (!empty($link['badge']))
                            <span
                                class="ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-semibold text-white">
                                {{ $link['badge'] > 9 ? '9+' : $link['badge'] }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach

            @php
                $settingsTenantParam = request()->route('tenant');
                $settingsTenantKey = is_object($settingsTenantParam)
                    ? $settingsTenantParam->getRouteKey()
                    : $tenant?->getRouteKey() ?? $tenant?->id;
                $settingsLinks = [
                    [
                        'label' => 'Profile',
                        'route' => 'tenant.trades.settings.profile',
                        'match' => 'tenant.trades.settings.profile*',
                        'icon' => 'fa-user-gear',
                    ],
                    [
                        'label' => 'Operations',
                        'route' => 'tenant.trades.settings.operations',
                        'match' => 'tenant.trades.settings.operations*',
                        'icon' => 'fa-sliders',
                    ],
                    [
                        'label' => 'Lead Intake',
                        'route' => 'tenant.trades.settings.leads',
                        'match' => 'tenant.trades.settings.leads*',
                        'icon' => 'fa-inbox',
                    ],
                    [
                        'label' => 'Time & Shifts',
                        'route' => 'tenant.trades.settings.time',
                        'match' => 'tenant.trades.settings.time*',
                        'icon' => 'fa-clock',
                    ],
                ];
            @endphp

            <div class="pt-2 mt-2 border-t border-border-default/60">
                @php $settingsActive = request()->routeIs('tenant.trades.settings.*'); @endphp
                <details class="group" @if ($settingsActive) open @endif>
                    <summary
                        class="oh-sidebar__link flex items-center justify-between gap-3 cursor-pointer list-none {{ $settingsActive ? 'is-active' : '' }}"
                        @if ($settingsActive) aria-current="page" @endif>
                        <span class="flex items-center gap-3">
                            <i class="fa-solid fa-gear"></i>
                            <span class="nav-text">Settings</span>
                        </span>
                        <i
                            class="fa-solid fa-chevron-right text-xs text-text-subtle transition-transform group-open:rotate-90"></i>
                    </summary>
                    <div class="mt-2 space-y-1">
                        @if (Route::has('tenant.trades.settings.index'))
                            <a href="{{ route('tenant.trades.settings.index', ['tenant' => $settingsTenantKey]) }}"
                                class="oh-sidebar__link flex items-center gap-3 pl-8 {{ request()->routeIs('tenant.trades.settings.index') ? 'bg-[rgba(var(--brand-primary),0.08)] text-text-base' : '' }}">
                                <i class="fa-solid fa-sliders"></i>
                                <span class="nav-text">Overview</span>
                            </a>
                        @endif
                        @foreach ($settingsLinks as $link)
                            @if (Route::has($link['route']))
                                @php $isActive = request()->routeIs($link['match']); @endphp
                                <a href="{{ route($link['route'], ['tenant' => $settingsTenantKey]) }}"
                                    class="oh-sidebar__link flex items-center gap-3 pl-8 {{ $isActive ? 'bg-[rgba(var(--brand-primary),0.08)] text-text-base' : '' }}"
                                    @if ($isActive) aria-current="page" @endif>
                                    <i class="fa-solid {{ $link['icon'] }}"></i>
                                    <span class="nav-text">{{ $link['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </details>
            </div>
        </nav>
    </div>
</aside>
