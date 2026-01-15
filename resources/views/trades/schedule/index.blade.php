@extends('layouts.trades')

@section('title', 'Trade Schedule')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $currentView = request()->query('view', 'calendar');
        $techFilter = (int) request()->query('tech', 0);
        $unscheduledCount = $unscheduledJobs?->count() ?? 0;
        $tz = $tenant->timezone ?? config('app.timezone');
    @endphp
    @push('head')
        <style>
            .trades-schedule-page {
                overflow-x: hidden;
            }

            html,
            body {
                overflow-x: hidden;
            }

            #trade-calendar,
            #trade-calendar .fc-view-harness,
            #trade-calendar .fc-scroller {
                overflow-x: hidden !important;
            }

            #trade-calendar .fc-scrollgrid,
            #trade-calendar .fc-scrollgrid table {
                width: 100% !important;
                table-layout: fixed;
            }

            #trade-calendar .fc {
                --fc-border-color: rgb(var(--ui-border));
                --fc-page-bg-color: rgb(var(--ui-bg));
                --fc-neutral-bg-color: rgb(var(--ui-surface));
                --fc-event-text-color: #fff;
                --fc-today-bg-color: rgba(var(--ui-primary), 0.08);
            }

            .fc-event-assigned {
                background-color: rgb(var(--tenant-primary)) !important;
                border-color: rgb(var(--tenant-primary)) !important;
            }

            .fc-event-unassigned {
                background-color: #f59e0b !important;
                border-color: #f59e0b !important;
            }

            .fc-event-pto {
                background-color: #0f766e !important;
                border-color: #0f766e !important;
            }
        </style>
        @if (in_array($currentView, ['calendar', 'pto'], true))
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
        @endif
    @endpush
    <div class="trades-schedule-page max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Schedule</h1>
                <p class="text-sm text-text-subtle mt-1">Plan appointment blocks and multi-tech assignments.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="text-xs text-text-subtle hover:text-text-base mr-2"
                    href="{{ route('tenant.trades.reports.schedule', ['tenant' => $tenantKey]) }}">
                    View insights →
                </a>
                <button type="button" data-unscheduled-open class="oh-btn">
                    <span class="inline-flex items-center gap-2">
                        <span>Unscheduled jobs</span>
                        @if ($unscheduledCount > 0)
                            <span
                                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 rounded-full bg-[rgb(var(--ui-danger))] text-white text-[11px] px-1">
                                {{ $unscheduledCount }}
                            </span>
                        @endif
                    </span>
                </button>
                <a href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey, 'view' => 'calendar']) }}"
                    class="oh-btn {{ $currentView === 'calendar' ? 'oh-btn--primary' : '' }}">
                    Calendar
                </a>
                <a href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey, 'view' => 'pto']) }}"
                    class="oh-btn {{ $currentView === 'pto' ? 'oh-btn--primary' : '' }}">
                    PTO
                </a>
                <a href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey, 'view' => 'list']) }}"
                    class="oh-btn {{ $currentView === 'list' ? 'oh-btn--primary' : '' }}">
                    List
                </a>
                <a href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey]) }}"
                    class="oh-btn oh-btn--primary">
                    New appointment
                </a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        @if (in_array($currentView, ['calendar', 'pto'], true))
            <div class="oh-card p-4">
                @if ($currentView === 'pto')
                    <div class="flex flex-wrap items-center gap-3 pb-3">
                        <label class="text-xs text-text-subtle" for="pto-tech-filter">Filter by tech</label>
                        <select id="pto-tech-filter" class="oh-select h-9 text-sm">
                            <option value="">All techs</option>
                            @foreach ($techs ?? [] as $tech)
                                @php
                                    $label = trim(($tech->first_name ?? '') . ' ' . ($tech->last_name ?? ''));
                                    if (!$label) {
                                        $label = $tech->username ?? ($tech->email ?? 'Tech #' . $tech->id);
                                    }
                                @endphp
                                <option value="{{ $tech->id }}" @selected($techFilter === (int) $tech->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div id="trade-calendar"></div>
            </div>
        @else
            @php
                $filters = $filters ?? [];
                $statusOptions = $statusOptions ?? [];
                $search = $filters['q'] ?? '';
                $from = $filters['from'] ?? '';
                $to = $filters['to'] ?? '';
                $status = $filters['status'] ?? '';
                $tech = $filters['tech'] ?? '';
                $issues = $filters['issues'] ?? '';
                $sort = $filters['sort'] ?? 'soonest';
                $hasFilters =
                    $search !== '' ||
                    $from !== '' ||
                    $to !== '' ||
                    $status !== '' ||
                    $tech !== '' ||
                    $issues !== '' ||
                    ($sort !== 'soonest');
                $groupedAppointments = $appointments
                    ? $appointments->getCollection()->groupBy(fn($appointment) => $appointment->start_at?->timezone($tz)->toDateString() ?? 'unscheduled')
                    : collect();
                $issueToneClasses = [
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                    'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
                    'info' => 'border-slate-200 bg-slate-50 text-slate-600',
                ];
            @endphp

            <form method="GET" class="oh-card border border-border-default/60 rounded-2xl p-4 space-y-4">
                <input type="hidden" name="view" value="list">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4 space-y-1.5">
                        <label class="text-xs text-text-subtle">Search</label>
                        <input type="text" name="q" value="{{ $search }}" class="oh-input h-10 w-full"
                            placeholder="Job, client, address, tech...">
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                        <label class="text-xs text-text-subtle">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="oh-input h-10 w-full">
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                        <label class="text-xs text-text-subtle">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="oh-input h-10 w-full">
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                        <label class="text-xs text-text-subtle">Status</label>
                        <select name="status" class="oh-select h-10 w-full">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                        <label class="text-xs text-text-subtle">Tech</label>
                        <select name="tech" class="oh-select h-10 w-full">
                            <option value="">All techs</option>
                            @foreach ($techs ?? [] as $techUser)
                                @php
                                    $label = trim(($techUser->first_name ?? '') . ' ' . ($techUser->last_name ?? ''));
                                    if (!$label) {
                                        $label = $techUser->username ?? ($techUser->email ?? 'Tech #' . $techUser->id);
                                    }
                                @endphp
                                <option value="{{ $techUser->id }}" @selected((string) $tech === (string) $techUser->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 space-y-1.5">
                        <label class="text-xs text-text-subtle">Sort</label>
                        <select name="sort" class="oh-select h-10 w-full">
                            <option value="soonest" @selected($sort === 'soonest')>Soonest</option>
                            <option value="status" @selected($sort === 'status')>Status priority</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                            <input type="checkbox" name="issues" value="1" class="rounded border-border-default"
                                @checked(!empty($issues))>
                            Has issues
                        </label>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs text-text-subtle">Times shown in {{ $tz }}.</div>
                    <div class="flex items-center gap-2">
                        <button class="oh-btn" type="submit">Apply filters</button>
                        @if ($hasFilters)
                            <a class="oh-btn" href="{{ route('tenant.trades.schedule.index', ['tenant' => $tenantKey, 'view' => 'list']) }}">Reset</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="space-y-6">
                @forelse ($groupedAppointments as $day => $dayAppointments)
                    @php
                        $dayLabel = $day !== 'unscheduled'
                            ? \Illuminate\Support\Carbon::parse($day)->format('l, M j, Y')
                            : 'Unscheduled';
                    @endphp
                    <div class="rounded-2xl border border-border-default/60 bg-[rgb(var(--ui-surface))] overflow-hidden">
                        <div class="sticky top-0 z-10 bg-[rgb(var(--ui-bg))] border-b border-border-default/60 px-4 py-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $dayLabel }}</div>
                                <div class="text-xs text-text-subtle">{{ $dayAppointments->count() }} appointments</div>
                            </div>
                        </div>

                        <div class="hidden lg:block">
                            <table class="min-w-full text-sm">
                                <thead class="bg-surface-muted text-text-subtle">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Time</th>
                                        <th class="px-4 py-3 text-left font-semibold">Job</th>
                                        <th class="px-4 py-3 text-left font-semibold">Client</th>
                                        <th class="px-4 py-3 text-left font-semibold">Service location</th>
                                        <th class="px-4 py-3 text-left font-semibold">Techs</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold">Issues</th>
                                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-default">
                                    @foreach ($dayAppointments as $appointment)
                                        @php
                                            $jobSummary = $appointment->job?->summary ?? 'Job';
                                            $clientName =
                                                trim(
                                                    ($appointment->job?->client?->firstName ?? '') .
                                                        ' ' .
                                                        ($appointment->job?->client?->lastName ?? ''),
                                                ) ?:
                                                'Client';
                                            $startAt = $appointment->start_at?->timezone($tz);
                                            $endAt = $appointment->end_at?->timezone($tz);
                                            $timeLabel = $startAt
                                                ? $startAt->format('g:i A') . ' - ' . ($endAt?->format('g:i A') ?? '')
                                                : 'TBD';
                                            $statusLabel = ucfirst(str_replace('_', ' ', $appointment->status ?? 'scheduled'));
                                            $issuesList = $appointment->issues ?? [];
                                            $addressText = $appointment->address_text ?: 'No address';
                                            $techLabel = $appointment->tech_label ?? 'Unassigned';
                                            $techCount = $appointment->tech_count ?? 0;
                                            $issueRowClass = $appointment->issue_row_class ?? '';
                                        @endphp
                                        <tr class="{{ $issueRowClass }}">
                                            <td class="px-4 py-3 text-xs text-text-subtle whitespace-nowrap">{{ $timeLabel }}</td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-semibold text-text-base">{{ $jobSummary }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-text-subtle">{{ $clientName }}</td>
                                            <td class="px-4 py-3 text-xs text-text-subtle">{{ $addressText }}</td>
                                            <td class="px-4 py-3 text-xs text-text-subtle">
                                                <div class="text-text-base font-medium">{{ $techCount }} tech{{ $techCount === 1 ? '' : 's' }}</div>
                                                <div class="text-[11px] text-text-subtle mt-1">{{ $techLabel }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    @forelse ($issuesList as $issue)
                                                        @php
                                                            $tone = $issue['tone'] ?? 'info';
                                                            $badgeClass = $issueToneClasses[$tone] ?? $issueToneClasses['info'];
                                                        @endphp
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] {{ $badgeClass }}"
                                                            @if (!empty($issue['detail'])) title="Conflict: {{ $issue['detail'] }}" @endif>
                                                            {{ $issue['label'] }}
                                                        </span>
                                                    @empty
                                                        <span class="text-xs text-text-subtle">-</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <a class="oh-btn text-xs"
                                                        href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                                        View
                                                    </a>
                                                    <a class="oh-btn text-xs"
                                                        href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                                        Edit
                                                    </a>
                                                    <a class="oh-btn text-xs"
                                                        href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}#appointment-assignments">
                                                        Reassign
                                                    </a>
                                                    <a class="oh-btn text-xs"
                                                        href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}#appointment-time">
                                                        Reschedule
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="lg:hidden divide-y divide-border-default">
                            @foreach ($dayAppointments as $appointment)
                                @php
                                    $jobSummary = $appointment->job?->summary ?? 'Job';
                                    $clientName =
                                        trim(
                                            ($appointment->job?->client?->firstName ?? '') .
                                                ' ' .
                                                ($appointment->job?->client?->lastName ?? ''),
                                        ) ?:
                                        'Client';
                                    $startAt = $appointment->start_at?->timezone($tz);
                                    $endAt = $appointment->end_at?->timezone($tz);
                                    $timeLabel = $startAt
                                        ? $startAt->format('g:i A') . ' - ' . ($endAt?->format('g:i A') ?? '')
                                        : 'TBD';
                                    $statusLabel = ucfirst(str_replace('_', ' ', $appointment->status ?? 'scheduled'));
                                    $issuesList = $appointment->issues ?? [];
                                    $addressText = $appointment->address_text ?: 'No address';
                                    $techLabel = $appointment->tech_label ?? 'Unassigned';
                                    $techCount = $appointment->tech_count ?? 0;
                                    $issueRowClass = $appointment->issue_row_class ?? '';
                                @endphp
                                <div class="p-4 {{ $issueRowClass }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-text-base">{{ $jobSummary }}</div>
                                            <div class="text-xs text-text-subtle mt-1">{{ $clientName }}</div>
                                            <div class="text-xs text-text-subtle mt-1">{{ $timeLabel }}</div>
                                        </div>
                                        <span class="oh-pill oh-pill--muted text-[11px]">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="text-xs text-text-subtle mt-2">{{ $addressText }}</div>
                                    <div class="text-xs text-text-subtle mt-2">
                                        {{ $techCount }} tech{{ $techCount === 1 ? '' : 's' }} - {{ $techLabel }}
                                    </div>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @forelse ($issuesList as $issue)
                                            @php
                                                $tone = $issue['tone'] ?? 'info';
                                                $badgeClass = $issueToneClasses[$tone] ?? $issueToneClasses['info'];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] {{ $badgeClass }}"
                                                @if (!empty($issue['detail'])) title="Conflict: {{ $issue['detail'] }}" @endif>
                                                {{ $issue['label'] }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-text-subtle">No issues</span>
                                        @endforelse
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <a class="oh-btn text-xs"
                                            href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                            View
                                        </a>
                                        <a class="oh-btn text-xs"
                                            href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                            Edit
                                        </a>
                                        <a class="oh-btn text-xs"
                                            href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}#appointment-assignments">
                                            Reassign
                                        </a>
                                        <a class="oh-btn text-xs"
                                            href="{{ route('tenant.trades.schedule.edit', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}#appointment-time">
                                            Reschedule
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="oh-card border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No appointments match these filters.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $appointments->links() }}
            </div>
        @endif
    </div>

    <div class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity" data-unscheduled-backdrop>
    </div>
    <aside
        class="fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-[rgb(var(--ui-surface))] border-l border-border-default shadow-xl translate-x-full transition-transform duration-200 ease-out"
        style="transform: translateX(100%);"
        data-unscheduled-count="{{ $unscheduledCount }}" data-unscheduled-drawer>
        <div class="h-full flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border-default">
                <div class="text-sm font-semibold text-text-base">Unscheduled jobs</div>
                <button type="button" data-unscheduled-close class="oh-btn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="flex-1  px-5 py-4 space-y-3">
                <div class="text-xs text-text-subtle">
                    Pick a job and schedule it right away.
                </div>
                @forelse ($unscheduledJobs ?? [] as $job)
                    @php
                        $clientName =
                            trim(($job->client?->firstName ?? '') . ' ' . ($job->client?->lastName ?? '')) ?: 'Client';
                        $location = $job->serviceLocation;
                        $locationLabel = $location?->label ?? null;
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
                    @endphp
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 p-3">
                        <div class="text-sm font-semibold text-text-base truncate">{{ $job->summary }}</div>
                        <div class="text-xs text-text-subtle mt-1 truncate">{{ $clientName }}</div>
                        @if ($locationLabel)
                            <div class="text-[11px] text-text-subtle mt-1 truncate">{{ $locationLabel }}</div>
                        @endif
                        @if ($addressText)
                            <div class="text-[11px] text-text-subtle mt-1 truncate">
                                {{ $addressText }}
                                @if ($mapUrl)
                                    <a href="{{ $mapUrl }}" class="ml-1 text-text-base hover:underline"
                                        target="_blank" rel="noopener">Map</a>
                                @endif
                            </div>
                        @else
                            <div class="text-[11px] text-amber-700 mt-1">No service address on this job.</div>
                        @endif
                        <a href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey, 'job' => $job->id]) }}"
                            class="oh-btn text-xs mt-3">
                            Schedule
                        </a>
                    </div>
                @empty
                    <div
                        class="rounded-lg border border-border-default bg-surface-muted/60 px-3 py-2 text-xs text-text-subtle">
                        All jobs are scheduled.
                    </div>
                @endforelse
            </div>
        </div>
    </aside>
    @push('scripts')
        @if (in_array($currentView, ['calendar', 'pto'], true))
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const calendarEl = document.getElementById('trade-calendar');
                    if (!calendarEl || !window.FullCalendar) return;

                    const viewMode = "{{ $currentView }}";
                    const techFilter = "{{ $techFilter ?: '' }}";
                    const baseUrl = viewMode === 'pto' ?
                        "{{ route('tenant.trades.schedule.pto-events', ['tenant' => $tenantKey]) }}" :
                        "{{ route('tenant.trades.schedule.events', ['tenant' => $tenantKey]) }}";
                    const eventsUrl = techFilter ? `${baseUrl}?tech=${techFilter}` : baseUrl;

                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        timeZone: "{{ $tz }}",
                        initialView: 'timeGridWeek',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'timeGridWeek,timeGridDay'
                        },
                        events: {
                            url: eventsUrl,
                        },
                        eventClassNames: (arg) => {
                            if (viewMode === 'pto') {
                                return ['fc-event-pto'];
                            }
                            return arg.event.extendedProps.unassigned ? ['fc-event-unassigned'] : [
                                'fc-event-assigned'
                            ];
                        },
                        eventContent: (arg) => {
                            const techCount = arg.event.extendedProps.techCount || 0;
                            const techLabel = viewMode === 'pto' ?
                                (arg.event.extendedProps.type || 'PTO') :
                                (techCount ? `${techCount} tech${techCount === 1 ? '' : 's'}` : 'Unassigned');
                            const title = document.createElement('div');
                            title.className = 'text-xs font-semibold';
                            title.textContent = arg.event.title;
                            const meta = document.createElement('div');
                            meta.className = 'text-[10px] opacity-90';
                            meta.textContent = techLabel;
                            return {
                                domNodes: [title, meta]
                            };
                        }
                    });

                    calendar.render();

                    if (viewMode === 'pto') {
                        const filter = document.getElementById('pto-tech-filter');
                        if (filter) {
                            filter.addEventListener('change', (event) => {
                                const nextTech = event.target.value;
                                const url = new URL(window.location.href);
                                url.searchParams.set('view', 'pto');
                                if (nextTech) {
                                    url.searchParams.set('tech', nextTech);
                                } else {
                                    url.searchParams.delete('tech');
                                }
                                window.location.href = url.toString();
                            });
                        }
                    }
                });
            </script>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const drawer = document.querySelector('[data-unscheduled-drawer]');
                const backdrop = document.querySelector('[data-unscheduled-backdrop]');
                const openBtn = document.querySelector('[data-unscheduled-open]');
                const closeBtn = document.querySelector('[data-unscheduled-close]');

                if (!drawer || !backdrop || !openBtn) return;

                const openDrawer = () => {
                    drawer.style.transform = 'translateX(0)';
                    drawer.classList.remove('translate-x-full');
                    backdrop.classList.remove('opacity-0', 'pointer-events-none');
                };
                const closeDrawer = () => {
                    drawer.style.transform = 'translateX(100%)';
                    drawer.classList.add('translate-x-full');
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                };

                openBtn.addEventListener('click', openDrawer);
                if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
                backdrop.addEventListener('click', closeDrawer);
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') closeDrawer();
                });

            });
        </script>
    @endpush
@endsection
