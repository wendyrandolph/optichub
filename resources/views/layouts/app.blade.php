<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title . ' — ' : '' }}Optic Hub</title>
    <script>
        (function() {
            try {
                const storageKey = 'optic-theme';
                const saved = localStorage.getItem(storageKey); // 'light' | 'dark' | null
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const initial = saved ? saved : (systemDark ? 'dark' : 'light');

                if (initial === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');

                // keep data attribute for debugging / analytics if desired
                document.documentElement.dataset.theme = initial;

                // respond if user changes OS theme and no manual setting was chosen
                if (!saved) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                        const wantsDark = e.matches;
                        document.documentElement.classList.toggle('dark', wantsDark);
                        document.documentElement.dataset.theme = wantsDark ? 'dark' : 'light';
                    });
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Smooth content shift when toggling sidebar */
        #main-content-wrapper {
            transition: padding-left 300ms ease;
        }
    </style>
    @stack('head')
</head>

{{-- <body class="optic bg-surface-page text-text-base"> --}}

<body class="optic bg-optic-bg text-optic-text">
    {{-- Fixed sidebar lives outside the normal flow --}}
    @include('partials.sidebar')

    {{-- Main content wrapper shifts right to make room for the fixed sidebar. --}}
    <div id="main-content-wrapper" class="min-h-screen flex flex-col pl-64">
        @include('partials.header')

        <main class="flex-1 p-8">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-green-50 text-green-800 p-3 ring-1 ring-green-200">{{ session('status') }}
                </div>
            @endif
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    <script>
        // Sidebar <-> content coordination (Font Awesome chevron + Tailwind paddings)
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.getElementById('main-content-wrapper');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const chevron = toggleBtn ? toggleBtn.querySelector('i.fas') : null; // <i class="fas fa-chevron-left">

            const setState = (expanded) => {
                if (!sidebar || !mainWrapper) return;
                // Sidebar width classes
                sidebar.classList.toggle('w-64', expanded);
                sidebar.classList.toggle('w-20', !expanded);
                // Content left padding to match sidebar width
                mainWrapper.classList.toggle('pl-64', expanded);
                mainWrapper.classList.toggle('pl-20', !expanded);
                // Flip chevron direction for feedback (optional)
                if (chevron) {
                    chevron.classList.toggle('fa-chevron-left', expanded);
                    chevron.classList.toggle('fa-chevron-right', !expanded);
                }
                // Persist
                localStorage.setItem('sidebarExpanded', expanded ? 'true' : 'false');
            };

            // Initialize from storage (default: expanded)
            const stored = localStorage.getItem('sidebarExpanded');
            const initial = stored === null ? true : stored === 'true';
            setState(initial);

            // Attach toggle
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const isExpanded = sidebar.classList.contains('w-64');
                    setState(!isExpanded);
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
