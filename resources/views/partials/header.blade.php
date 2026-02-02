@php
    $currentUser = auth('admin')->user() ?? (auth('client')->user() ?? auth()->user());
    $user = $currentUser;
    $name = $name ?? ($currentUser?->name ?? '');
    $tenantName = optional($currentUser?->tenant)->name ?? null;

    $jobTitle = $currentUser->job_title ?? ($currentUser->title ?? ($currentUser->position ?? null));

    $rawRole = $currentUser->role ?? null;

    // --- CLEAN ROLE LABEL MAP ---
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',

        // Provider-side roles (your company)
        'provider' => 'Provider Admin',
        'provider_employee' => 'Provider Team Member',
        'provider_client' => 'Provider Client',

        // Tenant company roles
        'tenant_admin' => 'Tenant Admin',
        'tenant_employee' => 'Team Member',
        'client' => 'Client',
        'client_org_client' => 'Client Contact',
    ];

    $roleLabel = $roleLabels[$rawRole] ?? ucwords(str_replace('_', ' ', $rawRole));

    $isPlatformOwner =
        $currentUser && method_exists($currentUser, 'isPlatformOwner') ? $currentUser->isPlatformOwner() : false;

    $first = $user?->first_name;
    $last = $user?->last_name;
    $tenantId =
        $currentUser?->tenant_id ??
        (request()->route('tenant') instanceof \App\Models\Tenant
            ? request()->route('tenant')->id
            : (is_numeric(request()->route('tenant'))
                ? (int) request()->route('tenant')
                : null));
    $quickSearchUrl = null;
    if ($tenantId && \Illuminate\Support\Facades\Route::has('tenant.search.quick')) {
        $quickSearchUrl = route('tenant.search.quick', ['tenant' => $tenantId]);
    } elseif (\Illuminate\Support\Facades\Route::has('admin.search.quick')) {
        $quickSearchUrl = route('admin.search.quick');
    }
    // Build a safe display name: First Last, fallback to name, then Guest User
    $name = trim(($first ? $first : '') . ' ' . ($last ? $last : '')) ?: $user?->name ?? 'Guest User';
    // Initial from first name, fallback to name, then '?'
    $userFirst = strtoupper(mb_substr($first ?? ($user?->name ?? '?'), 0, 1));
    $userLast = strtoupper(mb_substr($last ?? ($user?->name ?? '?'), 0, 1));
    $userInitial = $userFirst . $userLast;

    $profileRoute = null;
    if (auth('admin')->check()) {
        if (\Illuminate\Support\Facades\Route::has('admin.profile')) {
            $profileRoute = route('admin.profile');
        } else {
            $tenantId = $currentUser?->tenant_id ?? request()->route('tenant');
            if ($tenantId && \Illuminate\Support\Facades\Route::has('tenant.settings.profile')) {
                $profileRoute = route('tenant.settings.profile', ['tenant' => $tenantId]);
            }
        }
    } elseif (auth('client')->check()) {
        // Client portal settings
        if (\Illuminate\Support\Facades\Route::has('portal.settings.edit')) {
            $profileRoute = route('portal.settings.edit');
        }
    }

    $chatInboxUrl = null;
    $chatUser = auth('admin')->user() ?? auth('web')->user();
    $isTradesContext = ($currentUser?->tenant?->workspace_type ?? null) === 'trades' || request()->routeIs('tenant.trades.*');
    if ($chatUser && $tenantId) {
        if ($isTradesContext && \Illuminate\Support\Facades\Route::has('tenant.trades.chat.index')) {
            $chatInboxUrl = route('tenant.trades.chat.index', ['tenant' => $tenantId]);
        } elseif (\Illuminate\Support\Facades\Route::has('tenant.chat.index')) {
            $chatInboxUrl = route('tenant.chat.index', ['tenant' => $tenantId]);
        }

    }
@endphp


