{{-- resources/views/partials/portal/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $tenantName = $tenant?->name ?? 'Workspace';
    $portalTheme = $portalTheme ?? [];

    $user = auth('client')->user();
    $first = $user?->first_name ?? $client?->firstName ?? $client?->first_name ?? '';
    $last = $user?->last_name ?? $client?->lastName ?? $client?->last_name ?? '';
    $email = $user?->email ?? '';
    $initials = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
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
            'label' => 'Messages',
            'route' => 'portal.messages.index',
            'match' => 'portal.messages.*',
            'icon' => 'fa-envelope',
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
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.18em] text-text-subtle">
                    Client Portal
                </p>
                <p class="text-sm font-medium text-text-base truncate mt-1">
                    {{ $tenantName }}
                </p>
            </div>
            @if (!empty($portalTheme['logo_light']))
                <img src="{{ $portalTheme['logo_light'] }}" class="h-7 w-auto rounded-md object-contain"
                    alt="{{ $tenantName }} logo">
            @endif
            <button type="button"
                class="lg:hidden inline-flex items-center justify-center h-8 w-8 rounded-lg border border-border-default bg-surface-accent"
                data-portal-sidebar-close aria-label="Close sidebar">
                <i class="fa-solid fa-xmark text-sm text-text-base"></i>
            </button>
        </div>

        <div class="mt-4 border-t border-border-default"></div>

        <nav class="flex flex-col flex-grow mt-4 space-y-1">
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
                        <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    @endif
                    <i class="fa-solid {{ $link['icon'] }} text-xs {{ $isActive ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                    <span>{{ $link['label'] }}</span>
                    @if ($link['route'] === 'portal.messages.index' && !empty($portalUnreadCount))
                        <span class="ml-auto rounded-full bg-[rgb(var(--brand-primary))] px-2 py-0.5 text-[10px] font-semibold text-white">
                            {{ $portalUnreadCount }}
                        </span>
                    @endif
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
                    <p class="text-xs text-text-subtle truncate">{{ $tenantName }}</p>
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
