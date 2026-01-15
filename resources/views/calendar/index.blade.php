@extends('layouts.app')

@section('title', 'Calendar')

@section('content')
    @php
        $routeTenant = request()->route('tenant');
        $calendarTenantId =
            $routeTenant instanceof \App\Models\Tenant
                ? $routeTenant->getKey()
                : tenant('id') ?? auth()->user()?->tenant_id;
    @endphp
    <section class="space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Calendar</p>
            <h1 class="text-2xl font-semibold text-text-base">Schedule & Deadlines</h1>
            <p class="text-sm text-text-subtle">Tasks, follow-ups, invoices, and renewals in one view.</p>
        </div>

        {{-- Filters --}}
        <div class="oh-card border border-border-default/60 p-4 md:p-5 space-y-3">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @php $types = ['task' => 'Tasks', 'opportunity' => 'Follow-ups', 'invoice' => 'Invoices', 'service' => 'Services']; @endphp
                @foreach ($types as $key => $label)
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="oh-input h-4 w-4 fc-type" value="{{ $key }}" checked>
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

        <div class="oh-card border border-border-default shadow-sm p-3">
            <div id="calendar"></div>
        </div>
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
                const typeInputs = document.querySelectorAll('.fc-type');
                const memberSelect = document.getElementById('fc-member');
                const projectSelect = document.getElementById('fc-project');

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

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek',
                    },

                    // If your backend returns ISO timestamps with TZ offsets, leave this alone.
                    // If you return date-only all-day events, this helps FullCalendar behave predictably:
                    timeZone: 'local',

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
                        filters.types.forEach(t => params.append('types[]', t));

                        fetch(`{{ route('tenant.calendar.events', ['tenant' => $calendarTenantId ?? (tenant('id') ?? auth()->user()?->tenant_id)]) }}?` +
                                params.toString(), {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                })
                            .then(r => r.ok ? r.json() : Promise.reject(r))
                            .then(data => success(data))
                            .catch(err => failure(err));
                    },

                    eventClick(info) {
                        const url = info.event.extendedProps?.url || info.event.url;
                        if (url) window.location.href = url;
                    },
                });

                // refetch when filters change
                [memberSelect, projectSelect, ...typeInputs].forEach(el => {
                    el?.addEventListener('change', () => calendar.refetchEvents());
                });

                calendar.render();
            });
        </script>
    @else
        <script>
            console.warn('No tenant context available for calendar events.');
        </script>
    @endif
@endpush
