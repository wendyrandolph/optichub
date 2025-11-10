<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\{Task, Contact, Project, Phase, TeamMember, Tenant, User};


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

    // Pull tasks for THIS tenant (ignore global scopes while debugging)
    $tasks = Task::withoutGlobalScopes()
      ->where('tenant_id', $tenantId)
      ->with(['project:id,project_name,color', 'phase:id,name'])
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
        'assign_type'     => $t->assign_type ?? null,
        'assign_id'       => $t->assign_id ?? null,
        'project_id'      => $t->project_id,
        'phase_id'        => $t->phase_id,
        'project_name'    => optional($t->project)->project_name ?? '',
        'phase_name'      => optional($t->phase)->name ?? '',
        'project_color'   => $t->project->color ?? '#94a3b8',
        'card_bg_color'   => '#fff',
        'card_text_color' => '#111827',
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
    $users   = TeamMember::withoutGlobalScopes()->where('tenant_id', $tenantId)->get(['id', 'firstName as username', 'role']);
    $clients = TeamMember::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('role', 'client')->get(['id', 'firstName as client_name']);
    $phases  = Phase::withoutGlobalScopes()->whereIn('project_id', $projects->pluck('id'))->get(['id', 'name', 'project_id']);

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


  /** GET /{tenant}/tasks/create */
  public function create(Tenant $tenant)
  {
    $adminUsers  = User::whereIn('role', ['admin', 'super_admin', 'superadmin', 'provider'])->get(['id', 'first_name', 'last_name', 'username']);
    $clientUsers = Contact::all(['id', 'firstName', 'lastName']);
    $projects    = Project::with('client')->get(['id', 'project_name', 'client_id']);
    $phases      = Phase::all(['id', 'name', 'project_id']);


    return view('tasks.create', compact('adminUsers', 'clientUsers', 'projects', 'phases'));
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
      'client_id'   => ['nullable', 'integer', 'exists:clients,id'],
      'user_id'     => ['nullable', 'integer', 'exists:users,id'],
    ]);

    $data['tenant_id'] = $tenant->id;
    $task = Task::create($data);

    return Redirect::route('tenant.tasks.show', ['tenant' => $tenant, 'task' => $task])
      ->with('status', 'Task created');
  }

  /** GET /{tenant}/tasks/{task} */
  public function show(Tenant $tenant, Task $task)
  {
    // Ensure task belongs to this tenant (scoped bindings should handle, but double-guard is fine)
    abort_unless($task->tenant_id === $tenant->id, 404);

    $task->load(['project', 'client', 'user', 'comments.user']);
    return view('tasks.show', compact('tenant', 'task'));
  }

  /** GET /{tenant}/tasks/{task}/edit */
  public function edit(Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    return view('tasks.edit', [
      'tenant'  => $tenant,
      'task'    => $task,
      'projects' => Project::where('tenant_id', $tenant->id)->orderBy('project_name')->get(['id', 'project_name']),
      'clients' => Contact::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
    ]);
  }

  /** PUT/PATCH /{tenant}/tasks/{task} */
  public function update(Request $request, Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

    $data = $request->validate([
      'title'       => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'due_date'    => ['nullable', 'date'],
      'status'      => ['required', 'string', 'max:32'],  // e.g. 'todo','in_progress','completed','archived'
      'priority'    => ['nullable', 'string', 'max:32'],
      'project_id'  => ['nullable', 'integer', 'exists:projects,id'],
      'client_id'   => ['nullable', 'integer', 'exists:clients,id'],
      'user_id'     => ['nullable', 'integer', 'exists:users,id'],
    ]);

    $task->update($data);

    return Redirect::route('tenant.tasks.show', ['tenant' => $tenant, 'task' => $task])
      ->with('status', 'Task updated');
  }

  /** DELETE /{tenant}/tasks/{task} */
  public function destroy(Tenant $tenant, Task $task)
  {
    abort_unless($task->tenant_id === $tenant->id, 404);

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
}
