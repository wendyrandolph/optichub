<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Renlo'))</title>
    @hasSection('meta')
        @yield('meta')
    @endif
    @stack('head')
    @vite(['resources/css/marketing.css', 'resources/js/app.js'])
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
</head>

<body class="marketing trial-body">
    @yield('content')
</body>

</html>
