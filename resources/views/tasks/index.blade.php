@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
    @php
        // Tiny helpers (blade-local)
        $tenantParam = request()->route('tenant');

        $columns = [
            'open' => 'To Do',
            'in_progress' => 'Working On',
            'completed' => 'Done',
        ];

        // Count quick stats for header chips
        $counts = [
            'open' => collect($tasksByStatus['open'] ?? [])
                ->flatMap(fn($g) => $g['tasks'] ?? [])
                ->count(),
            'in_progress' => collect($tasksByStatus['in_progress'] ?? [])
                ->flatMap(fn($g) => $g['tasks'] ?? [])
                ->count(),
            'completed' => collect($tasksByStatus['completed'] ?? [])
                ->flatMap(fn($g) => $g['tasks'] ?? [])
                ->count(),
        ];

        // Due-date badge mapping
        $dueBadge = function (?string $date) {
            if (!$date) {
                return ['label' => 'No due date', 'class' => 'bg-ink-100 text-ink-700 border-ink-300'];
            }
            $today = now()->startOfDay();
            $d = \Carbon\Carbon::parse($date)->startOfDay();

            if ($d->lt($today)) {
                return [
                    'label' => 'Overdue',
                    'class' => 'bg-status-danger/10 text-status-danger border-status-danger/30',
                ];
            }
            if ($d->isSameDay($today)) {
                return ['label' => 'Today', 'class' => 'bg-amber-100 text-amber-700 border-amber-300'];
            }
            if ($d->lte($today->copy()->addDays(3))) {
                return ['label' => 'Soon', 'class' => 'bg-blue-100 text-blue-700 border-blue-300'];
            }
            return ['label' => 'Scheduled', 'class' => 'bg-green-200 text-green-700 border-green-600/30'];
        };
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Header / Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-text-base">Tasks</h1>
                <p class="text-sm text-text-subtle">Track progress and keep work moving.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.tasks.create', ['tenant' => $tenantParam]) }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white
                bg-gradient-to-b from-brand-primary to-blue-700 shadow-card hover:brightness-110">
                    <i class="fa-solid fa-plus"></i> New Task
                </a>

                <button id="toggleKey"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium
                     border border-border-default bg-surface-card text-text-base hover:brightness-110"
                    type="button" aria-controls="colorKey" aria-expanded="true">
                    Toggle Color Key
                </button>
            </div>
        </div>

        {{-- Quick KPI strip --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 px-4 py-3">
            <ul class="flex flex-wrap items-center gap-3 text-sm">
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-700"></span>
                    <span class="text-text-subtle">To Do</span>
                    <strong class="text-text-base tabular-nums">{{ $counts['open'] }}</strong>
                </li>
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-purple-700"></span>
                    <span class="text-text-subtle">Working On</span>
                    <strong class="text-text-base tabular-nums">{{ $counts['in_progress'] }}</strong>
                </li>
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-green-700"></span>
                    <span class="text-text-subtle">Done</span>
                    <strong class="text-text-base tabular-nums">{{ $counts['completed'] }}</strong>
                </li>
            </ul>
        </div>

        {{-- Toolbar --}}
        <div
            class="rounded-xl p-4 border border-border-default bg-surface-accent flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
                <label class="sr-only" for="projectFilter">Filter by project</label>
                <select id="projectFilter"
                    class="h-10 w-64 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                    <option value="">All projects</option>
                    @foreach ($projectColorMap as $pid => $info)
                        <option value="{{ (int) $pid }}">{{ $info['name'] ?? '' }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-2" role="tablist" aria-label="Role filter">
                    @php $role = 'all'; @endphp
                    <button type="button"
                        class="chip inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                 border-border-default bg-surface-card text-text-base is-active"
                        data-role="all" aria-pressed="true">All</button>

                    <button type="button"
                        class="chip inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                 border-border-default bg-surface-card text-text-base"
                        data-role="internal" aria-pressed="false">Internal</button>

                    <button type="button"
                        class="chip inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                 border-border-default bg-surface-card text-text-base"
                        data-role="client" aria-pressed="false">Client</button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <label class="sr-only" for="taskSearch">Search tasks</label>
                <input id="taskSearch"
                    class="h-10 md:w-72 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm"
                    type="search" placeholder="Search tasks…">
                <button id="clearFilters" type="button"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium
                     border border-border-default bg-surface-card text-text-base hover:brightness-110">
                    Reset
                </button>
            </div>
        </div>

        {{-- Project Color Key --}}
        <section id="colorKey" class="rounded-xl p-3 border border-border-default bg-surface-accent">
            <div class="flex items-center gap-3 flex-wrap text-sm text-text-subtle">
                <span class="font-medium text-text-base">Project Color Key</span>
                <ul class="flex flex-wrap gap-4">
                    @foreach ($projectColorMap as $projectId => $info)
                        <li class="flex items-center gap-2">
                            <span class="inline-block w-3.5 h-3.5 rounded-sm border border-border-default"
                                style="background: {{ $info['color'] ?? 'rgb(var(--brand-secondary))' }};"></span>
                            <span>{{ $info['name'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Kanban Board --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-start">
            @foreach ($columns as $status => $label)
                @php
                    $groups = $tasksByStatus[$status] ?? [];
                    $statusCount = collect($groups)->sum(fn($g) => count($g['tasks'] ?? []));
                @endphp

                <div class="rounded-xl p-4 border border-border-default bg-surface-accent shadow-card {{ $statusCount ? '' : 'opacity-80' }}"
                    data-status="{{ $status }}">
                    <h3 class="sticky top-0 z-10 -mt-4 mb-3 pr-8 font-semibold text-text-base">
                        <span class="inline-flex items-center gap-2">
                            <span>{{ $label }}</span>
                            <span
                                class="text-[10px] font-bold rounded-full px-2 py-0.5 bg-surface-card text-text-base border border-border-default">
                                {{ $statusCount }}
                            </span>
                        </span>
                    </h3>

                    @forelse ($groups as $phaseId => $phaseGroup)
                        @php
                            $phaseName = $phaseGroup['phase_name'] ?? 'No Phase';
                            $phaseTasks = $phaseGroup['tasks'] ?? [];
                        @endphp

                        <section class="rounded-lg p-2">
                            <header class="flex items-center justify-between px-1 py-1.5">
                                <h4 class="text-sm font-semibold text-text-base">{{ $phaseName }}</h4>
                                <span
                                    class="text-[10px] font-bold rounded-full px-2 py-0.5 bg-surface-card text-text-base border border-border-default">
                                    {{ count($phaseTasks) }}
                                </span>
                            </header>

                            <div class="grid gap-2">
                                @forelse ($phaseTasks as $task)
                                    @php
                                        $tid = (int) ($task['id'] ?? 0);
                                        $assign = (string) ($task['assign_type'] ?? '');
                                        $isAdmin = $assign === 'admin';
                                        $projColor = $task['project_color'] ?? 'rgb(var(--brand-secondary))';
                                        $badge = $dueBadge($task['due_date'] ?? null);
                                    @endphp

                                    <article id="task-{{ $tid }}"
                                        class="relative rounded-xl border border-border-default bg-surface-card shadow-card p-4 hover:shadow-md transition"
                                        style="--proj-color: {{ $projColor }};" data-assign-type="{{ $assign }}"
                                        data-project-id="{{ $task['project_id'] ?? '' }}" role="listitem">
                                        {{-- color strip --}}
                                        <span class="absolute inset-y-0 left-0 w-1.5 rounded-l-xl"
                                            style="background: var(--proj-color);"></span>

                                        <div class="flex flex-wrap items-center gap-2 text-xs text-text-subtle pl-2">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 border border-border-default">
                                                <i class="fa-solid fa-briefcase text-[rgb(var(--brand-primary))]"></i>
                                                {{ $task['project_name'] ?? '—' }}
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 border border-border-default">
                                                {{ $isAdmin ? 'Admin/Internal' : 'Client' }}
                                            </span>

                                            @if (!empty($task['phase_name']))
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 border border-border-default">
                                                    {{ $task['phase_name'] }}
                                                </span>
                                            @endif

                                            <span
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 border {{ $badge['class'] }}"
                                                title="Due date">
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $task['due_date'] ?? '—' }} • {{ $badge['label'] }}
                                            </span>
                                        </div>

                                        <div class="pl-2 mt-2">
                                            <h5 class="text-sm font-bold tracking-wide text-text-base">
                                                {{ $task['title'] ?? 'Untitled Task' }}
                                            </h5>
                                            @if (!empty($task['description']))
                                                <p class="text-xs text-text-subtle mt-1 line-clamp-2">
                                                    {{ $task['description'] }}</p>
                                            @endif
                                        </div>

                                        <div class="flex justify-end gap-2 mt-3 pt-3 border-t border-border-default">
                                            @if ($status === 'open')
                                                <button
                                                    class="grid place-items-center w-8 h-8 rounded-md border border-border-default text-text-base bg-surface-accent hover:brightness-110"
                                                    data-next="in_progress" data-task-id="{{ $tid }}"
                                                    title="Start">
                                                    <i class="fa-solid fa-circle-play"></i>
                                                </button>
                                            @endif

                                            <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantParam, 'task' => $tid]) }}"
                                                class="grid place-items-center w-8 h-8 rounded-md border border-border-default text-text-base bg-surface-accent hover:brightness-110"
                                                title="Comments">
                                                <i class="fa-solid fa-comments"></i>
                                            </a>

                                            @if ($status !== 'completed')
                                                @php
                                                    $payload = [
                                                        'id' => (int) ($task['id'] ?? 0),
                                                        'title' => $task['title'] ?? '',
                                                        'description' => $task['description'] ?? '',
                                                        'due_date' => $task['due_date'] ?? null,
                                                        'assign_type' => $task['assign_type'] ?? '',
                                                        'assign_id' => $task['assign_id'] ?? null,
                                                        'project_id' => $task['project_id'] ?? null,
                                                        'phase_id' => $task['phase_id'] ?? null,
                                                    ];
                                                @endphp

                                                <button type="button" class="js-edit ..."
                                                    data-update-url="{{ route('tenant.tasks.update', ['tenant' => $tenantParam, 'task' => (int) ($task['id'] ?? 0)]) }}"
                                                    data-payload='@json($payload)'>
                                                    <i class="fa-solid fa-pencil"></i>
                                                </button>
                                            @else
                                                <span
                                                    class="grid place-items-center w-8 h-8 rounded-md border border-border-default text-green-600 bg-surface-accent"
                                                    title="Completed">
                                                    <i class="fa-solid fa-square-check"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    {{-- Per-phase empty state --}}
                                    <div
                                        class="rounded-lg border border-dashed border-border-default bg-surface-card text-text-subtle text-sm p-4">
                                        No tasks in this phase.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    @empty
                        {{-- Column empty state --}}
                        <div
                            class="rounded-xl border border-dashed border-border-default bg-surface-card text-text-subtle text-sm p-6">
                            Nothing here yet.
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>

    {{-- Edit Task Modal (inline) --}}
    <div id="editModal" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50"></div>

        <div
            class="relative max-w-xl mx-auto mt-20 rounded-xl border border-border-default bg-surface-card shadow-card p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-text-base">Edit Task</h2>
                <button class="text-text-subtle hover:text-text-base" type="button"
                    onclick="document.getElementById('editModal').classList.add('hidden')">&times;</button>
            </div>

            <form id="editTaskForm" method="POST" action="#">
                @csrf
                @method('PUT')

                <input type="hidden" name="task_id" id="task-id">
                <input type="hidden" name="assign_id" id="assign_id_final">

                <div class="grid gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Title</span>
                        <input type="text" name="title" id="task-title"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm"
                            required>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Description</span>
                        <textarea name="description" id="task-description"
                            class="min-h-[96px] rounded-lg bg-surface-card text-text-base border border-border-default px-3 py-2 text-sm"></textarea>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Due Date</span>
                            <input type="date" name="due_date" id="task-due-date"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Assign Type</span>
                            <select name="assign_type" id="task-assign-type" required
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                <option value="">Choose...</option>
                                <option value="admin">Admin</option>
                                <option value="client">Client</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Admin</span>
                            <select id="task-assign-admin"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                <option value="">Choose Admin</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user['id'] ?? '' }}">
                                        {{ $user['username'] ?? ($user['name'] ?? 'User') }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Client</span>
                            <select id="task-assign-client"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                <option value="">Choose Client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client['id'] ?? '' }}">
                                        {{ $client['client_name'] ?? ($client['name'] ?? 'Client') }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Project</span>
                            <select name="project_id" id="task-project-id"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                @foreach ($projects as $project)
                                    <option value="{{ $project['id'] ?? '' }}">
                                        {{ $project['project_name'] ?? ($project['name'] ?? 'Project') }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Phase</span>
                            <select name="phase_id" id="task-phase-id"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                @foreach ($phases as $phase)
                                    <option value="{{ $phase['id'] ?? '' }}">{{ $phase['name'] ?? '' }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium
                         border border-border-default bg-surface-card text-text-base hover:brightness-110"
                            onclick="document.getElementById('editModal').classList.add('hidden')">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white
                         bg-gradient-to-b from-brand-primary to-blue-700 shadow-card">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    @push('scripts')
        <script>
            // Toggle color key
            document.getElementById('toggleKey')?.addEventListener('click', () => {
                const el = document.getElementById('colorKey');
                if (!el) return;
                const shown = el.style.display !== 'none';
                el.style.display = shown ? 'none' : '';
                document.getElementById('toggleKey')?.setAttribute('aria-expanded', String(!shown));
            });

            // Role filter chips (with aria-pressed)
            const chips = document.querySelectorAll('[data-role]');
            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    chips.forEach(c => {
                        c.classList.remove('is-active');
                        c.setAttribute('aria-pressed', 'false');
                    });
                    chip.classList.add('is-active');
                    chip.setAttribute('aria-pressed', 'true');

                    const role = chip.dataset.role;
                    document.querySelectorAll('[role="listitem"]').forEach(card => {
                        const type = card.getAttribute('data-assign-type') || 'admin';
                        const show = role === 'all' || (role === 'internal' ? type === 'admin' :
                            type === 'client');
                        card.style.display = show ? '' : 'none';
                    });
                });
            });

            // Project filter
            const pf = document.getElementById('projectFilter');
            pf?.addEventListener('change', () => {
                const pid = pf.value;
                document.querySelectorAll('[role="listitem"]').forEach(card => {
                    const cid = card.getAttribute('data-project-id') || '';
                    card.style.display = (!pid || pid === cid) ? '' : 'none';
                });
            });

            // Search (debounced)
            const search = document.getElementById('taskSearch');
            let t;
            search?.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    const q = search.value.toLowerCase();
                    document.querySelectorAll('[role="listitem"]').forEach(card => {
                        const text = card.textContent.toLowerCase();
                        card.style.display = text.includes(q) ? '' : 'none';
                    });
                }, 120);
            });

            // Reset
            document.getElementById('clearFilters')?.addEventListener('click', () => {
                const all = document.querySelector('[data-role="all"]');
                all?.click();
                if (pf) pf.value = '';
                if (search) search.value = '';
                document.querySelectorAll('[role="listitem"]').forEach(c => c.style.display = '');
            });
            // Set assign_id from the matching dropdown on submit
            document.getElementById('editTaskForm')?.addEventListener('submit', function(e) {
                const type = document.getElementById('task-assign-type')?.value;
                const adminId = document.getElementById('task-assign-admin')?.value || '';
                const clientId = document.getElementById('task-assign-client')?.value || '';
                const targetId = (type === 'admin') ? adminId : (type === 'client' ? clientId : '');
                document.getElementById('assign_id_final').value = targetId || '';
            });
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.js-edit');
                if (!btn) return;

                const taskId = parseInt(btn.dataset.taskId, 10);
                const updateUrl = btn.dataset.updateUrl;
                const payloadRaw = btn.dataset.payload || '{}';
                let payload = {};
                try {
                    payload = JSON.parse(payloadRaw);
                } catch (_) {}

                openEditModal(taskId, updateUrl, payload);
            });
        </script>
        <script>
            (() => {
                // -------- Modal helpers (isolated) --------
                function toYMD(v) {
                    return v ? String(v).substring(0, 10) : '';
                }

                function selectValue(sel, val) {
                    if (!sel) return;
                    const s = (val ?? '').toString();
                    if (!s) {
                        sel.value = '';
                        return;
                    }
                    const exists = Array.from(sel.options).some(o => o.value === s);
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = s;
                        opt.textContent = `#${s}`;
                        sel.appendChild(opt);
                    }
                    sel.value = s;
                }

                function toggleAssignPickers(type) {
                    const adminWrap = document.getElementById('task-assign-admin')?.closest('label');
                    const clientWrap = document.getElementById('task-assign-client')?.closest('label');
                    if (!adminWrap || !clientWrap) return;
                    if (type === 'admin') {
                        adminWrap.style.display = '';
                        clientWrap.style.display = 'none';
                    } else if (type === 'client') {
                        adminWrap.style.display = 'none';
                        clientWrap.style.display = '';
                    } else {
                        adminWrap.style.display = '';
                        clientWrap.style.display = '';
                    }
                }

                function openEditModal(updateUrl, data) {
                    const modal = document.getElementById('editModal');
                    const form = document.getElementById('editTaskForm');

                    const title = document.getElementById('task-title');
                    const desc = document.getElementById('task-description');
                    const due = document.getElementById('task-due-date');
                    const proj = document.getElementById('task-project-id');
                    const phase = document.getElementById('task-phase-id');
                    const typeSel = document.getElementById('task-assign-type');
                    const adminSel = document.getElementById('task-assign-admin');
                    const clientSel = document.getElementById('task-assign-client');

                    form.setAttribute('action', updateUrl);

                    title.value = data.title || '';
                    desc.value = data.description || '';
                    due.value = toYMD(data.due_date);

                    selectValue(proj, data.project_id);
                    selectValue(phase, data.phase_id);

                    typeSel.value = data.assign_type || '';
                    if (data.assign_type === 'admin') {
                        selectValue(adminSel, data.assign_id);
                        selectValue(clientSel, '');
                    } else if (data.assign_type === 'client') {
                        selectValue(clientSel, data.assign_id);
                        selectValue(adminSel, '');
                    } else {
                        selectValue(adminSel, '');
                        selectValue(clientSel, '');
                    }

                    toggleAssignPickers(typeSel.value);
                    typeSel.onchange = () => toggleAssignPickers(typeSel.value);

                    modal.classList.remove('hidden');
                }

                // Open modal from any .js-edit button
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.js-edit');
                    if (!btn) return;
                    let payload = {};
                    try {
                        payload = JSON.parse(btn.getAttribute('data-payload') || '{}');
                    } catch (err) {
                        console.error('[edit] bad payload', err);
                        return;
                    }
                    openEditModal(btn.dataset.updateUrl, payload);
                });

                // Write assign_id before submit
                document.getElementById('editTaskForm')?.addEventListener('submit', () => {
                    const type = document.getElementById('task-assign-type')?.value;
                    const adminId = document.getElementById('task-assign-admin')?.value || '';
                    const clientId = document.getElementById('task-assign-client')?.value || '';
                    document.getElementById('assign_id_final').value =
                        (type === 'admin') ? adminId : (type === 'client' ? clientId : '');
                });
            })();
        </script>
    @endpush
@endsection
