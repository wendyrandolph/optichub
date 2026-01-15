@php
    $tenant = $tenant ?? (auth()->user()?->tenant ?? null);
    $workspaceType = $tenant?->workspace_type ?? 'creative';
    $role = strtolower((string) (auth()->user()?->role ?? ''));
@endphp

@if ($workspaceType === 'trades')
    @if (auth()->user()?->isTech())
        @include('trades.partials.nav-field', ['tenant' => $tenant])
    @else
        @include('trades.partials.nav-office', ['tenant' => $tenant])
    @endif
@endif