<header id="main-header"
    class="w-xl w-full mx-auto px-3 sm:px-5 lg:px-8 py-3 flex justify-between items-center bg-[rgb(var(--ui-surface))] text-[rgb(var(--ui-text))]">
    <div class="flex items-center gap-4">
        <button id="sidebar-open" type="button" aria-label="Toggle sidebar" aria-expanded="false"
            class="lg:hidden inline-flex items-center justify-center
         h-10 w-10 rounded-xl
         bg-[rgb(var(--ui-surface))]
         ring-1 ring-[rgb(var(--ui-border)/.6)]
         transition
         hover:bg-[rgb(var(--ui-surface-muted))]
         active:scale-95">
            <i class="fa-solid fa-bars text-base"></i>
        </button>



        {{-- Theme toggle (beside logo) --}}
        <button id="theme-toggle" type="button" class="oh-theme-toggle" aria-label="Toggle theme" aria-pressed="false">
            <span class="oh-theme-toggle__track" aria-hidden="true">
                <i class="fa-solid fa-sun oh-theme-toggle__icon oh-theme-toggle__icon--sun" aria-hidden="true"></i>
                <i class="fa-solid fa-moon oh-theme-toggle__icon oh-theme-toggle__icon--moon" aria-hidden="true"></i>
                <span class="oh-theme-toggle__thumb" aria-hidden="true"></span>
            </span>
        </button>



        <!-- Brand -->
        <a href="{{ url('/dashboard') }}" class="flex items-center h-full" aria-label="Relan home">
            <img src="{{ asset('images/renlo.svg') }}" alt="Renlo" class="h-9 w-auto block dark:hidden">
            <img src="{{ asset('images/renlo-white-logo.svg') }}" alt="Renlo" class="h-9 w-auto hidden dark:block">
        </a>
    </div>
    <!-- Right: Actions -->
    <div class="flex items-center gap-2 sm:gap-3">
        <!-- Quick search trigger -->
        <button id="quick-search-trigger" type="button"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-lg border border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))] text-sm font-medium text-[rgb(var(--ui-text))] shadow-sm hover:bg-[rgb(var(--ui-surface-muted))]"
            aria-haspopup="dialog" aria-controls="quick-search-dialog" aria-label="Open search (⌘K / Ctrl+K)">
            <i class="fa-solid fa-magnifying-glass text-[rgb(var(--ui-text-subtle))]"></i>
        </button>

        <!-- Notifications -->
        <button type="button"
            class="text-[rgb(var(--ui-text-subtle))] hover:text-[rgb(var(--ui-primary))] transition p-2 rounded-full hover:bg-[rgb(var(--ui-surface-muted))]"
            aria-label="Notifications">
            <i class="fas fa-bell text-lg leading-none"></i>
        </button>

        @if ($chatInboxUrl)
            <a href="{{ $chatInboxUrl }}"
                class="relative text-[rgb(var(--ui-text-subtle))] hover:text-[rgb(var(--ui-primary))] transition p-2 rounded-full hover:bg-[rgb(var(--ui-surface-muted))]"
                aria-label="Inbox">
                <i class="fa-regular fa-comment-dots text-lg leading-none"></i>
            </a>
        @endif

        <!-- Profile / Logout -->
        <div class="flex items-center gap-2 sm:gap-3">
            @auth
                @if ($profileRoute)
                    <a href="{{ $profileRoute }}"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-[rgb(var(--ui-primary))] text-white flex items-center justify-center font-semibold shadow-md hover:bg-[rgb(var(--ui-primary-hover))] transition text-sm sm:text-base">
                        {{ $userInitial }}
                    </a>
                @else
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-[rgb(var(--ui-primary))] text-white flex items-center justify-center font-semibold shadow-md text-sm sm:text-base">
                        {{ $userInitial }}
                    </div>
                @endif

                <div class="flex flex-col leading-tight hidden sm:flex">
                    {{-- Line 1: User Name (clickable if profile route exists) --}}
                    @if ($profileRoute)
                        <a href="{{ $profileRoute }}"
                            class="text-sm font-medium text-[rgb(var(--ui-text))] hover:text-[rgb(var(--ui-primary))]">
                            {{ $name }}
                        </a>
                    @else
                        <span class="text-sm font-medium text-[rgb(var(--ui-text))]">
                            {{ $name }}
                        </span>
                    @endif

                    {{-- Line 2: Tenant Company Name (if any) --}}
                    @if (!empty($tenantName))
                        <span class="text-[11px] text-[rgb(var(--ui-text-subtle))] italic -mt-0.5">
                            {{ $tenantName }}
                        </span>
                    @endif

                    {{-- Line 3: Platform Owner OR Job Title OR Role --}}
                    {{-- Line 3: Platform Owner OR Job Title OR Role --}}
                    @if ($isPlatformOwner)
                        <span class="text-[11px] text-[rgb(var(--ui-text-subtle))] italic -mt-0.5">
                            Platform Owner
                        </span>
                    @else
                        @if (!empty($jobTitle))
                            <span class="text-[11px] text-[rgb(var(--ui-text-subtle))] italic -mt-0.5">
                                {{ $jobTitle }}
                            </span>
                        @elseif (!empty($roleLabel))
                            <span class="text-[11px] text-[rgb(var(--ui-text-subtle))] italic -mt-0.5">
                                {{ $roleLabel }}
                            </span>
                        @endif
                    @endif
                </div>


                <!-- Logout icon with tooltip (hidden on very small screens) -->
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="relative group hidden sm:flex items-center p-2 rounded-lg text-[#222222] hover:text-[rgb(var(--ui-text))] hover:bg-[rgb(var(--ui-surface-muted))] transition"
                    aria-label="Logout">
                    <i class="fas fa-right-from-bracket text-lg leading-none text-[#222222]"></i>
                    <span
                        class="pointer-events-none absolute top-full mt-2 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-black/90 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 invisible group-hover:visible transition">
                        Logout
                    </span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>

                <!-- Mobile logout icon (inline, smaller tap target to fit 425px) -->
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="relative group sm:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg text-[#222222] hover:text-[rgb(var(--ui-text))] hover:bg-[rgb(var(--ui-surface-muted))] transition"
                    aria-label="Logout">
                    <i class="fas fa-right-from-bracket text-base leading-none text-[#222222]"></i>
                </a>
            @endauth
        </div>
    </div>

