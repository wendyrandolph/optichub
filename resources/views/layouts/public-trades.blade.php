<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Quote') | {{ config('app.name', 'Renlo') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand-primary: {{ $brand['primary'] ?? '#0B1F52' }};
            --brand-secondary: {{ $brand['secondary'] ?? '#111827' }};
        }
        @media print {
            .print-hidden {
                display: none !important;
            }
            body {
                background: white;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
    <header class="border-b border-border-default bg-surface-card">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <img src="{{ $brand['logo'] ?? asset('images/renlo.svg') }}" alt="{{ $brand['name'] ?? 'Renlo' }}"
                    class="h-9 w-auto shrink-0">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-text-base truncate">
                        {{ $brand['name'] ?? 'Renlo' }}
                    </div>
                    @if (!empty($brand['location']))
                        <div class="text-xs text-text-subtle truncate">{{ $brand['location'] }}</div>
                    @endif
                </div>
            </div>
            <div class="text-xs text-text-subtle sm:text-right space-y-1">
                @if (!empty($brand['support_email']))
                    <div>{{ $brand['support_email'] }}</div>
                @endif
                @if (!empty($brand['support_phone']))
                    <div>{{ $brand['support_phone'] }}</div>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-border-default bg-surface-card">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-xs text-text-subtle flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                {{ $brand['name'] ?? 'Renlo' }}
            </div>
            <div class="space-x-2">
                @if (!empty($brand['support_email']))
                    <span>{{ $brand['support_email'] }}</span>
                @endif
                @if (!empty($brand['support_phone']))
                    <span>{{ $brand['support_phone'] }}</span>
                @endif
                <span>Powered by Renlo</span>
            </div>
        </div>
    </footer>
</body>
</html>
