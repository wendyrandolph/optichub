<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Renlo'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/renlomicroicon.png') }}">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))] marketing">
    <main class="marketing bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">@yield('content')</main>

    <footer class="flex items-center border-t border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))]">
        <div class="max-w-5xl mx-auto px-6 py-6 text-xs text-[rgb(var(--ui-text-subtle))]">
            Powered by Renlo
        </div>
    </footer>
</body>

</html>
