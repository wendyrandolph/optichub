@extends('layouts.app')

@section('title', 'Meeting Details')

@section('content')
    @php
        $tenantKey = $tenantId;
        $tz = tenant('timezone') ?? config('app.timezone');
        $startAt = optional($meeting->start_at)->timezone($tz);
        $endAt = optional($meeting->end_at)->timezone($tz);
        $memberName = $meeting->member
            ? trim(($meeting->member->first_name ?? '') . ' ' . ($meeting->member->last_name ?? ''))
            : null;
        $memberName = $memberName !== '' ? $memberName : ($meeting->member->username ?? $meeting->member->email ?? null);
        $clientName = $meeting->contact
            ? trim(($meeting->contact->firstName ?? '') . ' ' . ($meeting->contact->lastName ?? ''))
            : null;
    @endphp

    <div class="oh-page">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.08em] text-text-subtle">Calendar</div>
                <h1 class="text-2xl font-semibold tracking-tight text-text-base">{{ $meeting->title }}</h1>
                <p class="text-sm text-text-subtle">Meeting details and linked context.</p>
            </div>
            <a href="{{ route('tenant.calendar.index', ['tenant' => $tenantKey]) }}" class="oh-btn">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                Back to Calendar
            </a>
        </div>

        <section class="oh-card p-4 sm:p-6 lg:max-w-4xl lg:mx-auto">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Date</div>
                    <div class="text-sm font-semibold text-text-base">
                        @if ($meeting->all_day)
                            {{ $startAt?->format('M j, Y') ?? 'TBD' }} (All day)
                        @else
                            {{ $startAt?->format('M j, Y') ?? 'TBD' }}
                        @endif
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Time</div>
                    <div class="text-sm font-semibold text-text-base">
                        @if ($meeting->all_day)
                            All day
                        @else
                            {{ $startAt?->format('g:i A') ?? 'TBD' }} —
                            {{ $endAt?->format('g:i A') ?? 'TBD' }} ({{ $tz }})
                        @endif
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Team member</div>
                    <div class="text-sm font-semibold text-text-base">{{ $memberName ?: 'Unassigned' }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Client</div>
                    <div class="text-sm font-semibold text-text-base">{{ $clientName ?: 'No client' }}</div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Project</div>
                    <div class="text-sm font-semibold text-text-base">
                        {{ $meeting->project->project_name ?? 'No project' }}
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="text-xs text-text-subtle">Location</div>
                    <div class="text-sm font-semibold text-text-base">{{ $meeting->location ?: 'Not set' }}</div>
                </div>
            </div>

            @if ($meeting->description)
                <div class="mt-6 border-t border-border-default/60 pt-4">
                    <div class="text-xs text-text-subtle uppercase tracking-wide">Notes</div>
                    <p class="mt-2 text-sm text-text-base">{{ $meeting->description }}</p>
                </div>
            @endif
        </section>
    </div>
@endsection
