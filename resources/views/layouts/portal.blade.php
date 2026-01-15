{{-- resources/views/layouts/portal.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;

    $user = Auth::guard('client')->user();
    $client = $client ?? null;
    $tenant = $tenant ?? (optional($client)->tenant ?? (optional($user)->tenant ?? null));
    $portalTheme = $portalTheme ?? [];

    $pageTitle = trim(($title ?? '') . ' | ' . config('app.name', 'Renlo'), ' |');

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

<body class="min-h-screen flex flex-col bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]"
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

    {{-- Top header (portal, mobile-only) --}}
    <header class="lg:hidden border-b border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                {{-- Mobile toggle for sidebar --}}
                <button type="button"
                    class="lg:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border-default bg-surface-card hover:bg-surface-accent text-text-subtle hover:text-text-base"
                    aria-label="Open sidebar"
                    data-portal-sidebar-toggle>
                    <i class="fa-solid fa-bars text-sm text-text-base"></i>
                </button>

                <div class="flex flex-col min-w-0">
                    <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 min-w-0"
                        aria-label="{{ $portalTheme['brand_name'] ?? 'Renlo' }} home">
                        @if (!empty($portalTheme['logo_light']))
                            <img src="{{ $portalTheme['logo_light'] }}" alt="{{ $portalTheme['brand_name'] ?? 'Renlo' }}"
                                class="h-5 w-auto block dark:hidden">
                            <img src="{{ $portalTheme['logo_dark'] ?? $portalTheme['logo_light'] }}"
                                alt="{{ $portalTheme['brand_name'] ?? 'Renlo' }}" class="h-5 w-auto hidden dark:block">
                        @else
                            <span class="text-base font-semibold select-none text-[rgb(var(--ui-primary))]">
                                {{ $portalTheme['brand_name'] ?? 'Renlo' }}
                            </span>
                        @endif
                        @if (!empty($tenant?->name))
                            <span class="text-xs text-text-subtle truncate">• {{ $tenant->name }}</span>
                        @endif
                    </a>
                    <span class="text-[11px] uppercase tracking-wide text-text-subtle">
                        Secure Client Portal
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3 text-xs">
                @if ($user)
                    <a href="{{ route('portal.messages.index') }}"
                        class="relative inline-flex items-center justify-center h-8 w-8 rounded-full border border-border-default bg-surface-card text-text-subtle hover:text-text-base hover:bg-surface-accent"
                        aria-label="Messages">
                        <i class="fa-solid fa-envelope text-[12px] text-text-base"></i>
                        @if (!empty($portalUnreadCount))
                            <span
                                class="absolute -top-1 -right-1 h-4 min-w-[1rem] rounded-full bg-[rgb(var(--brand-primary))] px-1 text-[10px] font-semibold text-white flex items-center justify-center">
                                {{ $portalUnreadCount }}
                            </span>
                        @endif
                    </a>
                    <div class="hidden sm:block text-right leading-tight text-[rgb(var(--ui-text))]">
                        <div class="text-xs text-text-subtle">Client</div>
                        <div class="font-medium">{{ $user->first_name ?? ($client->firstName ?? '') }}</div>
                    </div>
                    @php
                        $first = $user->first_name ?? $client?->firstName ?? $client?->first_name ?? '';
                        $last = $user->last_name ?? $client?->lastName ?? $client?->last_name ?? '';
                        $email = $user->email ?? '';
                        $initials = strtoupper(
                            mb_substr($first, 0, 1) .
                                mb_substr($last, 0, 1)
                        );
                        if ($initials === '' && $email !== '') {
                            $initials = strtoupper(mb_substr($email, 0, 1));
                        }
                        $initials = $initials ?: 'C';
                    @endphp
                    <div
                        class="h-8 w-8 rounded-full bg-[rgb(var(--tenant-primary))] text-[rgb(var(--tenant-on-primary))] flex items-center justify-center text-xs font-semibold ring-1 ring-[rgba(var(--tenant-primary),0.25)]"
                        aria-label="Client">
                        {{ $initials }}
                    </div>
                @else
                    <a href="{{ route('portal.login') }}" class="oh-btn">Sign in</a>
                @endif
            </div>
        </div>
    </header>

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
            <div class="flex flex-wrap items-center gap-2">
                <span>© {{ now()->year }} {{ config('app.name', 'Renlo') }}. All rights reserved.</span>
                @if (!empty($tenant?->name))
                    <span class="hidden sm:inline">·</span>
                    <span>{{ $tenant->name }}</span>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @php
                    $supportEmail = $tenant?->support_email ?? config('mail.from.address');
                @endphp
                @if ($supportEmail)
                    <a href="mailto:{{ $supportEmail }}" class="hover:text-text-base">Support</a>
                @endif
                <a href="{{ route('privacy') }}" class="hover:text-text-base">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-text-base">Terms</a>
                <span class="hidden sm:inline">·</span>
                <span>Powered by {{ config('app.name', 'Renlo') }}</span>
            </div>
        </div>
    </footer>

    {{-- Tiny JS for mobile sidebar toggle (no framework required) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('[data-portal-sidebar]');
            const openBtns = document.querySelectorAll('[data-portal-sidebar-toggle]');
            const closeBtn = document.querySelector('[data-portal-sidebar-close]');
            const backdrop = document.querySelector('[data-portal-backdrop]');

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
        });
    </script>
</body>

</html>
