<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sign in') — Renlo</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen grid place-items-center bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
    <div class="w-full max-w-md bg-[rgb(var(--ui-surface))] border border-[rgb(var(--ui-border))] rounded-xl shadow p-6">
        @yield('content')
    </div>
</body>

</html>
