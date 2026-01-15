@php
    $job = $appointment->job;
    $clientName = trim(($job?->client?->firstName ?? '') . ' ' . ($job?->client?->lastName ?? '')) ?: 'Customer';
    $location = $job?->serviceLocation;
@endphp

Hello {{ $clientName }},

This is a reminder that your appointment is scheduled for {{ $appointment->start_at->format('M j, Y g:i A') }}.

@if ($location)
Location: {{ $location->label ?? 'Service location' }} — {{ $location->address_line1 }}{{ $location->address_line2 ? ', ' . $location->address_line2 : '' }}, {{ $location->city }}, {{ $location->state }} {{ $location->postal_code }}
@endif

If you need to reschedule, please reply to this email.

Thanks,
{{ $tenant->name ?? 'Renlo' }}
