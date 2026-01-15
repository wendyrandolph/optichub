<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="min-h-screen bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
    {{-- header/top nav (if you keep it as a partial too, update the include) --}}
    @includeIf('partials.navigation')

    <div class="flex">
        {{-- left sidebar --}}
        @includeIf('partials.sidebar')

        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>

    {{-- footer --}}
    @includeIf('partials.footer')

    @stack('scripts')
</body>

</html>
