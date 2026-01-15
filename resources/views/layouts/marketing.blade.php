<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Renlo'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/renlomicroicon.png') }}">
    @hasSection('meta')
        @yield('meta')
    @endif
    @stack('head')
    @vite(['resources/css/marketing.css', 'resources/js/app.js'])
    <!-- Favicons -->

</head>

<body class="min-h-screen bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))] marketing">
    {{-- Simple public header --}}
    <header class="border-b border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))]">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('marketing.home') }}" class="font-extrabold text-xl">
                <img src="{{ asset('images/renlo.svg') }}" alt="Renlo" class="h-9 w-auto block">
            </a>
            @php
                $isLoggedIn = auth()->check() || auth('admin')->check() || auth('client')->check();
            @endphp
            <nav class="flex items-center gap-6 text-sm text-[rgb(var(--ui-text))]">
                <a href="{{ route('marketing.features') }}" class="hover:text-[rgb(var(--ui-primary))]">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="hover:text-[rgb(var(--ui-primary))]">Pricing</a>
                <a href="{{ route('marketing.faq') }}" class="hover:text-[rgb(var(--ui-primary))]">FAQ</a>
                <a href="{{ route('marketing.about') }}" class="hover:text-[rgb(var(--ui-primary))]">About</a>
                <a href="{{ route('contact.form') }}" class="hover:text-[rgb(var(--ui-primary))]">Contact</a>
                @if ($isLoggedIn)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--primary">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn--primary">
                        Login
                    </a>
                @endif
            </nav>
        </div>
    </header>

    <main class="marketing bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">@yield('content')</main>

    <footer class="flex items-center border-t border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))]">
        <div class="max-w-6xl mx-auto px-6 py-10 text-sm text-[rgb(var(--ui-text-subtle))]">
            © {{ date('Y') }} Renlo. All rights reserved.
        </div>
    </footer>
</body>

<script>
    // Ensure the login link always navigates (guard against extensions/interceptors)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-force-nav="true"]');
        if (!link) return;
        if (e.defaultPrevented) return;
        e.preventDefault();
        window.location.assign(link.href);
    });
</script>
</html>
