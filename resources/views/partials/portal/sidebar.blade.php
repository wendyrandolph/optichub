{{-- resources/views/partials/portal/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $tenantName = $tenant?->name ?? ($tenant?->company_name ?? 'Workspace');
    $portalTheme = $portalTheme ?? [];
    $tenantLogo = $portalTheme['logo_light'] ?? asset('images/renlo.svg');

    $user = auth('client')->user();
    $first = $user?->first_name ?? ($client?->firstName ?? ($client?->first_name ?? ''));
    $last = $user?->last_name ?? ($client?->lastName ?? ($client?->last_name ?? ''));
    $email = $user?->email ?? '';
    $initials = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
    $clientCompanyName =
        $client?->company?->company_name ??
        ($client?->company?->name ??
            ($user?->contact?->company?->company_name ?? ($user?->contact?->company?->name ?? null)));
    if ($initials === '' && $email !== '') {
        $initials = strtoupper(mb_substr($email, 0, 1));
    }
    $initials = $initials ?: 'C';

    $links = [
        [
            'label' => 'Dashboard',
            'route' => 'portal.dashboard',
            'match' => 'portal.dashboard',
            'icon' => 'fa-gauge',
        ],
        [
            'label' => 'Projects',
            'route' => 'portal.projects.index',
            'match' => 'portal.projects.*',
            'icon' => 'fa-lightbulb',
        ],
        [
            'label' => 'Files',
            'route' => 'portal.files.index',
            'match' => 'portal.files.*',
            'icon' => 'fa-paperclip',
        ],
        [
            'label' => 'Invoices',
            'route' => 'portal.invoices.index',
            'match' => 'portal.invoices.*',
            'icon' => 'fa-file-invoice',
        ],
        [
            'label' => 'My account',
            'route' => 'portal.settings.edit',
            'match' => 'portal.settings.*',
            'icon' => 'fa-gear',
        ],
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 max-w-[80vw] bg-[rgb(var(--ui-surface))] bg-surface-card border-r border-border-default
           transform -translate-x-full transition-transform duration-200 ease-out
           lg:static lg:translate-x-0 lg:flex-shrink-0"
    data-portal-sidebar>
    <div class="h-full flex flex-col px-4 py-5">
        <div class="flex items-start justify-between gap-3">
            <a href="{{ route('portal.dashboard') }}" class="flex flex-col items-start gap-2 min-w-0"
                aria-label="{{ $tenantName }} client portal home">
                <img src="{{ $tenantLogo }}" class="h-9 w-auto rounded-md object-contain bg-white/70 px-2"
                    alt="{{ $tenantName }} logo">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-text-base truncate">
                        {{ $tenantName }}</p>

                </div>

            </a>

            <button type="button"
                class="lg:hidden inline-flex items-center justify-center h-8 w-8 rounded-lg border border-border-default bg-surface-accent"
                data-portal-sidebar-close aria-label="Close sidebar">
                <i class="fa-solid fa-xmark text-sm text-text-base"></i>
            </button>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button type="button"
                    class="lg:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent"
                    aria-label="Open sidebar" data-portal-sidebar-toggle>
                    <i class="fa-solid fa-bars text-sm text-text-base"></i>
                </button>

                @if ($user)
                    <div class="relative">
                        <button type="button"
                            class="relative inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent"
                            aria-label="Notifications" data-portal-notifications-toggle>
                            <i class="fa-regular fa-bell text-[12px] text-text-base"></i>
                            @if (!empty($portalAlertCount))
                                <span
                                    class="absolute -top-1 -right-1 h-4 min-w-[1rem] rounded-full bg-[rgb(var(--brand-primary))] px-1 text-[10px] font-semibold text-white flex items-center justify-center">
                                    {{ $portalAlertCount }}
                                </span>
                            @endif
                        </button>
                        <div
                            class="absolute left-0 mt-2 w-64 rounded-xl border border-border-default bg-[rgb(var(--ui-surface))] shadow-lg p-3 text-sm text-text-base hidden z-50"
                            data-portal-notifications-menu>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs uppercase tracking-wide text-text-subtle">Notifications</p>
                                <a href="{{ route('portal.projects.index') }}" class="text-xs text-text-subtle hover:text-text-base">
                                    View all
                                </a>
                            </div>
                            <div class="space-y-2">
                                @if (!empty($portalAlerts) && $portalAlerts->isNotEmpty())
                                    @foreach ($portalAlerts as $alert)
                                        <a href="{{ $alert['url'] ?? route('portal.projects.index') }}"
                                            class="flex items-start gap-2 rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs text-text-base hover:bg-surface-accent">
                                            <span class="mt-0.5 h-1.5 w-1.5 rounded-full {{ !empty($alert['is_urgent']) ? 'bg-red-500' : 'bg-[rgb(var(--brand-primary))]' }}"></span>
                                            <div class="min-w-0">
                                                <p class="font-semibold truncate">{{ $alert['title'] ?? 'Notification' }}</p>
                                                <p class="text-[11px] text-text-subtle truncate">{{ $alert['meta'] ?? '' }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                @else
                                    <div class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs text-text-subtle">
                                        No new notifications.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('portal.projects.index') }}"
                        class="relative inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent"
                        aria-label="Project chat">
                        <i class="fa-solid fa-envelope text-[12px] text-text-base"></i>
                        @if (!empty($portalUnreadCount))
                            <span
                                class="absolute -top-1 -right-1 h-4 min-w-[1rem] rounded-full bg-[rgb(var(--brand-primary))] px-1 text-[10px] font-semibold text-white flex items-center justify-center">
                                {{ $portalUnreadCount }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>

            @if ($user)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent"
                        aria-label="Log out">
                        <i class="fa-solid fa-right-from-bracket text-[12px] text-text-base"></i>
                    </button>
                </form>
            @endif
        </div>

        <div class="mt-4">
            <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Client Portal</p>
        </div>
        <div class="mt-3 border-t border-border-default"></div>

        <nav class="flex flex-col flex-grow mt-3 border-b border-border-default space-y-1">
            @foreach ($links as $link)
                @php
                    $route = $link['route'];
                    if (!Route::has($route)) {
                        continue;
                    }
                    $isActive = request()->routeIs($link['match']);
                @endphp
                <a href="{{ route($route) }}"
                    class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}">
                    @if ($isActive)
                        <span
                            class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    @endif
                    <i
                        class="fa-solid {{ $link['icon'] }} text-xs {{ $isActive ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="pt-4 border-t border-border-default">
            <div class="flex items-center gap-3 px-3 py-2">
                <div
                    class="h-9 w-9 rounded-full bg-[rgb(var(--tenant-primary))] text-[rgb(var(--tenant-on-primary))] flex items-center justify-center text-xs font-semibold ring-1 ring-[rgba(var(--tenant-primary),0.25)]">
                    {{ $initials }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-text-base truncate">
                        {{ $first !== '' || $last !== '' ? trim($first . ' ' . $last) : ($email ?: 'Client') }}
                    </p>
                    <p class="text-xs text-text-subtle truncate">
                        {{ $clientCompanyName ?: 'Individual client' }}
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-text-subtle hover:text-text-base hover:bg-surface-accent">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    Log out
                </button>
            </form>
        </div>
    </div>
</aside>
