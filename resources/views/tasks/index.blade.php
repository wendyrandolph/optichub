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
                ->flatMap(fn($g) => collect(data_get($g, 'tasks', [])))
                ->count(),
            'in_progress' => collect($tasksByStatus['in_progress'] ?? [])
                ->flatMap(fn($g) => collect(data_get($g, 'tasks', [])))
                ->count(),
            'completed' => collect($tasksByStatus['completed'] ?? [])
                ->flatMap(fn($g) => collect(data_get($g, 'tasks', [])))
                ->count(),
        ];

        // Due-date badge mapping
        $dueBadge = function (?string $date) {
            if (!$date) {
                return ['label' => 'No due date', 'class' => 'oh-pill oh-pill--muted'];
            }

            $today = now()->startOfDay();
            $d = \Carbon\Carbon::parse($date)->startOfDay();

            if ($d->lt($today)) {
                return ['label' => 'Overdue', 'class' => 'oh-pill oh-pill--danger'];
            }
            if ($d->isSameDay($today)) {
                return ['label' => 'Today', 'class' => 'oh-pill oh-pill--accent'];
            }
            if ($d->lte($today->copy()->addDays(3))) {
                return ['label' => 'Soon', 'class' => 'oh-pill oh-pill--brand'];
            }
            return ['label' => 'Scheduled', 'class' => 'oh-pill oh-pill--success'];
        };
    @endphp


    <div class="oh-page">

        {{-- Header / Actions (matches Reports / Lead Insights hierarchy) --}}
        <header class="flex flex-col gap-2">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Tasks</div>
                    <h1 class="text-2xl font-semibold tracking-tight text-text-base">Tasks Overview</h1>
                    <p class="text-sm text-text-subtle">Track progress and keep work moving.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.tasks.create', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--primary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        New Task
                    </a>

                    <button id="toggleKey" type="button" aria-controls="colorKey" aria-expanded="true" class="oh-btn">
                        Toggle Color Key
                    </button>
                </div>
            </div>

            {{-- flash messages --}}
            @if (session('success_message') || session('error_message'))
                <div class="space-y-2">
                    @if (session('success_message'))
                        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('success_message') }}
                        </div>
                    @endif
                    @if (session('error_message'))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {{ session('error_message') }}
                        </div>
                    @endif
                </div>
            @endif
        </header>

        {{-- Quick KPI strip -> make it a real “system” card --}}
        <section class="oh-card py-3">
            <ul class="flex flex-wrap items-center gap-3 text-sm">
                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[rgb(var(--brand-secondary))]"></span>
                    <span class="text-text-subtle">To Do</span>
                    <span class="oh-chip-muted tabular-nums">{{ $counts['open'] }}</span>
                </li>

                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    <span class="text-text-subtle">Working On</span>
                    <span class="oh-chip-muted tabular-nums">{{ $counts['in_progress'] }}</span>
                </li>

                <li class="inline-flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[rgb(var(--status-success))]"></span>
                    <span class="text-text-subtle">Done</span>
                    <span class="oh-chip-muted tabular-nums">{{ $counts['completed'] }}</span>
                </li>
            </ul>
        </section>

        {{-- Toolbar -> same surface language as other pages --}}
        <section class="oh-card bg-white">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full">
                    <label class="sr-only" for="projectFilter">Filter by project</label>
                    <select id="projectFilter" class="oh-select w-full sm:w-64">
                        <option value="">All projects</option>
                        @foreach ($projectColorMap as $pid => $info)
                            <option value="{{ (int) $pid }}">{{ data_get($info, 'name', '') }}</option>
                        @endforeach
                    </select>

                    {{-- Role filter chips -> token pills --}}
                    <div class="flex items-center gap-2 flex-wrap" role="tablist" aria-label="Role filter">
                        <button type="button" class="oh-pill is-active" data-role="all" aria-pressed="true">All</button>

                        <button type="button" class="oh-pill" data-role="internal" aria-pressed="false">Internal</button>

                        <button type="button" class="oh-pill" data-role="client" aria-pressed="false">Client</button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 flex-wrap justify-start lg:justify-end w-full lg:w-auto">
                    <label class="sr-only" for="taskSearch">Search tasks</label>
                    <input id="taskSearch" type="search" placeholder="Search tasks…"
                        class="oh-input w-full sm:w-64 md:w-72" />

                    <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                        <input type="checkbox" id="myTasksOnly"
                            class="rounded border-[rgb(var(--border-default))] text-[rgb(var(--brand-primary))]" />
                        My tasks only
                    </label>

                    <button id="clearFilters" type="button" class="oh-btn w-full sm:w-auto">Reset</button>
                </div>
            </div>
        </section>

        {{-- Project Color Key -> keep, but make it match the system --}}
        <section id="colorKey" class="oh-card bg-white py-3">
            <div class="flex items-center gap-3 flex-wrap text-sm">
                <span class="font-semibold text-text-base">Project Color Key</span>
                <ul class="flex flex-wrap gap-4">
                    @foreach ($projectColorMap as $projectId => $info)
                        <li class="flex items-center gap-2 text-text-subtle">
                            <span class="inline-block w-3.5 h-3.5 rounded-sm border border-[rgb(var(--border-default))]"
                                style="background: {{ data_get($info, 'color', 'rgb(var(--brand-secondary))') }};"></span>
                            <span>{{ data_get($info, 'name', '') }}</span>
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
                    $statusCount = collect($groups)->sum(fn($g) => count(data_get($g, 'tasks', [])));
                    $colAccent = match ($status) {
                        'open' => 'rgb(var(--brand-secondary))',
                        'in_progress' => 'rgb(var(--brand-primary))',
                        'completed' => 'rgb(var(--status-success))',
                        default => 'rgb(var(--brand-secondary))',
                    };
                @endphp

                {{-- Column matches report tile language: left accent strip + oh-card --}}
                <section class="oh-card relative overflow-hidden p-0 {{ $statusCount ? '' : 'opacity-85' }}"
                    data-status="{{ $status }}" style="--tile-accent: {{ $colAccent }};">
                    <span class="absolute inset-y-0 left-0 w-[4px]"></span>

                    <header class="p-4 pb-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-base">
                                {{ $label }}
                                <span class="oh-chip-muted ml-2">{{ $statusCount }}</span>
                            </h3>
                        </div>
                    </header>

                    <div class="px-4 pb-4 space-y-3">
                        @forelse ($groups as $phaseId => $phaseGroup)
                            @php
                                $phaseName = data_get($phaseGroup, 'phase_name', 'No Phase');
                                $phaseTasks = data_get($phaseGroup, 'tasks', []);
                            @endphp

                            {{-- Phase group gets a subtle inset card so it reads like your other pages --}}
                            <section class="p-3">
                                <header class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-text-base">{{ $phaseName }}</h4>
                                    <span class="oh-chip-muted">{{ count($phaseTasks) }}</span>
                                </header>

                                <div class="mt-3 grid gap-2">
                                    @forelse ($phaseTasks as $task)
                                        @php
                                            $tid = (int) data_get($task, 'id', 0);
                                            $assign = (string) data_get($task, 'assign_type', '');
                                            $isAdmin = $assign === 'admin';
                                            $projColor = data_get(
                                                $task,
                                                'project_color',
                                                'rgb(var(--brand-secondary))',
                                            );
                                            $badge = $dueBadge(data_get($task, 'due_date'));
                                            $assignId = data_get($task, 'assign_id');
                                        @endphp

                                        {{-- Task card surface matches system; project color strip stays --}}
                                        <article id="task-{{ $tid }}"
                                            class="relative rounded-xl border border-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))] p-4 shadow-sm hover:shadow-md transition"
                                            style="--proj-color: {{ $projColor }};"
                                            data-assign-type="{{ $assign }}"
                                            data-project-id="{{ data_get($task, 'project_id') ?? '' }}"
                                            data-assign-id="{{ $assignId }}" role="listitem">

                                            {{-- project color strip (keep) --}}
                                            <span class="absolute inset-y-0 left-0 w-1.5 rounded-l-xl"
                                                style="background: var(--proj-color);"></span>

                                            {{-- Meta pills row -> switch to oh-pill family for unity --}}
                                            <div class="flex flex-wrap items-center gap-2 pl-2">
                                                <span class="oh-pill oh-pill--muted">
                                                    <i class="fa-solid fa-briefcase text-[rgb(var(--brand-primary))]"></i>
                                                    {{ data_get($task, 'project_name', '—') }}
                                                </span>

                                                <span class="oh-pill oh-pill--muted">
                                                    {{ $isAdmin ? 'Admin/Internal' : 'Client' }}
                                                </span>

                                                @if (!empty(data_get($task, 'phase_name')))
                                                    <span class="oh-pill oh-pill--muted">
                                                        {{ data_get($task, 'phase_name') }}
                                                    </span>
                                                @endif

                                                <span class="{{ $badge['class'] }}" title="Due date">
                                                    <i class="fa-regular fa-calendar"></i>
                                                    {{ data_get($task, 'due_date', '—') }} • {{ $badge['label'] }}
                                                </span>
                                            </div>

                                            <div class="pl-2 mt-2 space-y-1">
                                                <h5 class="text-sm font-semibold text-text-base">
                                                    {{ data_get($task, 'title', 'Untitled Task') }}
                                                </h5>

                                                @if (!empty(data_get($task, 'description')))
                                                    <p class="text-xs text-text-subtle line-clamp-2">
                                                        {{ data_get($task, 'description') }}
                                                    </p>
                                                @endif

                                                <p class="text-[11px] text-text-subtle space-x-2">
                                                    @if (!empty(data_get($task, 'started_at')))
                                                        <span>Started:
                                                            {{ \Carbon\Carbon::parse(data_get($task, 'started_at'))->format('M j, Y') }}</span>
                                                    @endif
                                                    @if (!empty(data_get($task, 'completed_at')))
                                                        <span>Completed:
                                                            {{ \Carbon\Carbon::parse(data_get($task, 'completed_at'))->format('M j, Y') }}</span>
                                                    @endif
                                                    @if (!empty(data_get($task, 'hours_spent')))
                                                        <span>Hours:
                                                            {{ number_format(data_get($task, 'hours_spent'), 2) }}</span>
                                                    @endif
                                                </p>
                                            </div>

                                            {{-- Actions -> make consistent buttons (oh-btn sized icon buttons) --}}
                                            <div
                                                class="mt-3 pt-3 border-t border-[rgb(var(--border)/.55)] flex justify-end gap-2">
                                                @php
                                                    $currentUser = auth()->user();
                                                    $role = strtolower((string) ($currentUser?->role ?? ''));
                                                    $canDelete =
                                                        in_array($role, ['provider', 'admin', 'super_admin', 'superadmin'], true) ||
                                                        ((int) (data_get($task, 'user_id') ?? 0) === (int) ($currentUser?->id ?? 0));
                                                    $isMine =
                                                        auth()->check() &&
                                                        (data_get($task, 'assign_type') ?? '') === 'admin' &&
                                                        (int) (data_get($task, 'assign_id') ?? 0) === auth()->id();
                                                    $isMagicLinkTask =
                                                        \Illuminate\Support\Str::startsWith(
                                                            (string) data_get($task, 'title', ''),
                                                            'Send new portal link to ',
                                                        ) && !empty(data_get($task, 'contact_id'));
                                                @endphp

                                                @if ($status === 'open' && $isMine)
                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.start', ['tenant' => $tenantParam, 'task' => $tid]) }}">
                                                        @csrf
                                                        <button type="submit" class="oh-btn oh-tooltip"
                                                            aria-label="Start task" data-tooltip="Start" title="Start"
                                                            style="width:2rem;padding:0;">
                                                            <i class="fa-solid fa-circle-play text-xs"></i>
                                                        </button>
                                                    </form>
                                                @elseif ($status === 'in_progress' && $isMine)
                                                    <form method="POST"
                                                        action="{{ data_get($task, 'timer_started_at')
                                                            ? route('tenant.tasks.pause', ['tenant' => $tenantParam, 'task' => $tid])
                                                            : route('tenant.tasks.resume', ['tenant' => $tenantParam, 'task' => $tid]) }}">
                                                        @csrf
                                                        <button type="submit" class="oh-btn oh-tooltip"
                                                            aria-label="{{ data_get($task, 'timer_started_at') ? 'Pause task' : 'Resume task' }}"
                                                            data-tooltip="{{ data_get($task, 'timer_started_at') ? 'Pause' : 'Resume' }}"
                                                            title="{{ data_get($task, 'timer_started_at') ? 'Pause' : 'Resume' }}"
                                                            style="width:2rem;padding:0;">
                                                            <i
                                                                class="fa-solid {{ data_get($task, 'timer_started_at') ? 'fa-pause' : 'fa-play' }} text-xs"></i>
                                                        </button>
                                                    </form>

                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.complete', ['tenant' => $tenantParam, 'task' => $tid]) }}">
                                                        @csrf
                                                        <button type="submit" class="oh-btn oh-btn--primary oh-tooltip"
                                                            aria-label="Mark complete" data-tooltip="Complete" title="Complete"
                                                            style="width:2rem;padding:0;background: rgb(var(--status-success));border-color: rgb(var(--status-success));">
                                                            <i class="fa-solid fa-check text-xs"></i>
                                                        </button>
                                                    </form>
                                                @elseif ($status === 'completed' && $isMine)
                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.archive', ['tenant' => $tenantParam, 'task' => $tid]) }}">
                                                        @csrf
                                                        <button type="submit" class="oh-btn oh-tooltip"
                                                            aria-label="Archive task" data-tooltip="Archive" title="Archive"
                                                            style="width:2rem;padding:0;">
                                                            <i class="fa-solid fa-box-archive text-xs"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantParam, 'task' => $tid]) }}"
                                                    class="oh-btn oh-tooltip" aria-label="View task" data-tooltip="View"
                                                    title="View"
                                                    style="width:2rem;padding:0;">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </a>

                                                @if (!empty(data_get($task, 'project_id')))
                                                    <a href="{{ route('tenant.projects.messages.index', ['tenant' => $tenantParam, 'project' => (int) data_get($task, 'project_id')]) }}"
                                                        class="oh-btn oh-tooltip" aria-label="Project messages"
                                                        data-tooltip="Project chat" title="Project chat"
                                                        style="width:2rem;padding:0;">
                                                        <i class="fa-solid fa-comments text-xs"></i>
                                                    </a>
                                                @else
                                                    <button type="button"
                                                        class="oh-btn oh-tooltip opacity-60 cursor-not-allowed"
                                                        aria-label="Project messages unavailable"
                                                        data-tooltip="Project chat unavailable"
                                                        title="Project chat unavailable"
                                                        style="width:2rem;padding:0;" aria-disabled="true">
                                                        <i class="fa-solid fa-comments text-xs"></i>
                                                    </button>
                                                @endif

                                                @if ($isMagicLinkTask)
                                                    <form method="POST"
                                                        action="{{ route('tenant.contacts.magic-link', ['tenant' => $tenantParam, 'contact' => (int) data_get($task, 'contact_id')]) }}">
                                                        @csrf
                                                        <button type="submit" class="oh-btn oh-btn--primary oh-tooltip"
                                                            aria-label="Send magic link" data-tooltip="Send link" title="Send link"
                                                            style="width:2rem;padding:0;">
                                                            <i class="fa-solid fa-link text-xs"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($status !== 'completed')
                                                    @php
                                                        $payload = [
                                                            'id' => (int) data_get($task, 'id', 0),
                                                            'title' => data_get($task, 'title', ''),
                                                            'description' => data_get($task, 'description'),
                                                            'due_date' => data_get($task, 'due_date'),
                                                            'assign_type' => data_get($task, 'assign_type', ''),
                                                            'assign_id' => data_get($task, 'assign_id'),
                                                            'project_id' => data_get($task, 'project_id'),
                                                            'phase_id' => data_get($task, 'phase_id'),
                                                            'status' => data_get($task, 'status', $status),
                                                        ];
                                                    @endphp

                                                    <button type="button" class="oh-btn oh-tooltip js-edit"
                                                        aria-label="Edit task" data-tooltip="Edit" title="Edit"
                                                        style="width:2rem;padding:0;"
                                                        data-task-id="{{ $tid }}"
                                                        data-update-url="{{ route('tenant.tasks.update', ['tenant' => $tenantParam, 'task' => (int) data_get($task, 'id', 0)]) }}"
                                                        data-payload='@json($payload)'>
                                                        <i class="fa-solid fa-pencil text-xs"></i>
                                                    </button>
                                                @else
                                                    <span class="oh-btn oh-tooltip" style="width:2rem;padding:0;opacity:.85;"
                                                        aria-label="Completed" data-tooltip="Completed" title="Completed">
                                                        <i class="fa-solid fa-square-check text-xs"></i>
                                                    </span>
                                                @endif

                                                @if ($canDelete)
                                                    <form method="POST"
                                                        action="{{ route('tenant.tasks.destroy', ['tenant' => $tenantParam, 'task' => $tid]) }}"
                                                        onsubmit="return confirm('Delete this task? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="oh-btn oh-tooltip"
                                                            aria-label="Delete task" data-tooltip="Delete" title="Delete"
                                                            style="width:2rem;padding:0;">
                                                            <i class="fa-solid fa-trash text-xs"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <div
                                            class="rounded-xl border border-dashed border-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))] text-text-subtle text-sm p-4">
                                            No tasks in this phase.
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))] text-text-subtle text-sm p-6">
                                Nothing here yet.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    {{-- Edit Task Modal (inline) --}}
    <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 pointer-events-none"
        aria-hidden="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <!-- Panel (centered) -->
        <div id="editModalPanel"
            class="relative w-[min(720px,calc(100vw-2rem))] rounded-xl border border-border-default bg-white shadow-2xl p-6 max-h-[90vh] overflow-auto pointer-events-auto transform-gpu"
            style="transform: translateY(0);">


            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-text-base">Edit Task</h2>
                <button type="button" id="closeEditModal" class="text-text-subtle hover:text-text-base"
                    aria-label="Close">
                    &times;
                </button>
            </div>

            <form id="editTaskForm" method="POST" action="#">
                @csrf
                @method('PUT')

                <input type="hidden" name="task_id" id="task-id">
                <input type="hidden" name="assign_id" id="assign_id_final">
                <input type="hidden" name="status" id="task-status">
                <input type="hidden" name="return_url" id="task-return-url">

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
                            <span class="text-text-subtle">Hours spent</span>
                            <input type="number" step="0.25" min="0" name="hours_spent" id="task-hours"
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
                                    @php
                                        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                        $label = $name ?: $user['username'] ?? 'User';
                                    @endphp
                                    <option value="{{ $user['id'] ?? '' }}">
                                        {{ $label }}</option>
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

                    <div class="grid gap-3">
                        <label
                            class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)] bg-surface-card"
                            id="requires-approval-wrap" style="display: none;">
                            <input type="checkbox" name="requires_approval" id="task-requires-approval" value="1"
                                class="mt-1 rounded border-border-default text-brand-primary" />
                            <div>
                                <div class="text-sm font-semibold text-text-base">Requires approval</div>
                                <div class="text-xs text-text-subtle">Shows in the client thread as an approval item.</div>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Project</span>
                            <select name="project_id" id="task-project-id"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                @foreach ($projects as $project)
                                    <option value="{{ data_get($project, 'id') ?? '' }}">
                                        {{ data_get($project, 'project_name') ?? data_get($project, 'name', 'Project') }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Phase</span>
                            <select name="phase_id" id="task-phase-id"
                                class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3 text-sm">
                                @foreach ($phases as $phase)
                                    <option value="{{ data_get($phase, 'id') ?? '' }}">{{ data_get($phase, 'name', '') }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="cancelEditModal"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium
                         border border-border-default bg-surface-card text-text-base hover:brightness-110">
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

            // My tasks only
            const myOnly = document.getElementById('myTasksOnly');
            const currentUserId = {{ auth()->id() ?? 'null' }};

            // Search (debounced)
            const search = document.getElementById('taskSearch');
            let t;
            search?.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    const q = search.value.toLowerCase();
                    document.querySelectorAll('[role="listitem"]').forEach(card => {
                        const text = card.textContent.toLowerCase();
                        const show = text.includes(q) && (!myOnly?.checked || card.getAttribute(
                            'data-assign-id') == currentUserId);
                        card.style.display = show ? '' : 'none';
                    });
                }, 120);
            });

            myOnly?.addEventListener('change', () => {
                document.querySelectorAll('[role="listitem"]').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const show = text.includes(search?.value.toLowerCase() || '') &&
                        (!myOnly.checked || card.getAttribute('data-assign-id') == currentUserId);
                    card.style.display = show ? '' : 'none';
                });
            });

            // Reset
            document.getElementById('clearFilters')?.addEventListener('click', () => {
                const all = document.querySelector('[data-role="all"]');
                all?.click();
                if (pf) pf.value = '';
                if (search) search.value = '';
                if (myOnly) myOnly.checked = false;
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

                const updateUrl = btn.dataset.updateUrl;
                const payloadRaw = btn.dataset.payload || '{}';
                let payload = {};
                try {
                    payload = JSON.parse(payloadRaw);
                } catch (_) {}

                openEditModal(updateUrl, payload);
            });

            // Auto-open edit modal if ?edit={id} is in the URL
            window.addEventListener('DOMContentLoaded', () => {
                const params = new URLSearchParams(window.location.search);
                const editId = params.get('edit');
                if (!editId) return;

                // try immediately
                let btn = document.querySelector(`.js-edit[data-task-id="${editId}"]`);
                if (btn) {
                    btn.click();
                    return;
                }

                // if not found yet (e.g., dynamic render), retry once after a short delay
                setTimeout(() => {
                    btn = document.querySelector(`.js-edit[data-task-id="${editId}"]`);
                    if (btn) {
                        btn.click();
                    }
                }, 150);
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
                    const reqWrap = document.getElementById('requires-approval-wrap');
                    if (!adminWrap || !clientWrap) return;
                    if (type === 'admin') {
                        adminWrap.style.display = '';
                        clientWrap.style.display = 'none';
                        if (reqWrap) reqWrap.style.display = 'none';
                    } else if (type === 'client') {
                        adminWrap.style.display = 'none';
                        clientWrap.style.display = '';
                        if (reqWrap) reqWrap.style.display = '';
                    } else {
                        adminWrap.style.display = '';
                        clientWrap.style.display = '';
                        if (reqWrap) reqWrap.style.display = 'none';
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
                    const hiddenId = document.getElementById('task-id');
                    const statusInput = document.getElementById('task-status');
                    const hoursInput = document.getElementById('task-hours');
                    const reqApproval = document.getElementById('task-requires-approval');

                    form.setAttribute('action', updateUrl);
                    if (hiddenId) hiddenId.value = data.id || '';
                    if (statusInput) statusInput.value = data.status || '';

                    title.value = data.title || '';
                    desc.value = data.description || '';
                    due.value = toYMD(data.due_date);

                    selectValue(proj, data.project_id);
                    selectValue(phase, data.phase_id);
                    if (hoursInput) hoursInput.value = data.hours_spent ?? '';
                    if (reqApproval) reqApproval.checked = !!data.requires_approval;

                    typeSel.value = data.assign_type || '';
                    if (data.assign_type === 'admin') {
                        selectValue(adminSel, data.assign_id);
                        selectValue(clientSel, '');
                        if (reqApproval) reqApproval.checked = false;
                    } else if (data.assign_type === 'client') {
                        selectValue(clientSel, data.assign_id);
                        selectValue(adminSel, '');
                        if (reqApproval) reqApproval.checked = !!data.requires_approval;
                    } else {
                        selectValue(adminSel, '');
                        selectValue(clientSel, '');
                        if (reqApproval) reqApproval.checked = false;
                    }

                    toggleAssignPickers(typeSel.value);
                    typeSel.onchange = () => {
                        toggleAssignPickers(typeSel.value);
                        if (reqApproval) {
                            reqApproval.checked = typeSel.value === 'client' ? reqApproval.checked : false;
                        }
                    };

                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    modal.scrollIntoView({
                        block: 'center',
                        inline: 'center',
                        behavior: 'auto'
                    });
                }

                function closeEditModal() {
                    const modal = document.getElementById('editModal');
                    if (!modal) return;
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
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

                document.getElementById('closeEditModal')?.addEventListener('click', closeEditModal);
                document.getElementById('cancelEditModal')?.addEventListener('click', closeEditModal);

                // Write assign_id before submit
                document.getElementById('editTaskForm')?.addEventListener('submit', () => {
                    const type = document.getElementById('task-assign-type')?.value;
                    const adminId = document.getElementById('task-assign-admin')?.value || '';
                    const clientId = document.getElementById('task-assign-client')?.value || '';
                    document.getElementById('assign_id_final').value =
                        (type === 'admin') ? adminId : (type === 'client' ? clientId : '');
                    // propagate return URL if present on query (?back=...)
                    const backParam = new URLSearchParams(window.location.search).get('back');
                    if (backParam) {
                        const ret = document.getElementById('task-return-url');
                        if (ret) ret.value = backParam;
                    }
                });

                // Close modal on Escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        closeEditModal();
                    }
                });
                // Close modal when clicking backdrop
                document.getElementById('editModal')?.addEventListener('click', (e) => {
                    if (e.target.id === 'editModal') {
                        closeEditModal();
                    }
                });
            })();
        </script>
    @endpush
@endsection
