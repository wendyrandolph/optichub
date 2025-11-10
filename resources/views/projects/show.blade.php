@extends('layouts.app')

@section('content')
    @php
        $tenantId = $tenant->id ?? (auth()->user()?->tenant_id ?? tenant('id'));
        $projectId = data_get($project, 'id', 0);
        $projectName = data_get($project, 'project_name', data_get($project, 'name', ''));
        $projectOwnerName = data_get(
            $project,
            'project_owner_name',
            data_get($project, 'owner.name', data_get($project, 'owner_name', 'N/A'))
        );
        $organizationName = data_get(
            $project,
            'organization_name',
            data_get($project, 'organization.name', 'N/A')
        );
        $description = data_get($project, 'description', '');

        $formatDate = static function ($value, string $format = 'M j, Y') {
            if (empty($value)) {
                return null;
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->format($format);
            } catch (\Exception $e) {
                return null;
            }
        };

        $slugClass = static function ($value) {
            if (empty($value)) {
                return 'phase-unknown';
            }
            return 'phase-' . \Illuminate\Support\Str::slug($value, '-');
        };

        $startLabel = $formatDate(
            data_get($project, 'start_date', data_get($project, 'startDate'))
        ) ?? '—';
        $nextDueValue = $next_due ?? data_get($project, 'next_due');
        $nextDueLabel = $formatDate($nextDueValue, 'M j') ?? '—';

        $overall = (int) data_get($project, 'percent_complete', data_get($project, 'percentComplete', 0));
        $openCount = (int) data_get($counts ?? [], 'open', 0);
        $overdue = (int) data_get($counts ?? [], 'overdue', 0);
        $blocked = (int) data_get($counts ?? [], 'blocked', 0);

        $phasesList = $phases ?? [];
    @endphp

    <div class="container">
        <div class="project-view">
            <div class="project-header">
                <span> Project: </span>
                <h2 class="project-title">{{ $projectName }}</h2>
                <div class="project-meta">
                    <span><strong>Start:</strong> {{ $startLabel }}</span>
                    <span><strong>Contact:</strong> {{ $projectOwnerName ?? 'N/A' }}</span>
                    <span><strong>Company:</strong> {{ $organizationName ?? 'N/A' }}</span>
                </div>
                @if (!empty($description))
                    <p class="project-desc">{!! nl2br(e($description)) !!}</p>
                @endif
            </div>

            <div class="project-actions">
                <a href="{{ route('tenant.projects.edit', ['tenant' => $tenantId, 'project' => $projectId]) }}"
                    class="btn btn--ghost">Edit Project</a>
                <button class="btn btn--brand" onclick="openNewTaskForProject({{ $projectId }})">
                    <i class="fa fa-plus"></i> Add Task
                </button>
                <button class="btn btn--ghost js-toggle-all" data-state="collapsed">Expand all</button>
                <a class="btn btn--primary" href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}">All projects</a>
            </div>

            <div class="proj-kpis">
                <div class="kpi">
                    <span class="kpi__label">Progress</span>
                    <div class="kpi__bar"><i style="width: {{ $overall }}%"></i></div>
                    <span class="kpi__value">{{ $overall }}%</span>
                </div>
                <div class="kpi">
                    <span class="kpi__label">Open</span>
                    <span class="kpi__value">{{ $openCount }}</span>
                </div>
                <div class="kpi {{ $overdue ? 'kpi--warn' : '' }}">
                    <span class="kpi__label">Overdue</span>
                    <span class="kpi__value">{{ $overdue }}</span>
                </div>
                <div class="kpi">
                    <span class="kpi__label">Blocked</span>
                    <span class="kpi__value">{{ $blocked }}</span>
                </div>
                <div class="kpi">
                    <span class="kpi__label">Next due</span>
                    <span class="kpi__value">{{ $nextDueLabel }}</span>
                </div>
            </div>

            <h3 class="section-heading">Assigned Tasks</h3>
            <div class="phases-grid">
                @forelse ($phasesList as $phase)
                    @php
                        $phaseName = data_get($phase, 'name', 'Phase');
                        $phaseClass = $slugClass($phaseName);
                        $phaseCounts = data_get($phase, 'counts', []);
                        $phaseTotal = (int) data_get($phaseCounts, 'total', count(data_get($phase, 'tasks', [])));
                        $phaseOpen = (int) data_get($phaseCounts, 'open', 0);
                        $phaseOverdue = (int) data_get($phaseCounts, 'overdue', 0);
                        $phasePercent = (int) data_get($phase, 'percent_complete', data_get($phase, 'percentComplete', 0));
                        $tasks = data_get($phase, 'tasks', []);
                    @endphp
                    <div class="phase-card {{ $phaseClass }}">
                        <details class="phase-block">
                            <summary class="phase-summary {{ $phaseClass }}">
                                <span class="caret" aria-hidden="true"></span>
                                <strong>{{ $phaseName }}</strong>
                                <div class="phase-meta">
                                    <span class="chip" title="Total tasks">{{ $phaseTotal }}</span>
                                    <span class="chip chip--open" title="Open">{{ $phaseOpen }} open</span>
                                    <span class="chip chip--overdue" title="Overdue">{{ $phaseOverdue }} overdue</span>
                                    <div class="phase-progress">
                                        <span class="progress-text">{{ $phasePercent }}% complete</span>
                                        <div class="progress-bar">
                                            <div class="fill" style="width: {{ $phasePercent }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </summary>

                            @if (!empty($tasks))
                                <div class="task-header-row {{ $phaseClass }}">
                                    <div class="col-title">Task</div>
                                    <div class="col-meta">Assigned To</div>
                                    <div class="col-meta">Due</div>
                                    <div class="col-meta">Status</div>
                                    <div class="col-desc">Description</div>
                                    <div class="col-actions">Actions</div>
                                </div>
                                @foreach ($tasks as $task)
                                    @php
                                        $taskId = data_get($task, 'id');
                                        $taskTitle = data_get($task, 'title', 'Untitled');
                                        $taskDescription = data_get($task, 'description', '');
                                        $taskDue = $formatDate(data_get($task, 'due_date', data_get($task, 'dueDate')));
                                        $taskStatus = data_get($task, 'status', 'pending');
                                        $taskPhaseId = data_get($task, 'phase_id', data_get($task, 'phaseId'));
                                    @endphp
                                    <div class="task-row">
                                        <div class="col-title">
                                            <a href="{{ url('/tasks#task-' . $taskId) }}" class="task-link">
                                                {{ $taskTitle }}
                                            </a>
                                        </div>
                                        <div class="col-meta">
                                            <span>{{ data_get($task, 'assigned_name', 'Unassigned') }}</span>
                                        </div>
                                        <div class="col-meta">
                                            <span>{{ $taskDue ?? '—' }}</span>
                                        </div>
                                        <div class="col-meta">
                                            <span class="badge status-{{ $taskStatus }}">
                                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $taskStatus)) }}
                                            </span>
                                        </div>
                                        <div class="col-desc">
                                            {!! nl2br(e($taskDescription)) !!}
                                        </div>
                                        <div class="col-actions">
                                            <button class="btn btn-sm btn-edit"
                                                onclick="openEditTaskModal(this)"
                                                data-id="{{ $taskId }}"
                                                data-title="{{ $taskTitle }}"
                                                data-description="{{ $taskDescription }}"
                                                data-due-date="{{ data_get($task, 'due_date', data_get($task, 'dueDate')) }}"
                                                data-assign-id="{{ data_get($task, 'assign_id', data_get($task, 'assignId')) }}"
                                                data-assign-type="{{ data_get($task, 'assign_type', data_get($task, 'assignType')) }}"
                                                data-project-id="{{ data_get($task, 'project_id', data_get($task, 'projectId')) }}"
                                                data-phase-id="{{ $taskPhaseId }}">
                                                Edit
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="no-tasks"><em>No tasks assigned right now.</em></p>
                            @endif
                        </details>
                    </div>
                @empty
                    <p class="no-tasks"><em>No phases defined for this project yet.</em></p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="editTaskModal" class="modal hidden">
        <div class="modal-content">
            <span class="close" onclick="closeEditTaskModal()">&times;</span>
            <h3>Edit Task</h3>
            <form id="editTaskForm" method="POST" action="#">
                @csrf
                <input type="hidden" name="id" id="edit-id">
                <input type="hidden" name="project_id" id="edit-project-id" value="{{ $projectId }}">

                <label for="edit-title">Title:</label>
                <input type="text" name="title" id="edit-title" required>

                <label for="edit-description">Description:</label>
                <textarea name="description" id="edit-description"></textarea>

                <label for="edit-due-date">Due Date:</label>
                <input type="date" name="due_date" id="edit-due-date" required>

                <label for="edit-assign-type">Assign Type:</label>
                <select name="assign_type" id="edit-assign-type" onchange="filterAssignIdDropdown()">
                    <option value="user">User</option>
                    <option value="client">Client</option>
                </select>

                <label for="edit-assign-id">Assigned To:</label>
                <select name="assign_id" id="edit-assign-id" required></select>

                <label for="edit-phase-id">Phase ID:</label>
                <select name="phase_id" id="edit-phase-id">
                    <option value="">No Phase</option>
                    @foreach ($phasesList as $phaseOption)
                        @php
                            $phaseOptionId = data_get($phaseOption, 'id');
                        @endphp
                        <option value="{{ $phaseOptionId }}">{{ data_get($phaseOption, 'name') }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-save">Save</button>
            </form>
        </div>
    </div>

    <div id="project-view-data"
        data-users='@json($users ?? [])'
        data-clients='@json($clients ?? [])'
        data-phases='@json($phasesList)'
        style="display: none;">
    </div>
@endsection
