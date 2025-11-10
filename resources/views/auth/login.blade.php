<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Optic Hub') }} | {{ __('Login') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 text-gray-800 antialiased">
    <main class="w-full max-w-md">
        <div class="relative overflow-hidden rounded-3xl border border-border-light bg-white p-8 shadow-xl shadow-blue-900/5">
            <div class="absolute -right-20 -top-20 h-40 w-40 rounded-full bg-blue-100 blur-3xl"></div>
            <div class="absolute -left-24 -bottom-24 h-48 w-48 rounded-full bg-green-100 blur-3xl"></div>

            <div class="relative text-center">
                <div class="inline-flex items-baseline gap-2 text-3xl font-extrabold tracking-wide">
                    <span class="text-primary">Optic</span>
                    <span class="text-kpi-green">Hub</span>
                </div>
                <h1 class="mt-4 text-2xl font-semibold text-gray-900">{{ __('Welcome Back') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Sign in to manage your workspace.') }}</p>
            </div>

            @if ($errors->any())
                <div class="relative mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ __('Authentication failed. Please check your email and password.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="relative mt-8 space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                        {{ __('Email Address') }}
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full rounded-2xl border border-border-light bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                        {{ __('Password') }}
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="w-full rounded-2xl border border-border-light bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="group relative flex w-full items-center justify-center gap-2 rounded-2xl bg-primary py-3 text-sm font-semibold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                >
                    {{ __('Login') }}
                </button>
            </form>
        </div>
    </main>
</body>
</html>
