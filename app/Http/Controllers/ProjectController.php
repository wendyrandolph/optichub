<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Project;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

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

    $query = Project::query()
      ->where('tenant_id', $tenant->id)     // ⬅️ tenant isolation
      ->with('user');

    // Client role: only own projects
    if ($user && ($user->organization_type === 'client_org' || $user->organization_type === 'provider') && $user->role === 'client') {
      $query->where('client_user_id', $user->id);
    }

    $projects = $query->latest()->paginate(15);

    return view('projects.index', compact('tenant', 'projects'));
  }

  /** GET /{tenant}/projects/create */
  public function create(Tenant $tenant)
  {
    // pull only what you need
    $clients = Contact::where('tenant_id', $tenant->id)
      ->orderBy('lastName')       // adjust field name if yours is different
      ->get(['id', 'firstName', 'lastName']);

    return view('projects.create', [
      'tenant'  => $tenant,
      'clients' => $clients,
    ]);
  }

  /** POST /{tenant}/projects */
  public function store(Tenant $tenant, \App\Http\Requests\StoreProjectRequest $request)
  {
    $this->authorize('create', Project::class);

    $data = $request->validated();
    $data['tenant_id'] = $tenant->id;   // ⬅️ stamp tenant
    $data['user_id']   = Auth::id();    // creator/owner

    $project = Project::create($data);

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

    return view('projects.show', compact('tenant', 'project'));
  }

  /** GET /{tenant}/projects/{project}/edit */
  public function edit(Tenant $tenant, Project $project): View
  {
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('update', $project);

    return view('projects.edit', compact('tenant', 'project'));
  }

  /** PUT/PATCH /{tenant}/projects/{project} */
  public function update(Tenant $tenant, \App\Http\Requests\UpdateProjectRequest $request, Project $project)
  {
    abort_unless($project->tenant_id === $tenant->id, 404);

    $this->authorize('update', $project);

    $project->update($request->validated());

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
}
