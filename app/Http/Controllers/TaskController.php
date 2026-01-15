<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\{Task, Contact, Project, Phase, TeamMember, Tenant, User, ProjectConversation, ProjectMessage, TaskComment, TimeEntry};
use App\Models\Activity;
use App\Models\ActivityLog;


class TaskController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  /** GET /{tenant}/tasks */
  // app/Http/Controllers/TaskController.php


  public function index()
  {
    $tenantParam = request()->route('tenant');
    $tenantId = $tenantParam instanceof Tenant ? $tenantParam->getKey() : (int) $tenantParam;
    $statusFilter = request('status', '');
    $assigneeId = request('assignee_id');

    $statusFilterMap = [
      'open' => ['todo', 'open'],
      'in_progress' => ['working', 'in_progress', 'doing'],
      'completed' => ['done', 'complete', 'completed'],
      'wip' => ['todo', 'open', 'working', 'in_progress', 'doing'],
    ];
    $filterStatuses = $statusFilterMap[$statusFilter] ?? null;

    // Pull tasks for THIS tenant (ignore global scopes while debugging)
    $tasks = Task::withoutGlobalScopes()
      ->where('tenant_id', $tenantId)
      ->when(is_numeric($assigneeId), fn($q) => $q->where('user_id', (int) $assigneeId))
      ->when($filterStatuses, fn($q) => $q->whereIn('status', $filterStatuses))
      ->with(['project:id,project_name,color', 'phase:id,name', 'client:id,firstName,lastName,first_name,last_name'])
      ->get();

    // Normalize statuses to the columns your Blade uses
    $statusMap = [
      'todo'        => 'open',
      'open'        => 'open',
      'working'     => 'in_progress',
      'in_progress' => 'in_progress',
      'doing'       => 'in_progress',
      'done'        => 'completed',
      'complete'    => 'completed',
      'completed'   => 'completed',
    ];

    $tasksByStatus = ['open' => [], 'in_progress' => [], 'completed' => []];

    foreach ($tasks as $t) {
      $normalized = $statusMap[strtolower($t->status ?? '')] ?? 'open';
      $phaseId = $t->phase_id ?? 0;

      if (!isset($tasksByStatus[$normalized][$phaseId])) {
        $tasksByStatus[$normalized][$phaseId] = [
          'phase_name' => optional($t->phase)->name ?? 'No Phase',
          'tasks'      => [],
        ];
      }

      $tasksByStatus[$normalized][$phaseId]['tasks'][] = [
        'id'              => $t->id,
        'title'           => $t->title,
        'description'     => $t->description,
        'due_date'        => optional($t->due_date)?->toDateString(),
        'started_at'      => optional($t->started_at)?->toDateTimeString(),
        'completed_at'    => optional($t->completed_at)?->toDateTimeString(),
        'timer_started_at'=> optional($t->timer_started_at)?->toDateTimeString(),
        'worked_seconds'  => $t->worked_seconds,
        'hours_spent'     => $t->hours_spent,
        'assign_type'     => $t->assign_type ?? null,
        'assign_id'       => $t->assign_id ?? $t->user_id ?? null,
        'project_id'      => $t->project_id,
        'phase_id'        => $t->phase_id,
        'contact_id'      => $t->contact_id,
        'project_name'    => optional($t->project)->project_name ?? '',
        'phase_name'      => optional($t->phase)->name ?? '',
        'client_name'     => trim(
          ($t->client?->firstName ?? $t->client?->first_name ?? '') . ' ' .
          ($t->client?->lastName ?? $t->client?->last_name ?? '')
        ),
        'project_color'   => $t->project->color ?? '#94a3b8',
        'card_bg_color'   => '#fff',
        'card_text_color' => '#111827',
        'requires_approval' => (bool) ($t->requires_approval ?? false),
      ];
    }

    // Legend data
    $projects = Project::withoutGlobalScopes()
      ->where('tenant_id', $tenantId)
      ->get(['id', 'project_name', 'color']);

    $projectColorMap = $projects->mapWithKeys(fn($p) => [
      $p->id => ['name' => $p->project_name, 'color' => $p->color ?: '#94a3b8']
    ])->toArray();

    // Debug counts we can show in Blade to ensure the handoff works
    $debugCounts = [
      'open'        => array_sum(array_map(fn($g) => count($g['tasks'] ?? []), $tasksByStatus['open'] ?? [])),
      'in_progress' => array_sum(array_map(fn($g) => count($g['tasks'] ?? []), $tasksByStatus['in_progress'] ?? [])),
      'completed'   => array_sum(array_map(fn($g) => count($g['tasks'] ?? []), $tasksByStatus['completed'] ?? [])),
      'rawTotal'    => $tasks->count(),
      'tenantId'    => $tenantId,
    ];

    // Dropdown data (only if your modal needs them)
    $users   = User::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name', 'username', 'role']);
    $clients = Contact::where('tenant_id', $tenantId)
      ->orderBy('firstName')
      ->get(['id', 'firstName as client_name', 'lastName']);
    // Limit phases to tenant-level templates (project_id is null), max 5, unique by name
    $phases = Phase::where('tenant_id', $tenantId)
      ->whereNull('project_id')
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get(['id', 'name', 'project_id'])
      ->unique('name')
      ->take(5)
      ->values();

    if ($phases->isEmpty()) {
      $defaultPhaseNames = ['Planning', 'In Progress', 'Design Review', 'Client Feedback', 'Done'];
      $phases = collect($defaultPhaseNames)->map(function ($name, $idx) {
        return (object) ['id' => $idx + 1, 'name' => $name, 'project_id' => null];
      });
    }

    return view('tasks.index', compact(
      'projectColorMap',
      'tasksByStatus',
      'users',
      'clients',
      'projects',
      'phases',
      'debugCounts'
    ));
  }

  // POST /{tenant}/tasks/{task}/start
  public function start(Tenant $tenant, Task $task)
  {
    if ($task->tenant_id !== $tenant->id) {
      abort(404);
    }

    $user = Auth::user();
    $canUpdate = $user && $user->can('update', $task);
    $isAssignee = $user && $task->assign_type === 'admin' && (int) $task->assign_id === (int) $user->id;
    if (! $canUpdate && ! $isAssignee) {
      abort(403);
    }

    $task->status = 'in_progress';
    if (! $task->started_at) {
      $task->started_at = now();
    }
    if (! $task->timer_started_at) {
      $task->timer_started_at = now();
    }
    $task->save();

    return back()->with('success_message', 'Task moved to Working On.');
  }

  protected function accumulateTime(Task $task): void
  {
    if (! $task->timer_started_at) {
      return;
    }

    $delta = max(0, now()->diffInSeconds($task->timer_started_at));
    $worked = (int) ($task->worked_seconds ?? 0);
    $task->worked_seconds = max(0, $worked + $delta);
    $task->timer_started_at = null;
    $task->hours_spent = round(($task->worked_seconds ?? 0) / 3600, 2);
  }

  // POST /{tenant}/tasks/{task}/pause
  public function pause(Tenant $tenant, Task $task)
  {
    if ($task->tenant_id !== $tenant->id) {
      abort(404);
    }

    $user = Auth::user();
    $canUpdate = $user && $user->can('update', $task);
    $isAssignee = $user && $task->assign_type === 'admin' && (int) $task->assign_id === (int) $user->id;
    if (! $canUpdate && ! $isAssignee) {
      abort(403);
    }

    $this->accumulateTime($task);
    $task->save();

    return back()->with('success_message', 'Task paused.');
  }

  // POST /{tenant}/tasks/{task}/resume
  public function resume(Tenant $tenant, Task $task)
  {
    if ($task->tenant_id !== $tenant->id) {
      abort(404);
    }

    $user = Auth::user();
    $canUpdate = $user && $user->can('update', $task);
    $isAssignee = $user && $task->assign_type === 'admin' && (int) $task->assign_id === (int) $user->id;
    if (! $canUpdate && ! $isAssignee) {
      abort(403);
    }

    if (! $task->started_at) {
      $task->started_at = now();
    }
    $task->timer_started_at = now();
    $task->status = 'in_progress';
    $task->save();

    return back()->with('success_message', 'Task resumed.');
  }

  /** POST /{tenant}/tasks/{task}/approve */
  public function approveTask(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);
    if (! $task->requires_approval) {
      return back()->with('error_message', 'This task does not require approval.');
    }

    $user = $request->user();
    abort_unless($user && $user->can('update', $task), 403);

    $note = $request->string('note')->toString();

    $task->forceFill([
      'approval_status' => 'approved',
      'approval_note' => $note ?: null,
      'approval_decided_at' => now(),
      'approval_decided_by' => $user->id,
    ])->save();

    $this->emitApprovalMessage($tenant, $task, 'approved', $note, $user);

    return back()->with('success_message', 'Task approved.');
  }

  /** POST /{tenant}/tasks/{task}/request-changes */
  public function requestChanges(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);
    if (! $task->requires_approval) {
      return back()->with('error_message', 'This task does not require approval.');
    }

    $user = $request->user();
    abort_unless($user && $user->can('update', $task), 403);

    $data = $request->validate([
      'note' => ['nullable', 'string', 'max:2000'],
    ]);

    $task->forceFill([
      'approval_status' => 'changes_requested',
      'approval_note' => $data['note'] ?? null,
      'approval_decided_at' => now(),
      'approval_decided_by' => $user->id,
    ])->save();

    $this->emitApprovalMessage($tenant, $task, 'changes_requested', $data['note'] ?? null, $user);

    return back()->with('success_message', 'Changes requested on task.');
  }

  // POST /{tenant}/tasks/{task}/complete
  public function complete(Tenant $tenant, Task $task)
  {
    if ($task->tenant_id !== $tenant->id) {
      abort(404);
    }

    $user = Auth::user();
    $canUpdate = $user && $user->can('update', $task);
    $isAssignee = $user && $task->assign_type === 'admin' && (int) $task->assign_id === (int) $user->id;
    if (! $canUpdate && ! $isAssignee) {
      abort(403);
    }

    $this->accumulateTime($task);
    $task->status = 'completed';
    $task->completed_at = now();
    $task->hours_spent = round(($task->worked_seconds ?? 0) / 3600, 2);
    $task->save();

    return back()->with('success_message', 'Task marked as completed.');
  }

  // POST /{tenant}/tasks/{task}/archive
  public function archive(Tenant $tenant, Task $task)
  {
    if ($task->tenant_id !== $tenant->id) {
      abort(404);
    }

    $user = Auth::user();
    $canUpdate = $user && $user->can('update', $task);
    $isAssignee = $user && $task->assign_type === 'admin' && (int) $task->assign_id === (int) $user->id;
    if (! $canUpdate && ! $isAssignee) {
      abort(403);
    }

    $task->status = 'archived';
    $task->archived_at = now();
    $task->save();

    return back()->with('success_message', 'Task archived.');
  }


  /** GET /{tenant}/tasks/create */
  public function create(Tenant $tenant)
  {
    $adminUsers  = User::where('tenant_id', $tenant->id)
      ->whereIn('role', ['admin', 'super_admin', 'superadmin', 'provider'])
      ->get(['id', 'first_name', 'last_name', 'username', 'tenant_id']);

    $clientUsers = Contact::where('tenant_id', $tenant->id)
      ->orderBy('firstName')
      ->get(['id', 'firstName as client_name', 'lastName', 'tenant_id']);

    $assignees = User::where('tenant_id', $tenant->id)
      ->orderBy('first_name')
      ->orderBy('last_name')
      ->get(['id', 'first_name', 'last_name', 'username', 'email']);

    $projects = Project::where('tenant_id', $tenant->id)
      ->with('client')
      ->get(['id', 'project_name', 'contact_id', 'tenant_id']);

    // Use tenant-level phase template (project_id null) if defined
    $phases = Phase::where('tenant_id', $tenant->id)
      ->whereNull('project_id')
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get(['id', 'name', 'project_id']);

    return view('tasks.create', compact('tenant', 'adminUsers', 'clientUsers', 'projects', 'phases', 'assignees'));
  }

  /** POST /{tenant}/tasks */
  public function store(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'title'       => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'due_date'    => ['nullable', 'date'],
      'status'      => ['nullable', 'string', 'max:32'],   // your defaults handle 'todo'
      'priority'    => ['nullable', 'string', 'max:32'],   // default 'medium'
      'project_id'  => ['nullable', 'integer', 'exists:projects,id'],
      'contact_id'   => ['nullable', 'integer', 'exists:clients,id'],
      'user_id'     => ['nullable', 'integer', 'exists:users,id'],
      'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
      'assign_type' => ['nullable', 'string', 'in:admin,client'],
      'assign_id'   => ['nullable', 'integer'],
      'requires_approval' => ['nullable', 'boolean'],
    ]);

    if (empty($data['user_id']) && !empty($data['assignee_id'])) {
      $data['user_id'] = $data['assignee_id'];
    }
    unset($data['assignee_id']);

    $data['tenant_id'] = $tenant->id;
    $data['requires_approval'] = $request->boolean('requires_approval', false);

    if (!empty($data['assign_type']) && $data['assign_type'] === 'admin' && !empty($data['assign_id']) && empty($data['user_id'])) {
      $data['user_id'] = $data['assign_id'];
    }

    if (empty($data['assign_type']) && !empty($data['user_id'])) {
      $data['assign_type'] = 'admin';
      $data['assign_id'] = $data['user_id'];
    }

    if ($data['requires_approval']) {
      $data['approval_status'] = 'pending';
      $data['approval_decided_at'] = null;
      $data['approval_decided_by'] = null;
    }

    if (empty($data['status'])) {
      $data['status'] = 'todo';
    }

    $task = Task::create($data);

    ActivityLog::record(
      $tenant->id,
      Auth::id(),
      $task,
      'task_created',
      $task->title ?? 'Task created'
    );

    return Redirect::route('tenant.tasks.show', ['tenant' => $tenant, 'task' => $task])
      ->with('status', 'Task created');
  }

  /** GET /{tenant}/tasks/{task} */
  public function show(Tenant $tenant, Task $task)
  {
    // Ensure task belongs to this tenant (scoped bindings should handle, but double-guard is fine)
    abort_unless($task->tenant_id === $tenant->id, 404);

    $task->load(['project', 'client', 'user', 'comments.user']);

    $conversation = null;
    $messages = collect();

    if ($task->project) {
      $conversation = ProjectConversation::firstOrCreate(
        ['project_id' => $task->project->id],
        [
          'tenant_id' => $tenant->id,
          'company_name' => $task->project->contact?->company_name,
        ]
      );

      $messages = $conversation->messages()->orderBy('created_at')->get();
    }

    return view('tasks.show', compact('tenant', 'task', 'conversation', 'messages'));
  }

  /** GET /{tenant}/tasks/{task}/edit */
  public function edit(Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    return view('tasks.edit', [
      'tenant'  => $tenant,
      'task'    => $task,
      'projects' => Project::where('tenant_id', $tenant->id)->orderBy('project_name')->get(['id', 'project_name']),
      'clients' => Contact::where('tenant_id', $tenant->id)
        ->orderBy('firstName')
        ->get(['id', 'firstName', 'lastName']),
      'users' => User::where('tenant_id', $tenant->id)
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get(['id', 'first_name', 'last_name', 'username', 'email']),
      'phases' => Phase::where('tenant_id', $tenant->id)
        ->whereNull('project_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'project_id']),
    ]);
  }

  /** PUT/PATCH /{tenant}/tasks/{task} */
  public function update(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    $phaseRule = Schema::hasTable('project_phases')
      ? ['nullable', 'integer', 'exists:project_phases,id']
      : ['nullable', 'integer'];
    $projectPhasesRule = Schema::hasTable('project_phases')
      ? ['array']
      : ['array'];

    $data = $request->validate([
      'title'       => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'due_date'    => ['nullable', 'date'],
      'status'      => ['nullable', 'string', 'max:32'],  // e.g. 'todo','in_progress','completed','archived'
      'priority'    => ['nullable', 'string', 'max:32'],
      'project_id'  => ['nullable', 'integer', 'exists:projects,id'],
      'contact_id'   => ['nullable', 'integer', 'exists:clients,id'],
      'user_id'     => ['nullable', 'integer', 'exists:users,id'],
      'phase_id'    => $phaseRule,
      'assign_type' => ['nullable', 'string', 'in:admin,client'],
      'assign_id'   => ['nullable', 'integer'],
      'hours_spent' => ['nullable', 'numeric', 'min:0', 'max:100000'],
      'requires_approval' => ['nullable', 'boolean'],
      'phases' => $projectPhasesRule,
    ]);

    if (empty($data['status'])) {
      $data['status'] = $task->status; // preserve current status if none provided
    }

    $data['requires_approval'] = $request->boolean('requires_approval', false);

    if (!empty($data['assign_type']) && $data['assign_type'] === 'admin' && !empty($data['assign_id']) && empty($data['user_id'])) {
      $data['user_id'] = $data['assign_id'];
    }

    if (empty($data['assign_type']) && !empty($data['user_id'])) {
      $data['assign_type'] = 'admin';
      $data['assign_id'] = $data['user_id'];
    }

    if ($data['requires_approval'] && empty($task->approval_status)) {
      $data['approval_status'] = 'pending';
      $data['approval_decided_at'] = null;
      $data['approval_decided_by'] = null;
      $data['approval_note'] = null;
    }

    if (! $data['requires_approval']) {
      $data['approval_status'] = null;
      $data['approval_decided_at'] = null;
      $data['approval_decided_by'] = null;
      $data['approval_note'] = null;
    }

    $task->update($data);

    // If hours were provided, log them as time entries (source of truth)
    if ($request->filled('hours_spent')) {
      $desiredTotal = (float) $request->input('hours_spent');
      $currentTotal = (float) TimeEntry::where('tenant_id', $tenant->id)
        ->where('task_id', $task->id)
        ->sum('hours');

      $delta = round($desiredTotal - $currentTotal, 2);
      if ($delta > 0.0001) {
        TimeEntry::create([
          'tenant_id' => $tenant->id,
          'user_id' => auth()->id(),
          'project_id' => $task->project_id,
          'task_id' => $task->id,
          'date' => now()->toDateString(),
          'hours' => $delta,
          'billable' => true,
          'notes' => 'Logged via task editor',
        ]);
      }

      // Sync hours_spent to the actual total from time entries
      $newTotal = TimeEntry::where('tenant_id', $tenant->id)
        ->where('task_id', $task->id)
        ->sum('hours');
      $task->update(['hours_spent' => $newTotal]);
    }

    activity()
      ->useLog('task')
      ->performedOn($task)
      ->causedBy(Auth::user())
      ->withProperties([
        'title' => $task->title,
        'status' => $task->status,
        'assign_type' => $task->assign_type,
        'assign_id' => $task->assign_id,
      ])
      ->log('task_updated');

    $return = $request->filled('return_url') ? $request->string('return_url')->toString() : null;

    if ($return) {
      return Redirect::to($return)->with('success_message', 'Task updated successfully.');
    }

    return Redirect::route('tenant.tasks.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Task updated successfully.');
  }

  /** DELETE /{tenant}/tasks/{task} */
  public function destroy(Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    $user = auth()->user();
    $role = strtolower((string) ($user?->role ?? ''));
    $isAdmin = in_array($role, ['admin', 'super_admin', 'superadmin', 'provider', 'owner'], true);
    $isPlatformOwner = $user?->isPlatformOwner() ?? false;
    $isCreator = $user && (int) $task->user_id === (int) $user->id;

    abort_unless($isAdmin || $isPlatformOwner || $isCreator, 403);

    $task->delete();

    return Redirect::route('tenant.tasks.index', ['tenant' => $tenant])
      ->with('status', 'Task deleted');
  }

  /** POST /{tenant}/tasks/{task}/status */
  public function updateStatus(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    $request->validate([
      'status' => ['required', 'string', 'max:32'],
    ]);

    $task->update(['status' => $request->string('status')]);

    return response()->json(['success' => true, 'data' => ['id' => $task->id, 'status' => $task->status]]);
  }

  /** POST /{tenant}/tasks/{task}/comments */
  public function addComment(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    $data = $request->validate([
      'comment' => ['required', 'string', 'max:2000'],
    ]);

    TaskComment::create([
      'task_id' => $task->id,
      'user_id' => $request->user()->id,
      'comment' => $data['comment'],
    ]);

    return Redirect::route('tenant.tasks.show', ['tenant' => $tenant, 'task' => $task])
      ->with('status', 'Comment added');
  }

  protected function emitApprovalMessage(Tenant $tenant, Task $task, string $status, ?string $note, $user = null): void
  {
    if (! $task->project_id) {
      return;
    }

    $conversation = ProjectConversation::firstOrCreate(
      ['project_id' => $task->project_id],
      [
        'tenant_id' => $tenant->id,
        'company_name' => optional($task->project)->contact?->company_name,
        'public_token' => \Str::random(40),
      ]
    );

    if (empty($conversation->public_token)) {
      $conversation->forceFill(['public_token' => \Str::random(40)])->save();
    }

    $actorName = $user?->name ?? ($user?->display_name ?? 'System');
    $statusLabel = match ($status) {
      'approved' => 'approved',
      'changes_requested' => 'requested changes',
      default => $status,
    };

    $body = "Task \"{$task->title}\" {$statusLabel} by {$actorName}.";
    if ($note) {
      $body .= "\n\nNote: {$note}";
    }

    ProjectMessage::create([
      'conversation_id' => $conversation->id,
      'sender_type' => 'system',
      'sender_id' => $user?->id,
      'body' => $body,
    ]);
  }
}
