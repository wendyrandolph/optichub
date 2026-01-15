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
                $links = [
                    [
                        'label' => 'Today',
                        'route' => 'tenant.trades.field.today',
                        'match' => 'tenant.trades.field.today',
                        'icon' => 'fa-sun',
                        'enabled' => $moduleEnabled('field'),
                    ],
                    [
                        'label' => 'My Jobs',
                        'route' => 'tenant.trades.jobs.index',
                        'match' => 'tenant.trades.jobs.*',
                        'icon' => 'fa-briefcase',
                        'enabled' => $moduleEnabled('jobs'),
                    ],
                    [
                        'label' => 'Team Chat',
                        'route' => 'tenant.trades.chat.index',
                        'match' => 'tenant.trades.chat.*',
                        'icon' => 'fa-comments',
                        'enabled' => true,
                    ],
                    [
                        'label' => 'Time Clock',
                        'route' => 'tenant.trades.field.time',
                        'match' => 'tenant.trades.field.time',
                        'icon' => 'fa-clock',
                        'enabled' => $moduleEnabled('field'),
                    ],
                    [
                        'label' => 'PTO Request',
                        'route' => 'tenant.trades.field.pto',
                        'match' => 'tenant.trades.field.pto',
                        'icon' => 'fa-calendar-check',
                        'enabled' => $moduleEnabled('field'),
                    ],
                ];
            @endphp

            @foreach ($links as $link)
                @if ($link['enabled'] && Route::has($link['route']))
                    @php
                        $isActive = request()->routeIs($link['match']);
                        $params = ['tenant' => $tenant?->id] + ($link['params'] ?? []);
                    @endphp
                    <a href="{{ route($link['route'], $params) }}"
                        class="oh-sidebar__link flex items-center gap-3 {{ $isActive ? 'is-active' : '' }}"
                        @if ($isActive) aria-current="page" @endif>
                        <i class="fa-solid {{ $link['icon'] }}"></i>
                        <span class="nav-text">{{ $link['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
</aside>
