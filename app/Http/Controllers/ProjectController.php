<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Project;
use App\Models\Contact;
use App\Models\ClientCompany;
use App\Models\Task;
use App\Models\ProjectConversation;
use App\Models\Phase;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\TimeEntry;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Carbon\Carbon;

class ProjectController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    // If you have a ProjectPolicy, you can enable this:
    // $this->authorizeResource(Project::class, 'project');
  }

  /** GET /{tenant}/projects */
  public function index(Tenant $tenant, Request $request): View
  {
    $user  = Auth::user();
    $q      = $request->string('q')->toString();
    $status = $request->string('status')->toString();
    $sort   = $request->string('sort', 'recent')->toString();
    $ownerId = $request->input('owner_id');
    $ownerCol = Schema::hasColumn('projects', 'project_manager_id') ? 'project_manager_id' : 'user_id';

    $query = Project::query()
      ->where('tenant_id', $tenant->id)     // ⬅️ tenant isolation
      ->with(['user', 'company', 'contact']);

    // Client role: only own projects
    if ($user && ($user->organization_type === 'client_org' || $user->organization_type === 'provider') && $user->role === 'client') {
      $query->where('client_user_id', $user->id);
    }

    // Search
    if ($q !== '') {
      $query->where(function ($sub) use ($q) {
        $sub->where('project_name', 'like', "%{$q}%")
          ->orWhere('description', 'like', "%{$q}%")
          ->orWhereHas('contact', function ($c) use ($q) {
            $c->where('firstName', 'like', "%{$q}%")
              ->orWhere('lastName', 'like', "%{$q}%")
              ->orWhere('company_name', 'like', "%{$q}%");
          })
          ->orWhereHas('company', function ($co) use ($q) {
            $co->where('company_name', 'like', "%{$q}%");
          });
      });
    }

    // Status filter
    if ($status !== '') {
      if ($status === 'open') {
        $query->whereIn('status', ['open', 'active']); // treat active as open for filters
      } else {
        $query->where('status', $status);
      }
    }

    if (is_numeric($ownerId)) {
      $query->where($ownerCol, (int) $ownerId);
    }

    // Sorting
    switch ($sort) {
      case 'name_asc':
        $query->orderBy('project_name', 'asc');
        break;
      case 'name_desc':
        $query->orderBy('project_name', 'desc');
        break;
      case 'start_desc':
        $query->orderBy('start_date', 'desc');
        break;
      default:
        $query->orderBy('updated_at', 'desc'); // recent
        break;
    }

    $projects = $query->paginate(15)->withQueryString();

    return view('projects.index', compact('tenant', 'projects'));
  }

  /** GET /{tenant}/projects/create */
  public function create(Tenant $tenant)
  {
    // pull only what you need
    $clients = Contact::where('tenant_id', $tenant->id)
      ->orderBy('lastName')       // adjust field name if yours is different
      ->get(['id', 'firstName', 'lastName']);
    $users = TeamMember::where('tenant_id', $tenant->id)
      ->with(['user:id,username,email'])
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName', 'email', 'user_id']);

    return view('projects.create', [
      'tenant'  => $tenant,
      'clients' => $clients,
      'users'   => $users,
      'currentUserId' => Auth::id(),
      'usesPhasesDefault' => (bool) ($tenant->default_uses_phases ?? false),
    ]);
  }

  /** POST /{tenant}/projects */
  public function store(Tenant $tenant, \App\Http\Requests\StoreProjectRequest $request)
  {
    $this->authorize('create', Project::class);

    $data = $request->validated();
    $data['tenant_id'] = $tenant->id;
    $data['user_id']   = Auth::id();
    $data['uses_phases'] = $request->boolean('uses_phases', false);
    $data['contact_id'] = $data['client_id'] ?? null;
    unset($data['client_id']);

    // ✅ Ensure every project has a color (fallback if none selected)
    $palette = [
      '#1F3C66',
      '#2E5D95',
      '#5C89B5',
      '#A3C1DD',
      '#EA7D51',
      '#F28B7D',
      '#68A7A1',
      '#9EB5A6',
      '#6C7A89',
      '#333333',
      '#D0CBE6',
      '#E3C89D',
      '#B6E3C1',
      '#F3D0D7',
      '#FFD7B5',
    ];

    if (empty($data['color'])) {
      // Option 1 (recommended): derive from project name so it feels "per project"
      $seed = (string) ($data['project_name'] ?? '');
      $idx  = abs(crc32($seed)) % count($palette);
      $data['color'] = $palette[$idx];

      // Option 2 (your current UI logic): based on client_id (uncomment if preferred)
      // $clientId = (int) ($data['client_id'] ?? 0);
      // $data['color'] = $palette[$clientId % count($palette)];
    }

    $project = Project::create($data);

    ActivityLog::record(
      $tenant->id,
      Auth::id(),
      $project,
      'project_created',
      $project->project_name ?? 'Project created'
    );

    return Redirect::route('tenant.projects.show', [
      'tenant'  => $tenant,
      'project' => $project,
    ])->with('success', 'Project created successfully.');
  }

  /** GET /{tenant}/projects/{project} */
  public function show(Tenant $tenant, Project $project): View
  {
    // Extra guard (scopeBindings should already enforce this)
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('view', $project);

    $conversation = ProjectConversation::firstOrCreate(
      ['project_id' => $project->id],
      [
        'tenant_id' => $tenant->id,
        'company_name' => $project->contact?->company_name,
        'public_token' => Str::random(40),
      ]
    );

    // Ensure a token exists even on existing rows
    if (empty($conversation->public_token)) {
      $conversation->forceFill(['public_token' => Str::random(40)])->save();
    }

    $messages = $conversation->messages()->orderBy('created_at')->get();

    $publicUrl = route('conversation.public', ['token' => $conversation->public_token]);

    $tasks = Task::where('tenant_id', $tenant->id)
      ->where('project_id', $project->id)
      ->with([
        'user:id,first_name,last_name,username,display_name',
        'client:id,firstName,lastName',
        'teamMember:id,firstName,lastName',
      ])
      ->get();

    $taskIds = $tasks->pluck('id');

    // Base time query: time entries linked to this project directly OR via task belonging to this project
    $timeQuery = TimeEntry::where('tenant_id', $tenant->id)
      ->where(function ($q) use ($project, $taskIds) {
        $q->where('project_id', $project->id);
        if ($taskIds->isNotEmpty()) {
          $q->orWhereIn('task_id', $taskIds);
        }
      });

    // Hours by task from time entries (source of truth)
    $timeSumsByTask = (clone $timeQuery)
      ->whereNotNull('task_id')
      ->selectRaw('task_id, SUM(hours) as total_hours')
      ->groupBy('task_id')
      ->pluck('total_hours', 'task_id');

    $projectTotalHours = (clone $timeQuery)->sum('hours');
    $unassignedHours = (clone $timeQuery)
      ->whereNull('task_id')
      ->sum('hours');

    $requiresApproval = $tasks->contains(fn($t) => (bool) ($t->requires_approval ?? false));

    // Simple task stats for header cards
    $totalTasks = $tasks->count();
    $completedTasks = $tasks->where('status', 'completed')->count();
    $blockedTasks = $tasks->where('status', 'blocked')->count();
    $overdueTasks = $tasks->filter(function (Task $t) {
      return $t->due_date && $t->status !== 'completed' && \Carbon\Carbon::parse($t->due_date)->isPast();
    })->count();
    $openTasks = $totalTasks ? ($totalTasks - $completedTasks - $tasks->where('status', 'archived')->count()) : 0;
    $progress = $totalTasks ? round(($completedTasks / $totalTasks) * 100) : 0;

    // Build lookup maps for assignees (users, team members, clients/contacts)
    $tenantUsers = User::where('tenant_id', $tenant->id)
      ->get(['id', 'first_name', 'last_name', 'username', 'email']);
    $userMap = $tenantUsers->keyBy(fn($u) => (int) $u->id);

    $tenantTeamMembers = TeamMember::where('tenant_id', $tenant->id)
      ->get(['id', 'user_id', 'firstName', 'lastName']);
    $teamMap = $tenantTeamMembers->keyBy(fn($tm) => (int) $tm->id);
    $teamByUserMap = $tenantTeamMembers->keyBy(fn($tm) => (int) $tm->user_id);

    $clientIds = $tasks->where('assign_type', 'client')
      ->pluck('assign_id')
      ->merge($tasks->pluck('contact_id'))
      ->map(fn($v) => (int) $v)
      ->filter()
      ->unique();

    $clientMap = $clientIds->isNotEmpty()
      ? Contact::whereIn('id', $clientIds)->get()->keyBy(fn($c) => (int) $c->id)
      : collect();

    $tasksForView = $tasks->map(function (Task $t) use ($userMap, $teamMap, $teamByUserMap, $clientMap) {
      $worked = (int) ($t->worked_seconds ?? 0);
      if ($t->timer_started_at) {
        $worked += now()->diffInSeconds($t->timer_started_at);
      }
      $hours = round($worked / 3600, 2);
      $assignLabel = 'Unassigned';
      $assignType = $t->assign_type ?? (isset($t->contact_id) && $t->contact_id ? 'client' : 'admin');
      $assignId = (int) $t->assign_id;

      // Resolve admin/internal assignees
      $resolveAdminName = function ($entity) {
        if (!$entity) {
          return null;
        }

        $display = $entity->display_name ?? null;
        $first   = $entity->first_name ?? $entity->firstName ?? null;
        $last    = $entity->last_name ?? $entity->lastName ?? null;
        $username = $entity->username ?? null;

        return $display
          ?: trim(($first ?? '') . ' ' . ($last ?? ''))
          ?: $username
          ?: null;
      };

      if ($assignType === 'client') {
        $client = $t->client
          ?? ($assignId && $clientMap->has($assignId) ? $clientMap[$assignId] : null)
          ?? ($t->contact_id && $clientMap->has($t->contact_id) ? $clientMap[$t->contact_id] : null);

        if ($client) {
          $assignLabel = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''))
            ?: ($client->client_name ?? 'Client');
        } else {
          $assignLabel = 'Client';
        }
      } else {
        $adminName = null;

        // Prefer Users table resolution (matches edit modal dropdown)
        // 1) assign_id -> users.id
        $adminName = $adminName ?: ($assignId ? $resolveAdminName($userMap->get($assignId)) : null);
        // 2) user_id -> users.id
        $adminName = $adminName ?: ($t->user_id ? $resolveAdminName($userMap->get((int) $t->user_id)) : null);
        // 3) assign_id -> team_members.user_id
        $adminName = $adminName ?: ($assignId ? $resolveAdminName($teamByUserMap->get($assignId)) : null);
        // 4) user_id -> team_members.user_id
        $adminName = $adminName ?: ($t->user_id ? $resolveAdminName($teamByUserMap->get((int) $t->user_id)) : null);
        // 5) assign_id -> team_members.id
        $adminName = $adminName ?: ($assignId ? $resolveAdminName($teamMap->get($assignId)) : null);
        // 6) loaded relations
        $adminName = $adminName ?: $resolveAdminName($t->user);
        $adminName = $adminName ?: $resolveAdminName($t->teamMember);

        $assignLabel = $adminName ?: ($assignId ? 'Admin #' . $assignId : 'Unassigned');
      }

      return [
        'id' => $t->id,
        'title' => $t->title,
        'status' => $t->status,
        'due_date' => optional($t->due_date)?->toDateString(),
        'started_at' => optional($t->started_at)?->toDateTimeString(),
        'completed_at' => optional($t->completed_at)?->toDateTimeString(),
        'hours' => (float) ($timeSumsByTask[$t->id] ?? $t->hours_spent ?? $hours ?? 0),
        'assign_type' => $assignType,
        'assign_name' => $assignLabel,
        'requires_approval' => (bool) ($t->requires_approval ?? false),
        'approval_status' => $t->approval_status,
        'approval_note' => $t->approval_note,
      ];
    });

    $totalHours = (float) ($projectTotalHours ?: $tasksForView->sum('hours'));
    $pendingApprovals = $tasks->filter(function (Task $t) {
      if (! $t->requires_approval) {
        return false;
      }
      if (empty($t->approval_status)) {
        return true;
      }
      return in_array($t->approval_status, ['pending'], true);
    })->count();

    return view('projects.show', compact(
      'tenant',
      'project',
      'conversation',
      'messages',
      'publicUrl',
      'tasksForView',
      'totalHours',
      'openTasks',
      'overdueTasks',
      'blockedTasks',
      'progress',
      'requiresApproval',
      'pendingApprovals'
    , 'unassignedHours'));
  }

  /** GET /{tenant}/projects/{project}/edit */
  public function edit(Tenant $tenant, Project $project): View
  {
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('update', $project);

    $users = User::where('tenant_id', $tenant->id)
      ->orderBy('first_name')
      ->get(['id', 'first_name', 'last_name', 'username', 'email']);

    // Ensure the current project owner appears in the list even if not returned above
    if ($project->user_id && !$users->contains('id', $project->user_id)) {
      if ($extra = User::find($project->user_id)) {
        $users->push($extra);
      }
    }

    $clients = Contact::where('tenant_id', $tenant->id)
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName']);

    $clientCompanies = ClientCompany::where('tenant_id', $tenant->id)
      ->orderBy('company_name')
      ->get(['id', 'company_name']);

    $projectPhases = Phase::where('tenant_id', $tenant->id)
      ->where('project_id', $project->id)
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get(['id', 'name']);

    $usesPhasesDefault = (bool) ($tenant->default_uses_phases ?? false);

    return view('projects.edit', compact(
      'tenant',
      'project',
      'users',
      'clients',
      'clientCompanies',
      'projectPhases',
      'usesPhasesDefault'
    ));
  }

  /** PUT/PATCH /{tenant}/projects/{project} */
  public function update(Tenant $tenant, \App\Http\Requests\UpdateProjectRequest $request, Project $project)
  {
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('update', $project);

    $data = $request->validated();
    $data['uses_phases'] = $request->boolean('uses_phases', false);
    $data['contact_id'] = $data['client_id'] ?? null;
    unset($data['client_id']);

    $project->update($data);

    // If phases enabled, sync project phases from submitted names (max 5, unique)
    if ($data['uses_phases']) {
      $phaseNames = collect($request->input('phases', []))
        ->slice(0, 5)
        ->map(fn($n) => trim((string) $n))
        ->filter()
        ->unique()
        ->values()
        ->take(5);

      Phase::where('tenant_id', $tenant->id)
        ->where('project_id', $project->id)
        ->delete();

      foreach ($phaseNames as $idx => $name) {
        Phase::create([
          'tenant_id' => $tenant->id,
          'project_id' => $project->id,
          'name' => $name,
          'sort_order' => $idx + 1,
        ]);
      }
    }

    return Redirect::route('tenant.projects.show', [
      'tenant'  => $tenant,
      'project' => $project,
    ])->with('success', 'Project updated successfully.');
  }

  /** DELETE /{tenant}/projects/{project} */
  public function destroy(Tenant $tenant, Project $project)
  {
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('delete', $project);

    $project->delete();

    return Redirect::route('tenant.projects.index', [
      'tenant' => $tenant,
    ])->with('success', 'Project deleted successfully.');
  }

  /** POST /{tenant}/projects/{project}/complete */
  public function complete(Tenant $tenant, Project $project)
  {
    abort_unless($project->tenant_id === $tenant->id, 404);
    $this->authorize('update', $project);

    $project->update([
      'status' => 'closed',
      'end_date' => now()->toDateString(),
    ]);

    return Redirect::route('tenant.projects.show', [
      'tenant'  => $tenant,
      'project' => $project,
    ])->with('success_message', 'Project marked as completed.');
  }

  /** POST /{tenant}/projects/{project}/reopen */
  public function reopen(Tenant $tenant, Project $project)
  {
    abort_unless($project->tenant_id === $tenant->id, 404);
    $this->authorize('update', $project);

    $project->update([
      'status' => 'open',
      'end_date' => null,
    ]);

    return Redirect::route('tenant.projects.show', [
      'tenant'  => $tenant,
      'project' => $project,
    ])->with('success_message', 'Project reopened.');
  }
}
