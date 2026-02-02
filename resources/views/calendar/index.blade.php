@extends('layouts.app')

@section('title', 'Calendar')

@section('content')
    @php
        $routeTenant = request()->route('tenant');
        $calendarTenantId =
            $routeTenant instanceof \App\Models\Tenant
                ? $routeTenant->getKey()
                : tenant('id') ?? auth()->user()?->tenant_id;
        $currentView = request()->query('view', 'calendar');
        $tz = tenant('timezone') ?? config('app.timezone') ?? 'America/Denver';
    @endphp
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Calendar</p>
                <h1 class="text-2xl font-semibold text-text-base">Schedule & Deadlines</h1>
                <p class="text-sm text-text-subtle">Tasks, follow-ups, invoices, and renewals in one view.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tenant.calendar.index', ['tenant' => $calendarTenantId, 'view' => 'calendar']) }}"
                    class="oh-btn {{ $currentView === 'calendar' ? 'oh-btn--primary' : '' }}">
                    Calendar
                </a>
                <a href="{{ route('tenant.calendar.index', ['tenant' => $calendarTenantId, 'view' => 'schedule']) }}"
                    class="oh-btn {{ $currentView === 'schedule' ? 'oh-btn--primary' : '' }}">
                    Schedule
                </a>
                <a href="{{ route('tenant.meetings.create', ['tenant' => $calendarTenantId]) }}"
                    class="oh-btn oh-btn--primary">
                    New Meeting
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="oh-card border border-border-default/60 p-4 md:p-5 space-y-3">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @php $types = ['task' => 'Tasks', 'meeting' => 'Meetings', 'opportunity' => 'Follow-ups', 'invoice' => 'Invoices', 'service' => 'Services']; @endphp
                @foreach ($types as $key => $label)
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox"
                            class="h-4 w-4 rounded border-border-default text-[rgb(var(--brand-primary))] fc-type"
                            value="{{ $key }}" checked>
                        <span class="text-text-base">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Member</span>
                    <select id="fc-member" class="oh-select h-10">
                        <option value="">All</option>
                        @foreach ($members ?? [] as $m)
                            @php $label = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) ?: ($m->username ?? 'User'); @endphp
                            <option value="{{ $m->id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Project</span>
                    <select id="fc-project" class="oh-select h-10">
                        <option value="">All</option>
                        @foreach ($projects ?? [] as $p)
                            <option value="{{ $p->id }}">{{ $p->project_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        @if ($currentView === 'calendar')
            <div class="oh-card border border-border-default shadow-sm p-3">
                <div id="calendar"></div>
            </div>
        @else
            <div class="oh-card border border-border-default/70 p-4 md:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-border-default/60">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Schedule</h2>
                        <p class="text-sm text-text-subtle">Upcoming activity in a list view.</p>
                    </div>
                    <div class="text-xs text-text-subtle">Times shown in {{ $tz }}.</div>
                </div>
                <div id="scheduleList" class="mt-4 space-y-3"></div>
                <div id="scheduleEmpty"
                    class="mt-4 rounded-xl border border-border-default/60 bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle hidden">
                    No matching activity for the selected filters.
                </div>
            </div>
        @endif
    </section>

    <div id="calendarModal" class="calendar-modal hidden">
        <div class="modal-content">
            <button type="button" class="close"
                onclick="document.getElementById('calendarModal').classList.add('hidden')">
                &times;
            </button>
            <div id="calendar-modal-body"></div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    @if ($calendarTenantId)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const calendarEl = document.getElementById('calendar');
                const scheduleList = document.getElementById('scheduleList');
                const scheduleEmpty = document.getElementById('scheduleEmpty');
                const typeInputs = document.querySelectorAll('.fc-type');
                const memberSelect = document.getElementById('fc-member');
                const projectSelect = document.getElementById('fc-project');
                const currentView = @json($currentView);
                const tenantTz = @json($tz ?: 'America/Denver');

                // --- URL persistence helpers ---
                const qs = new URLSearchParams(window.location.search);

                function loadFromQuery() {
                    // member/project
                    if (memberSelect && qs.get('member_id')) memberSelect.value = qs.get('member_id');
                    if (projectSelect && qs.get('project_id')) projectSelect.value = qs.get('project_id');

                    // types[] (if present)
                    const typesFromUrl = qs.getAll('types[]');
                    if (typesFromUrl.length) {
                        typeInputs.forEach(i => i.checked = typesFromUrl.includes(i.value));
                    }
                }

                function writeToQuery(filters) {
                    const next = new URLSearchParams(window.location.search);

                    // clear existing
                    next.delete('member_id');
                    next.delete('project_id');
                    next.delete('types[]');

                    if (filters.member_id) next.set('member_id', filters.member_id);
                    if (filters.project_id) next.set('project_id', filters.project_id);
                    filters.types.forEach(t => next.append('types[]', t));

                    const newUrl = `${window.location.pathname}?${next.toString()}`;
                    window.history.replaceState({}, '', newUrl);
                }

                function currentFilters() {
                    const types = Array.from(typeInputs).filter(i => i.checked).map(i => i.value);
                    return {
                        types,
                        member_id: memberSelect?.value || '',
                        project_id: projectSelect?.value || '',
                    };
                }

                loadFromQuery();

                const fetchEvents = (params) =>
                    fetch(`{{ route('tenant.calendar.events', ['tenant' => $calendarTenantId ?? (tenant('id') ?? auth()->user()?->tenant_id)]) }}?` +
                            params.toString(), {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            })
                        .then(r => (r.ok ? r.json() : Promise.reject(r)));

                const renderSchedule = (events) => {
                    if (!scheduleList || !scheduleEmpty) return;
                    scheduleList.innerHTML = '';

                    if (!Array.isArray(events) || events.length === 0) {
                        scheduleEmpty.classList.remove('hidden');
                        return;
                    }

                    scheduleEmpty.classList.add('hidden');

                    const grouped = events
                        .map((event) => ({
                            ...event,
                            startDate: new Date(event.start),
                        }))
                        .sort((a, b) => a.startDate - b.startDate)
                        .reduce((acc, event) => {
                            const day = new Intl.DateTimeFormat(undefined, {
                                weekday: 'long',
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                                timeZone: tenantTz,
                            }).format(event.startDate);
                            acc[day] = acc[day] || [];
                            acc[day].push(event);
                            return acc;
                        }, {});

                    Object.entries(grouped).forEach(([dayLabel, dayEvents]) => {
                        const section = document.createElement('section');
                        section.className = 'rounded-xl border border-border-default/60 bg-surface-card';

                        const header = document.createElement('div');
                        header.className =
                            'px-4 py-2 border-b border-border-default/60 text-xs uppercase tracking-wide text-text-subtle';
                        header.textContent = dayLabel;
                        section.appendChild(header);

                        const list = document.createElement('div');
                        list.className = 'divide-y divide-border-default/60';

                        dayEvents.forEach((event) => {
                            const row = document.createElement('div');
                            row.className = 'px-4 py-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between';

                            const left = document.createElement('div');
                            left.className = 'space-y-1';

                            const title = document.createElement('div');
                            title.className = 'text-sm font-semibold text-text-base';
                            title.textContent = event.title || 'Untitled';

                            const meta = document.createElement('div');
                            meta.className = 'text-xs text-text-subtle';
                            const typeLabel = event.extendedProps?.type || 'event';
                            let timeLabel = 'All day';
                            if (!event.allDay) {
                                const endDate = event.end ? new Date(event.end) : null;
                                const startLabel = new Intl.DateTimeFormat([], {
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    timeZone: tenantTz,
                                }).format(event.startDate);
                                const endLabel = endDate
                                    ? new Intl.DateTimeFormat([], {
                                          hour: 'numeric',
                                          minute: '2-digit',
                                          timeZone: tenantTz,
                                      }).format(endDate)
                                    : null;
                                timeLabel = endLabel ? `${startLabel} – ${endLabel}` : startLabel;
                            }
                            meta.textContent = `${typeLabel} • ${timeLabel}`;

                            left.appendChild(title);
                            left.appendChild(meta);

                            const right = document.createElement('div');
                            right.className = 'text-xs text-text-subtle sm:text-right';
                            right.textContent = event.extendedProps?.assigned || event.extendedProps?.user || '';

                            row.appendChild(left);
                            row.appendChild(right);

                            list.appendChild(row);
                        });

                        section.appendChild(list);
                        scheduleList.appendChild(section);
                    });
                };

                const calendar = calendarEl
                    ? new FullCalendar.Calendar(calendarEl, {
                          initialView: 'dayGridMonth',
                          height: 'auto',
                          headerToolbar: {
                              left: 'prev,next today',
                              center: 'title',
                              right: 'dayGridMonth,timeGridWeek,listWeek',
                          },
                          timeZone: tenantTz,
                          loading(isLoading) {
                              const card = calendarEl.closest('.oh-card');
                              if (!card) return;
                              card.classList.toggle('fc-loading', isLoading);
                          },
                          events(fetchInfo, success, failure) {
                              const filters = currentFilters();
                              writeToQuery(filters);

                              const params = new URLSearchParams({
                                  start: fetchInfo.startStr,
                                  end: fetchInfo.endStr,
                                  member_id: filters.member_id,
                                  project_id: filters.project_id,
                              });
                              filters.types.forEach((t) => params.append('types[]', t));

                              fetchEvents(params)
                                  .then((data) => success(data))
                                  .catch((err) => failure(err));
                          },
                          eventClick(info) {
                              const url = info.event.extendedProps?.url || info.event.url;
                              if (url) window.location.href = url;
                          },
                      })
                    : null;

                // refetch when filters change
                [memberSelect, projectSelect, ...typeInputs].forEach(el => {
                    el?.addEventListener('change', () => {
                        if (calendar) {
                            calendar.refetchEvents();
                        }
                        if (currentView === 'schedule') {
                            const filters = currentFilters();
                            writeToQuery(filters);
                            const params = new URLSearchParams({
                                start: new Date().toISOString(),
                                end: new Date(Date.now() + 1000 * 60 * 60 * 24 * 60).toISOString(),
                                member_id: filters.member_id,
                                project_id: filters.project_id,
                            });
                            filters.types.forEach((t) => params.append('types[]', t));
                            fetchEvents(params).then(renderSchedule).catch(() => renderSchedule([]));
                        }
                    });
                });

                if (calendar && currentView === 'calendar') {
                    calendar.render();
                }

                if (currentView === 'schedule') {
                    const filters = currentFilters();
                    writeToQuery(filters);
                    const params = new URLSearchParams({
                        start: new Date().toISOString(),
                        end: new Date(Date.now() + 1000 * 60 * 60 * 24 * 60).toISOString(),
                        member_id: filters.member_id,
                        project_id: filters.project_id,
                    });
                    filters.types.forEach((t) => params.append('types[]', t));
                    fetchEvents(params).then(renderSchedule).catch(() => renderSchedule([]));
                }
            });
        </script>
    @else
        <script>
            console.warn('No tenant context available for calendar events.');
        </script>
    @endif
@endpush
