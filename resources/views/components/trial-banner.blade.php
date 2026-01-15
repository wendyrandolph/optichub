{{-- resources/views/components/trial-banner.blade.php --}}
@props(['tenant'])

@php
    $info = $tenant?->getTrialInfo();
    $status = $tenant?->getTenantAccessStatus();
@endphp

@if ($tenant && $status && ($status['is_trialing'] ?? false))
    <div
        class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-900 flex items-center justify-between">
        <div>
            Trial ends {{ optional($info['ends_at'] ?? null)->toFormattedDateString() }} —
            <strong>{{ $info['days_left'] }} day(s)</strong> left.
        </div>
        <a href="{{ route('billing.paywall') }}" class="underline font-medium">Upgrade now</a>
    </div>
@endif
