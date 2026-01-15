@extends('layouts.trades')

@section('title', 'Trade Jobs')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        $isFieldTech = $isFieldTech ?? (auth()->user()?->isTech() ?? false);
        $tz = $tenant->timezone ?? config('app.timezone');
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">
                    {{ $isFieldTech ? 'My Jobs' : 'Trade Jobs' }}
                </h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $isFieldTech ? 'Your assigned work and upcoming appointments.' : 'Track service and project jobs for trade work.' }}
                </p>
            </div>
            @unless ($isFieldTech)
                <a href="{{ route('tenant.trades.jobs.create', ['tenant' => $tenantKey]) }}" class="oh-btn oh-btn--primary">
                    New job
                </a>
            @endunless
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error_message') }}
            </div>
        @endif
        <form method="GET" action="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}"
            class="oh-card p-4 flex flex-col gap-3 md:flex-row md:items-end md:gap-4">
            <div class="flex-1">
                <label class="text-xs text-text-subtle">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Job summary or client name…"
                    class="oh-input mt-1 w-full">
            </div>
            @unless ($isFieldTech)
                <div class="flex-1">
                    <label class="text-xs text-text-subtle">Status</label>
                    <select name="status" class="oh-input mt-1 w-full">
                        <option value="">All</option>
                        <option value="open" @selected(request('status') === 'open')>Open</option>
                        <option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="text-xs text-text-subtle">Scheduling</label>
                    <select name="scheduling" class="oh-input mt-1 w-full">
                        <option value="">All</option>
                        <option value="unscheduled" @selected(request('scheduling') === 'unscheduled')>Unscheduled</option>
                        <option value="scheduled" @selected(request('scheduling') === 'scheduled')>Scheduled</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="text-xs text-text-subtle">Type</label>
                    <select name="type" class="oh-input mt-1 w-full">
                        <option value="">All</option>
                        <option value="service" @selected(request('type') === 'service')>Service</option>
                        <option value="project" @selected(request('type') === 'project')>Project</option>
                    </select>
                </div>
            @endunless

            <div class="flex gap-2">
                <button class="oh-btn oh-btn--primary" type="submit">Apply</button>
                <a class="oh-btn" href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}">Reset</a>
            </div>
        </form>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @forelse ($jobs as $job)
                @php
                    $nextAppt = $isFieldTech ? $job->appointments->first() : $job->nextAppointment;
                    $isScheduled = (bool) $nextAppt;

                    $startLabel = $isScheduled
                        ? $nextAppt->start_at?->timezone($tz)->format('M j, g:i A')
                        : null;
                    $endLabel = $isScheduled && $nextAppt?->end_at
                        ? $nextAppt->end_at->timezone($tz)->format('g:i A')
                        : null;

                    $clientName =
                        trim(($job->client->firstName ?? '') . ' ' . ($job->client->lastName ?? '')) ?: 'Client';
                    $locationLabel = $job->serviceLocation?->label ?: 'Location';

                    $assignments = $isScheduled ? $nextAppt->assignments ?? collect() : collect();
                    $techCount = $isScheduled ? (int) ($nextAppt->assignments_count ?? $assignments->count()) : 0;
                    $techName = $techCount === 1 ? $assignments->first()?->user?->name ?? 'Tech' : null;

                    $status = ucfirst((string) ($job->status ?? 'Open'));
                    $type = ucfirst((string) ($job->type ?? 'Service'));
                    $isToday = $isScheduled && $nextAppt?->start_at?->timezone($tz)->isToday();
                    $timerRunning = (int) ($job->active_timer_count ?? 0) > 0;
                    $primaryLabel = $isFieldTech && $isToday ? 'Open job' : 'View';
                    $primaryHref = $isFieldTech && $isToday
                        ? route('tenant.trades.field.show', ['tenant' => $tenantKey, 'appointment' => $nextAppt->id])
                        : route('tenant.trades.jobs.show', ['tenant' => $tenantKey, 'job' => $job->id]);
                    $statusPill = $timerRunning ? 'In progress' : ($isToday ? 'Today' : ($isScheduled ? 'Scheduled' : $status));
                @endphp

                <div class="oh-card p-5 border border-border-default shadow-card">
                    {{-- Top row --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-text-base truncate">{{ $job->summary }}</div>
                            <div class="text-xs text-text-subtle mt-1 truncate">
                                {{ $clientName }} · {{ $type }} · {{ $locationLabel }}
                            </div>

                            <div class="flex flex-wrap gap-2 mt-3">
                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusPill }}</span>
                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $type }}</span>

                                @if (!$isFieldTech)
                                    @if ($isScheduled)
                                        <span class="oh-pill oh-pill--success text-[11px]">Scheduled</span>
                                    @else
                                        <span class="oh-pill oh-pill--danger text-[11px]">Unscheduled</span>
                                    @endif
                                @elseif ($timerRunning)
                                    <span class="oh-pill oh-pill--warning text-[11px]">Timer running</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a class="oh-btn text-xs" href="{{ $primaryHref }}">
                                {{ $primaryLabel }}
                            </a>
                            @unless ($isFieldTech)
                                <a class="oh-btn text-xs"
                                    href="{{ route('tenant.trades.jobs.edit', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                                    Edit
                                </a>
                            @endunless
                        </div>
                    </div>

                    {{-- Schedule row --}}
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        @if ($isScheduled)
                            <a class="text-xs text-text-subtle hover:text-text-base inline-flex items-center gap-2"
                                href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $nextAppt->id]) }}">
                                <span class="oh-pill oh-pill--muted text-[11px]">
                                    Next: {{ $startLabel }}@if ($endLabel)
                                        – {{ $endLabel }}
                                    @endif
                                </span>
                            </a>

                            <div class="text-xs text-text-subtle">
                                @if ($techName)
                                    Assigned: <span class="text-text-base font-medium">{{ $techName }}</span>
                                @else
                                    <span class="oh-pill oh-pill--muted text-[11px]">{{ $techCount }} tech(s)</span>
                                @endif
                            </div>
                        @else
                            <div class="text-xs text-text-subtle">
                                No upcoming appointment scheduled.
                            </div>

                            @unless ($isFieldTech)
                                <a class="oh-btn oh-btn--primary text-xs"
                                    href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                                    Schedule
                                </a>
                            @endunless
                        @endif
                    </div>
                </div>
                @empty
                    <div
                        class="lg:col-span-2 rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No trade jobs yet. Create your first job.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $jobs->links() }}
            </div>
        </div>
    @endsection
