<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Renlo') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/renlomicroicon.png') }}">

    <script>
        (function() {
            try {
                const storageKey = 'renlo-theme';
                const saved = localStorage.getItem(storageKey);
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const initial = saved ? saved : (systemDark ? 'dark' : 'light');

                if (initial === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');

                document.documentElement.dataset.theme = initial;

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
        #main-content-wrapper {
            transition: padding-left 300ms ease;
        }
    </style>
    @stack('head')
</head>

<body class="optic bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
    @php
        $currentAdmin = auth('admin')->user() ?? auth()->user();
        $tenant = $currentAdmin?->tenant;
        $routeTenant = request()->route('tenant') ?? null;

        if (!$tenant && $routeTenant) {
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

            --tenant-primary-hex: {{ $tenantPrimaryHex }};
            --tenant-secondary-hex: {{ $tenantSecondaryHex }};
            --tenant-neutral-hex: {{ $tenantNeutralHex }};
        }
    </style>

    <div id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-black/50 opacity-0 pointer-events-none transition lg:hidden">
    </div>

    @include('trades.partials.nav')

    <div id="main-content-wrapper" class="min-h-screen flex flex-col pl-0 animate-fade-in-up">
        @include('partials.header')

        <main class="flex-1">
            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div data-flash
                        class="mb-4 relative rounded-xl bg-[rgba(var(--ui-success),0.1)] text-[rgb(var(--ui-success))] p-3 pr-10 ring-1 ring-[rgba(var(--ui-success),0.2)]">
                        {{ session('status') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-[rgb(var(--ui-success))] hover:text-text-base">x</button>
                    </div>
                @elseif (session('success'))
                    <div data-flash
                        class="mb-4 relative rounded-xl bg-[rgba(var(--ui-success),0.1)] text-[rgb(var(--ui-success))] p-3 pr-10 ring-1 ring-[rgba(var(--ui-success),0.2)]">
                        {{ session('success') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-[rgb(var(--ui-success))] hover:text-text-base">x</button>
                    </div>
                @elseif (session('error'))
                    <div data-flash
                        class="mb-4 relative rounded-xl bg-[rgba(var(--ui-danger),0.1)] text-[rgb(var(--ui-danger))] p-3 pr-10 ring-1 ring-[rgba(var(--ui-danger),0.2)]">
                        {{ session('error') }}
                        <button type="button" data-flash-close aria-label="Dismiss"
                            class="absolute right-3 top-3 text-xs text-[rgb(var(--ui-danger))] hover:text-text-base">x</button>
                    </div>
                @endif
                @yield('trades-content')
            </div>
        </main>

        @include('partials.footer')
    </div>

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
    @stack('scripts')
</body>

</html>
