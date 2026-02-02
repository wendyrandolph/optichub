<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Renlo') }}</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="{{ asset('images/renlomicroicon.png') }}">

    <!-- Add more sizes if needed -->
    <script>
        (function() {
            try {
                const storageKey = 'renlo-theme';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

<body class="optic bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
    @php
        $currentAdmin = auth('admin')->user() ?? auth()->user();
        $tenant = $currentAdmin?->tenant;
        $routeTenant = request()->route('tenant') ?? null;

        if (!$tenant && $routeTenant) {
            // If provider is viewing a tenant-scoped route, pull branding from the route model/param
            $tenant =
                $routeTenant instanceof \App\Models\Tenant ? $routeTenant : \App\Models\Tenant::find($routeTenant);
        }

        $hexToRgb = function (?string $hex, string $fallback = '#1C2E70') {
            $h = ltrim($hex ?: $fallback, '#');
            if (strlen($h) === 3) {
                $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            }
            $int = hexdec($h);
            $r = ($int >> 16) & 255;
            $g = ($int >> 8) & 255;
            $b = $int & 255;
            return "{$r} {$g} {$b}";
        };

        $tenantPrimaryHex = $tenant ? $tenant->brandColorHex('primary') : '#1C2E70';
        $tenantSecondaryHex = $tenant ? $tenant->brandColorHex('secondary') : '#172554';
        $tenantNeutralHex = $tenant ? $tenant->brandColorHex('neutral') : '#3a3f4b';

        $tenantPrimary = $tenant ? $tenant->brandColorRgb('primary') : '28 46 112';
        $tenantSecondary = $tenant ? $tenant->brandColorRgb('secondary') : '23 37 84';
        $tenantNeutral = $tenant ? $tenant->brandColorRgb('neutral') ?? '58 63 75' : '58 63 75';

        $primaryForContrast = $hexToRgb($tenantPrimaryHex);
        [$pr, $pg, $pb] = array_map('intval', explode(' ', $primaryForContrast));
        $luminance = 0.2126 * ($pr / 255) + 0.7152 * ($pg / 255) + 0.0722 * ($pb / 255);
        $tenantOnPrimary = $luminance > 0.6 ? '0 0 0' : '255 255 255';
    @endphp

    <style>
        :root {
            --tenant-primary: {{ $tenantPrimary }};
            --tenant-secondary: {{ $tenantSecondary }};
            --tenant-neutral: {{ $tenantNeutral }};
            --tenant-on-primary: {{ $tenantOnPrimary }};

            /* Hex variants for components that expect hex values */
            --tenant-primary-hex: {{ $tenantPrimaryHex }};
            --tenant-secondary-hex: {{ $tenantSecondaryHex }};
            --tenant-neutral-hex: {{ $tenantNeutralHex }};
        }
    </style>

    {{-- Mobile sidebar backdrop --}}
    <div id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-black/50 opacity-0 pointer-events-none transition lg:hidden">
    </div>

    {{-- Fixed sidebar lives outside the normal flow --}}
    @include('partials.sidebar')

    {{-- Main content wrapper shifts right to make room for the fixed sidebar. --}}
    <div id="main-content-wrapper" class="min-h-screen flex flex-col pl-0 animate-fade-in-up">


        @include('partials.header')

        <main class="flex-1">
            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div data-flash
                        class="mb-4 relative rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 pr-10 text-sm text-emerald-800">
                        {{ session('status') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-emerald-700 hover:text-emerald-900">x</button>
                    </div>
                @elseif (session('success'))
                    <div data-flash
                        class="mb-4 relative rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 pr-10 text-sm text-emerald-800">
                        {{ session('success') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-emerald-700 hover:text-emerald-900">x</button>
                    </div>
                @elseif (session('error'))
                    <div data-flash
                        class="mb-4 relative rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 pr-10 text-sm text-rose-800">
                        {{ session('error') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-rose-700 hover:text-rose-900">x</button>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>

        @include('partials.footer')
    </div>

    @include('partials.cookie-consent')

    {{-- Sidebar behavior handled in resources/js/app.js --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-flash]').forEach((banner) => {
                const close = banner.querySelector('[data-flash-close]');
                if (close) {
                    close.addEventListener('click', () => banner.remove());
                }
                setTimeout(() => {
                    if (document.body.contains(banner)) {
                        banner.remove();
                    }
                }, 4500);
            });
        });
    </script>
    @stack('modals')
    @stack('scripts')
</body>

</html>
