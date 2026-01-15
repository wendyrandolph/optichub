@extends('layouts.trades')

@section('title', 'Appointment')

@section('trades-content')
    @php
        $job = $appointment->job;
        $clientName = trim(($job?->client?->firstName ?? '') . ' ' . ($job?->client?->lastName ?? '')) ?: 'Client';
        $jobSummary = $job?->summary ?? 'Job';
        $warnings = session('overlap_warnings', []);
        $availabilityWarnings = session('availability_warnings', []);
        $suggestedWarning = session('suggested_tech_warning');
        $location = $job?->serviceLocation;
        $addressParts = array_filter([
            $location?->address_line1,
            $location?->address_line2,
            trim(
                ($location?->city ?? '') .
                    ($location?->state ? ', ' . $location->state : '') .
                    ($location?->postal_code ? ' ' . $location->postal_code : ''),
            ),
        ]);
        $addressText = $addressParts ? implode(', ', $addressParts) : '';
        $mapUrl = $addressText ? 'https://maps.google.com/?q=' . urlencode($addressText) : null;

        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
        $startAt = $appointment->start_at?->timezone($tz);
        $endAt = $appointment->end_at?->timezone($tz);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $jobSummary }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $clientName }} · {{ $startAt?->format('M j, Y g:i A') }} -
                    {{ $endAt?->format('g:i A') }}
                </p>
                @if ($addressText)
                    <p class="text-xs text-text-subtle mt-1">
                        {{ $addressText }}
                        @if ($mapUrl)
                            <a href="{{ $mapUrl }}" class="ml-2 text-text-base hover:underline" target="_blank"
                                rel="noopener">Open in maps</a>
                        @endif
                    </p>
                @else
                    <p class="text-xs text-amber-700 mt-1">No service address on this job.</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                    class="oh-btn">Edit</a>
                <a href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey]) }}" class="oh-btn">Schedule</a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        @if (!empty($warnings))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <div class="font-semibold mb-2">Overlap warning</div>
                <ul class="space-y-1">
                    @foreach ($warnings as $warning)
                        <li>
                            {{ $warning['user'] }} overlaps with {{ $warning['job'] }}
                            ({{ $warning['start'] }} – {{ $warning['end'] }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($availabilityWarnings))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <div class="font-semibold mb-2">Availability warning</div>
                <ul class="space-y-1">
                    @foreach ($availabilityWarnings as $warning)
                        <li>
                            {{ $warning['name'] }} is unavailable ({{ implode(', ', $warning['reasons'] ?? []) }}).
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($suggestedWarning))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $suggestedWarning }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="oh-card p-5 lg:col-span-2 space-y-4">
                <div class="flex items-center gap-2 text-xs text-text-subtle">
                    <span class="oh-pill oh-pill--muted">{{ ucfirst($appointment->status) }}</span>
                    <span>Job: {{ $jobSummary }}</span>
                </div>
                <div class="text-sm text-text-base">
                    {{ $appointment->notes ?: 'No notes yet.' }}
                </div>
                <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                    Checklist and appointment details will surface here.
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-semibold text-text-base">Field notes</div>
                    @if ($appointment->fieldNotes->isEmpty())
                        <div class="text-sm text-text-subtle">No field notes yet.</div>
                    @else
                        <div class="space-y-2">
                            @foreach ($appointment->fieldNotes as $note)
                                @php
                                    $author =
                                        $note->user?->name ??
                                        trim(
                                            ($note->user?->first_name ?? '') . ' ' . ($note->user?->last_name ?? ''),
                                        ) ?:
                                        $note->user?->username ??
                                        'Tech';
                                @endphp
                                <div class="rounded-lg border border-border-default px-4 py-3 text-sm">
                                    <div class="text-text-base">{{ $note->note }}</div>
                                    <div class="text-xs text-text-subtle mt-2">
                                        {{ $author }} · {{ $note->created_at?->timezone($tz)->format('M j, g:i A') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-semibold text-text-base">Field photos</div>
                    @if ($appointment->fieldPhotos->isEmpty())
                        <div class="text-sm text-text-subtle">No photos yet.</div>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($appointment->fieldPhotos as $photo)
                                @php
                                    $photoAuthor =
                                        $photo->user?->name ??
                                        trim(
                                            ($photo->user?->first_name ?? '') . ' ' . ($photo->user?->last_name ?? ''),
                                        ) ?:
                                        $photo->user?->username ??
                                        'Tech';
                                @endphp
                                <li class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-text-base truncate">{{ $photo->original_name }}</span>
                                    <span class="text-xs text-text-subtle">
                                        {{ $photoAuthor }} · {{ $photo->created_at?->timezone($tz)->format('M j, g:i A') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <div class="space-y-4">
                <div class="oh-card p-5">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Tech presence</div>
                    <div class="mt-3 space-y-3">
                        @forelse ($appointment->assignments as $assignment)
                            @php
                                $user = $assignment->user;
                                $userName =
                                    $user?->name ??
                                    trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')) ?:
                                    $user?->username;
                                $status = $assignment->presence_status ?? 'assigned';
                            @endphp
                            <div class="rounded-lg border border-border-default px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm text-text-base">{{ $userName ?: 'Tech' }}</span>
                                    <span
                                        class="oh-pill oh-pill--muted text-[11px]">{{ str_replace('_', ' ', $status) }}</span>
                                </div>
                                <form method="POST"
                                    action="{{ route('tenant.trades.schedule.assignments.update', ['tenant' => $tenantKey, 'appointment' => $appointment->id, 'assignment' => $assignment->id]) }}"
                                    class="mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center gap-2">
                                        <select name="presence_status" class="oh-select h-9 text-xs">
                                            @foreach (['assigned' => 'Assigned', 'on_site' => 'On site', 'done' => 'Done'] as $value => $label)
                                                <option value="{{ $value }}" @selected($status === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="oh-btn text-xs" type="submit">Update</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-text-subtle">No techs assigned yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
