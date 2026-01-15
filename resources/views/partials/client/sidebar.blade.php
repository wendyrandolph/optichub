{{-- resources/views/partials/client/sidebar.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $tenantName = $tenant->name ?? 'Workspace';
    $portalTheme = $portalTheme ?? [];

    $routeOr = function (string $name, array $params = []) {
        return Route::has($name) ? route($name, $params) : '#';
    };

    $isActive = function (array|string $patterns) {
        return request()->routeIs($patterns);
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 max-w-[80vw] bg-surface-card border-r border-border-default
           transform -translate-x-full transition-transform duration-200 ease-out
           lg:static lg:translate-x-0 lg:flex-shrink-0"
    data-portal-sidebar>
    <div class="h-full flex flex-col px-4 py-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[11px] uppercase tracking-[0.18em] text-text-subtle">
                    CLIENT PORTAL
                </p>
                <p class="text-sm font-medium text-text-base truncate mt-1">
                    {{ $tenant->name ?? 'Your workspace' }}
                </p>
            </div>
            @if (!empty($portalTheme['logo_light']))
                <img src="{{ $portalTheme['logo_light'] }}" class="h-7 w-auto rounded-md object-contain"
                    alt="{{ $tenant->name ?? 'Workspace' }} logo">
            @endif
            {{-- Close on mobile --}}
            <button type="button"
                class="lg:hidden inline-flex items-center justify-center h-8 w-8 rounded-lg border border-border-default bg-surface-accent"
                aria-label="Close sidebar"
                data-portal-sidebar-close>
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="mt-4 border-t border-border-default"></div>

        {{-- Nav items --}}
        <nav class="flex flex-col flex-grow mt-4 space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('portal.dashboard') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.dashboard') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="Dashboard" aria-current="{{ $isActive('portal.dashboard') ? 'page' : 'false' }}">
                @if ($isActive('portal.dashboard'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-solid fa-gauge text-xs {{ $isActive('portal.dashboard') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>Dashboard</span>
            </a>

            {{-- Projects (you can wire this later to a projects listing) --}}
            <a href="{{ route('portal.projects.index') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.projects.*') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="Projects" aria-current="{{ $isActive('portal.projects.*') ? 'page' : 'false' }}">
                @if ($isActive('portal.projects.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-regular fa-lightbulb text-xs {{ $isActive('portal.projects.*') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>Projects</span>
            </a>

            {{-- Files (hook up when you have a files index) --}}
            <a href="{{ route('portal.files.index') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.files.*') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="Files" aria-current="{{ $isActive('portal.files.*') ? 'page' : 'false' }}">
                @if ($isActive('portal.files.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-solid fa-paperclip text-xs {{ $isActive('portal.files.*') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>Files</span>
            </a>

            {{-- Billing & invoices --}}
            <a href="{{ route('portal.invoices.index') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.invoices.*') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="Billing &amp; invoices" aria-current="{{ $isActive('portal.invoices.*') ? 'page' : 'false' }}">
                @if ($isActive('portal.invoices.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-solid fa-file-invoice text-xs {{ $isActive('portal.invoices.*') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>Billing &amp; invoices</span>
            </a>

            {{-- Messages (placeholder for now) --}}
            <a href="{{ route('portal.messages.index') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.messages.*') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="Messages" aria-current="{{ $isActive('portal.messages.*') ? 'page' : 'false' }}">
                @if ($isActive('portal.messages.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-solid fa-envelope text-xs {{ $isActive('portal.messages.*') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>Messages</span>
                @if (!empty($portalUnreadCount))
                    <span class="ml-auto rounded-full bg-[rgb(var(--brand-primary))] px-2 py-0.5 text-[10px] font-semibold text-white">
                        {{ $portalUnreadCount }}
                    </span>
                @endif
            </a>

            {{-- My account (you can point this at a profile/edit page later) --}}
            <a href="{{ route('portal.settings.edit') }}"
                class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $isActive('portal.settings.*') ? 'bg-surface-accent text-text-base font-semibold' : 'text-text-subtle hover:text-text-base hover:bg-surface-accent/60' }}"
                title="My account" aria-current="{{ $isActive('portal.settings.*') ? 'page' : 'false' }}">
                @if ($isActive('portal.settings.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-0.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                @endif
                <i class="fa-solid fa-user-gear text-xs {{ $isActive('portal.settings.*') ? 'text-text-base' : 'text-text-subtle group-hover:text-text-base' }}"></i>
                <span>My account</span>
            </a>
        </nav>
    </div>
</aside>
