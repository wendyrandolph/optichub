{{-- resources/views/layouts/portal.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;

    $user = Auth::guard('client')->user();
    $client = $client ?? null;
    $tenant = $tenant ?? (optional($client)->tenant ?? (optional($user)->tenant ?? null));
    $portalTheme = $portalTheme ?? [];
    $tenantName = $tenant?->name ?? $tenant?->company_name ?? 'Workspace';
    $renloName = config('app.name', 'Renlo');
    $pageTitle = $renloName . ' Client Portal — ' . $tenantName;

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

    $brandPrimary = $portalTheme['primary'] ?? '#1C2E70';
    $brandHover = $portalTheme['primary_hover'] ?? '#172554';
    $brandAccent = $portalTheme['accent'] ?? '#8FAF9A';
    $tenantPrimary = $hexToRgb($brandPrimary);
    $tenantSecondary = $hexToRgb($brandHover);
    $tenantAccent = $hexToRgb($brandAccent);
    $tenantNeutral = '58 63 75';
    [$pr, $pg, $pb] = array_map('intval', explode(' ', $tenantPrimary));
    $luminance = 0.2126 * ($pr / 255) + 0.7152 * ($pg / 255) + 0.0722 * ($pb / 255);
    $tenantOnPrimary = $luminance > 0.6 ? '0 0 0' : '255 255 255';
    $tenantLogo = $portalTheme['logo_light'] ?? asset('images/renlo.svg');
    $renloLogo = asset('images/renlo.svg');
    $clientFirst = $user?->first_name ?? $client?->firstName ?? $client?->first_name ?? '';
    $clientLast = $user?->last_name ?? $client?->lastName ?? $client?->last_name ?? '';
    $clientEmail = $user?->email ?? '';
    $clientDisplay = trim($clientFirst . ' ' . $clientLast);
    $clientDisplay = $clientDisplay !== '' ? $clientDisplay : ($clientEmail ?: 'Client');
    $clientInitials = strtoupper(mb_substr($clientFirst, 0, 1) . mb_substr($clientLast, 0, 1));
    if ($clientInitials === '' && $clientEmail !== '') {
        $clientInitials = strtoupper(mb_substr($clientEmail, 0, 1));
    }
    $clientInitials = $clientInitials ?: 'C';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/renlomicroicon.png') }}">
</head>

<body class="min-h-screen flex flex-col bg-[rgba(var(--brand-primary),0.035)] text-[rgb(var(--ui-text))]"
    style="
        --brand-primary: {{ $tenantPrimary }};
        --brand-primary-hover: {{ $tenantSecondary }};
        --brand-secondary: {{ $tenantSecondary }};
        --brand-accent: {{ $tenantAccent }};
        --ui-primary: {{ $tenantPrimary }};
        --ui-primary-hover: {{ $tenantSecondary }};
        --ui-accent: {{ $tenantAccent }};
        --tenant-primary: {{ $tenantPrimary }};
        --tenant-secondary: {{ $tenantSecondary }};
        --tenant-neutral: {{ $tenantNeutral }};
        --tenant-on-primary: {{ $tenantOnPrimary }};
    ">

    {{-- Main area: sidebar + page content --}}
    <div class="flex-1 flex min-h-0 relative">
        <div class="lg:hidden fixed inset-0 bg-black/30 opacity-0 pointer-events-none transition-opacity duration-150"
            data-portal-backdrop></div>
        {{-- Sidebar --}}
        @if ($user)
            @include('partials.portal.sidebar', [
                'tenant' => $tenant ?? null,
                'client' => $client ?? null,
                'portalUnreadCount' => $portalUnreadCount ?? null,
            ])
        @endif

        {{-- Page content --}}
        <main class="flex-1 min-w-0">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @if (!empty($portalMagicLink) && $portalMagicLinkDaysLeft !== null && $portalMagicLinkDaysLeft <= 2 && $portalMagicLinkDaysLeft >= 0)
                    @php
                        $expiresAt = $portalMagicLink?->expires_at;
                        $secondsLeft = $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : null;
                        $interval = $secondsLeft !== null ? \Carbon\CarbonInterval::seconds($secondsLeft)->cascade() : null;
                        $parts = [];
                        if ($interval) {
                            if ($interval->days > 0) {
                                $parts[] = $interval->days . ' day' . ($interval->days === 1 ? '' : 's');
                            }
                            if ($interval->hours > 0) {
                                $parts[] = $interval->hours . ' hour' . ($interval->hours === 1 ? '' : 's');
                            }
                            $minutes = max(1, (int) $interval->minutes);
                            $parts[] = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
                        }
                        $countdown = $parts ? implode(' ', $parts) : 'less than a minute';
                    @endphp
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Your access link expires in {{ $countdown }}.
                        Please ask your provider to send a fresh link to keep your portal access active.
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    {{-- Footer stuck to bottom when content is short --}}
    <footer class="border-t border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))]">
        <div
            class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-xs text-[rgb(var(--ui-text-subtle))] flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                @php
                    $supportEmail = $tenant?->support_email ?? config('mail.from.address');
                @endphp
                @if ($supportEmail)
                    <a href="mailto:{{ $supportEmail }}" class="hover:text-text-base">Support</a>
                @endif
                <a href="{{ route('privacy') }}" class="hover:text-text-base">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-text-base">Terms</a>
                <a href="#" data-cookie-settings class="hover:text-text-base">Cookie settings</a>
            </div>
            <div class="flex items-center gap-2">
                <img src="{{ $renloLogo }}" alt="{{ $renloName }}" class="h-3 w-auto opacity-70">
                <span>Powered by {{ $renloName }}</span>
            </div>
        </div>
    </footer>

    @include('partials.cookie-consent')

    {{-- Tiny JS for mobile sidebar toggle (no framework required) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('[data-portal-sidebar]');
            const openBtns = document.querySelectorAll('[data-portal-sidebar-toggle]');
            const closeBtn = document.querySelector('[data-portal-sidebar-close]');
            const backdrop = document.querySelector('[data-portal-backdrop]');
            const notificationsToggle = document.querySelector('[data-portal-notifications-toggle]');
            const notificationsMenu = document.querySelector('[data-portal-notifications-menu]');

            if (!sidebar) return;

            const open = () => {
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.remove('opacity-0', 'pointer-events-none');
                    backdrop.classList.add('opacity-100');
                }
            };
            const close = () => {
                sidebar.classList.add('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    backdrop.classList.remove('opacity-100');
                }
            };

            openBtns.forEach(btn => btn.addEventListener('click', () => {
                // if already open and on mobile, close
                if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
                    close();
                } else {
                    open();
                }
            }));

            if (closeBtn) closeBtn.addEventListener('click', close);
            if (backdrop) backdrop.addEventListener('click', close);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') close();
            });

            // Ensure sidebar is visible on desktop, off-canvas on mobile
            const handleResize = () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                    if (backdrop) {
                        backdrop.classList.add('opacity-0', 'pointer-events-none');
                        backdrop.classList.remove('opacity-100');
                    }
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            };
            handleResize();
            window.addEventListener('resize', handleResize);

            if (notificationsToggle && notificationsMenu) {
                const closeNotifications = () => {
                    notificationsMenu.classList.add('hidden');
                };
                const toggleNotifications = (event) => {
                    event.stopPropagation();
                    notificationsMenu.classList.toggle('hidden');
                    if (!notificationsMenu.classList.contains('hidden')) {
                        fetch("{{ route('portal.notifications.seen') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json',
                            },
                        }).catch(() => {});
                    }
                };

                notificationsToggle.addEventListener('click', toggleNotifications);
                document.addEventListener('click', (event) => {
                    if (!notificationsMenu.contains(event.target) && !notificationsToggle.contains(event.target)) {
                        closeNotifications();
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeNotifications();
                    }
                });
            }
        });
    </script>
</body>

</html>
