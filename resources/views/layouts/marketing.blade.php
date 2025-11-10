<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Optic Hub'))</title>
    @hasSection('meta')
        @yield('meta')
    @endif
    @stack('head')
    @vite(['resources/css/marketing.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-gray-900">
    {{-- Simple public header --}}
    <header class="border-b">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('marketing.home') }}" class="font-extrabold text-xl">
                <span class="text-blue-900">Optic</span><span class="text-green-600"> Hub</span>
            </a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('marketing.features') }}" class="hover:text-indigo-600">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="hover:text-indigo-600">Pricing</a>
                <a href="{{ route('marketing.faq') }}" class="hover:text-indigo-600">FAQ</a>
                <a href="{{ route('marketing.about') }}" class="hover:text-indigo-600">About</a>
                <a href="{{ route('contact.form') }}" class="hover:text-indigo-600">Contact</a>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500">Login</a>
            </nav>
        </div>
    </header>

    <main class="marketing">@yield('content')</main>

    <footer class="mt-16 border-t">
        <div class="max-w-6xl mx-auto px-6 py-10 text-sm text-gray-500">
            © {{ date('Y') }} Optic Hub. All rights reserved.
        </div>
    </footer>
</body>

</html>
