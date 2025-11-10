@php
    $user = auth()->user();
    $first = $user?->first_name;
    $last = $user?->last_name;
    // Build a safe display name: First Last, fallback to name, then Guest User
    $name = trim(($first ? $first : '') . ' ' . ($last ? $last : '')) ?: $user?->name ?? 'Guest User';
    // Initial from first name, fallback to name, then '?'
    $userInitial = strtoupper(mb_substr($first ?? ($user?->name ?? '?'), 0, 1));
@endphp

<header id="main-header" class="w-xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <button id="theme-toggle"
            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium
                 bg-surface-card/60 hover:bg-surface-card/90 text-text-base"
            type="button" aria-label="Toggle theme">
            <span class="theme-label">Theme</span>
            {{-- Simple sun/moon swap; swap with your icons if you prefer --}}
            <svg class="sun h-4 w-4 hidden dark:inline-block" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M6.76 4.84l-1.8-1.79L3.17 4.84l1.79 1.79 1.8-1.79zM1 13h3v-2H1v2zm10 10h2v-3h-2v3zM20.84 4.22l-1.79-1.79-1.79 1.79 1.79 1.8 1.79-1.8zM20 13h3v-2h-3v2zM11 1h2v3h-2V1zM6.76 19.16l-1.8 1.79 1.79 1.79 1.8-1.79-1.79-1.79zM17.24 19.16l1.79 1.79 1.79-1.79-1.79-1.79-1.79 1.79zM12 6a6 6 0 100 12A6 6 0 0012 6z" />
            </svg>
            <svg class="moon h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
            </svg>
        </button>
        <!-- Brand -->
        <a href="{{ url('/dashboard') }}" class="flex items-center h-full" aria-label="Optic Hub home">
            <span class="text-xl font-extrabold select-none">
                <span class="text-blue-900">Optic</span><span class="text-green-600"> Hub</span>
            </span>
        </a>
    </div>
    <!-- Right: Actions -->
    <div class="flex items-center gap-3">
        <!-- Notifications -->
        <button type="button" class="text-gray-500 hover:text-indigo-600 transition p-2 rounded-full hover:bg-gray-100"
            aria-label="Notifications">
            <i class="fas fa-bell text-lg leading-none"></i>
        </button>

        <!-- Profile / Logout -->
        <div class="flex items-center gap-2">
            @auth
                <span class="text-sm font-medium text-gray-700 hidden sm:inline">{{ $name }}</span>
                <div
                    class="w-9 h-9 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold text-sm shadow-md">
                    {{ $userInitial }}
                </div>

                <!-- Logout link with tooltip -->
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="relative group flex items-center p-2 rounded-lg text-red-500 hover:bg-red-50 transition"
                    aria-label="Logout">
                    <i class="fas fa-right-from-bracket text-lg leading-none"></i>
                    <span
                        class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-black/90 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 invisible group-hover:visible transition">
                        Logout
                    </span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            @endauth
        </div>
    </div>
</header>
@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('theme-toggle');
            if (!btn) return;

            const storageKey = 'optic-theme';

            btn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                const theme = isDark ? 'dark' : 'light';
                document.documentElement.dataset.theme = theme;
                localStorage.setItem(storageKey, theme);
            });
        })();
    </script>
@endpush