</header>

{{-- Quick-search modal --}}
<div id="quick-search-overlay"
    class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden flex items-start justify-center px-3 sm:px-6 lg:pl-64 lg:pr-6 pt-20 sm:pt-24"
    data-quick-url="{{ $quickSearchUrl }}">
    <div id="quick-search-dialog"
        class="w-full max-w-full sm:max-w-[70vw] lg:max-w-2xl rounded-2xl bg-[rgb(var(--ui-surface))] shadow-3xl border border-[rgb(var(--ui-border)/0.7)] overflow-hidden mx-auto"
        role="dialog" aria-modal="true" aria-labelledby="quick-search-title">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-[rgb(var(--ui-border)/0.7)] bg-[rgb(var(--ui-surface))]">
            <i class="fa-solid fa-magnifying-glass text-[rgb(var(--ui-text-subtle))]"></i>
            <input id="quick-search-input" type="search" placeholder="Search projects, contacts, tasks…"
                class="flex-1 h-10 bg-transparent text-[rgb(var(--ui-text))] placeholder:text-[rgb(var(--ui-text-subtle))] focus:outline-none"
                autocomplete="off" />
            <button id="quick-search-close" type="button"
                class="h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-[rgb(var(--ui-surface-muted))]"
                aria-label="Close search">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="px-4 py-5 text-sm text-[rgb(var(--ui-text-subtle))] space-y-3 bg-[rgb(var(--ui-surface-muted))/0.6]">
            <div id="quick-search-status" class="text-xs text-[rgb(var(--ui-text-subtle))]">Type to search…</div>
            <ul id="quick-search-results"
                class="divide-y divide-[rgb(var(--ui-border)/0.7)] max-h-[60vh] overflow-y-auto rounded-xl border border-[rgb(var(--ui-border)/0.6)] bg-[rgb(var(--ui-surface))] shadow-inner">
            </ul>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('theme-toggle');
            if (!btn) return;

            const storageKey = 'renlo-theme';

            const syncUI = () => {
                const isDark = document.documentElement.classList.contains('dark');
                btn.classList.toggle('is-dark', isDark);
                btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            };

            btn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                const theme = isDark ? 'dark' : 'light';
                document.documentElement.dataset.theme = theme;
                localStorage.setItem(storageKey, theme);
                syncUI();
            });

            // make sure it matches whatever your <head> script decided on first paint
            syncUI();
        })();

        (function() {
            const overlay = document.getElementById('quick-search-overlay');
            const dialog = document.getElementById('quick-search-dialog');
            const input = document.getElementById('quick-search-input');
            const trigger = document.getElementById('quick-search-trigger');
            const closeBtn = document.getElementById('quick-search-close');
            const resultsEl = document.getElementById('quick-search-results');
            const statusEl = document.getElementById('quick-search-status');
            const quickUrl = overlay?.dataset.quickUrl || null;
            let activeIndex = -1;
            let currentResults = [];
            let fetchCtrl = null;
            let debounce;

            const openSearch = () => {
                if (!overlay) return;
                overlay.classList.remove('hidden');
                setTimeout(() => input?.focus(), 10);
            };
            const closeSearch = () => {
                overlay?.classList.add('hidden');
            };

            trigger?.addEventListener('click', () => openSearch());
            closeBtn?.addEventListener('click', () => closeSearch());
            overlay?.addEventListener('click', (e) => {
                if (e.target === overlay) closeSearch();
            });
            document.addEventListener('keydown', (e) => {
                const isMetaK = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k';
                if (isMetaK) {
                    e.preventDefault();
                    openSearch();
                }
                if (e.key === 'Escape' && !overlay?.classList.contains('hidden')) {
                    closeSearch();
                }

                if (!overlay || overlay.classList.contains('hidden')) return;
                if (['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
                if (e.key === 'ArrowDown') {
                    activeIndex = Math.min(activeIndex + 1, currentResults.length - 1);
                    highlight();
                } else if (e.key === 'ArrowUp') {
                    activeIndex = Math.max(activeIndex - 1, 0);
                    highlight();
                } else if (e.key === 'Enter' && activeIndex >= 0) {
                    window.location.href = currentResults[activeIndex]?.url;
                }
            });

            const render = () => {
                resultsEl.innerHTML = '';
                currentResults.forEach((item, idx) => {
                const li = document.createElement('li');
                li.className =
                        'flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-[rgb(var(--ui-surface-muted))] ' +
                        (idx === activeIndex ? 'bg-[rgb(var(--ui-surface-muted))/0.7]' : '');
                li.dataset.index = idx;
                li.innerHTML = `
                        <div class="text-[11px] uppercase text-[rgb(var(--ui-text-subtle))] w-16 pt-0.5">${item.type}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-[rgb(var(--ui-text))] truncate">${item.title}</div>
                            <div class="text-xs text-[rgb(var(--ui-text-subtle))] truncate">${item.subtitle || ''}</div>
                        </div>
                    `;
                    li.addEventListener('click', () => window.location.href = item.url);
                    li.addEventListener('mouseenter', () => {
                        activeIndex = idx;
                        highlight();
                    });
                    resultsEl.appendChild(li);
                });
                highlight();
            };

            const highlight = () => {
                [...resultsEl.children].forEach((el, i) => {
                    el.classList.toggle('bg-surface-accent/70', i === activeIndex);
                });
            };

            const search = (term) => {
                if (!quickUrl) {
                    statusEl.textContent = 'Search endpoint not configured.';
                    return;
                }
                if (fetchCtrl) fetchCtrl.abort();
                fetchCtrl = new AbortController();
                statusEl.textContent = 'Searching…';
                fetch(`${quickUrl}?q=${encodeURIComponent(term)}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: fetchCtrl.signal
                    })
                    .then(r => r.ok ? r.json() : {
                        results: []
                    })
                    .then(data => {
                        currentResults = data.results || [];
                        activeIndex = currentResults.length ? 0 : -1;
                        statusEl.textContent = currentResults.length ? '' : 'No results';
                        render();
                    })
                    .catch(() => {
                        statusEl.textContent = 'Search failed';
                    });
            };

            input?.addEventListener('input', () => {
                const term = input.value.trim();
                clearTimeout(debounce);
                if (term.length < 2) {
                    currentResults = [];
                    activeIndex = -1;
                    render();
                    statusEl.textContent = term.length ? 'Keep typing…' : 'Type to search…';
                    return;
                }
                debounce = setTimeout(() => search(term), 200);
            });
        })();
    </script>
@endpush
