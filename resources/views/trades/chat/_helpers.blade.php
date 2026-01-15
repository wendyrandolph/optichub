@php
    $tenantTz = $tenant->timezone ?? config('app.timezone');

    $formatTime = fn($dt) => $dt ? \Carbon\Carbon::parse($dt)->timezone($tenantTz)->format('g:ia') : '';

    $formatDay = fn($dt) => $dt
        ? (\Carbon\Carbon::parse($dt)->timezone($tenantTz)->isToday()
            ? 'Today'
            : \Carbon\Carbon::parse($dt)->timezone($tenantTz)->format('M j, Y'))
        : '';
@endphp
