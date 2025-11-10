<?php

namespace App\Http\Controllers;

use App\Mail\ClientCredentialsMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;

class ClientController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  /** GET /{tenant}/contacts */
  public function index(Tenant $tenant)
  {
    $this->authorize('viewAny', Client::class);

    $clients = Client::where('tenant_id', $tenant->id)
      ->with('userAccount')
      ->latest()
      ->paginate(20);

    return view('contacts.index', [
      'tenant'  => $tenant->id,
      'clients' => $clients,
    ]);
  }

  /** GET /{tenant}/contacts/create */
  public function create(Tenant $tenant)
  {
    $this->authorize('create', Client::class);

    return view('contacts.create', [
      'tenant' => $tenant->id,
    ]);
  }

  /** POST /{tenant}/contacts */
  public function store(Request $request, Tenant $tenant)
  {
    $this->authorize('create', Client::class);

    $data = $request->validate([
      'firstName' => 'required|string|max:255',
      'lastName'  => 'required|string|max:255',
      'email'     => [
        'required',
        'email',
        Rule::unique('clients', 'email')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'phone'     => 'nullable|string|max:50',
      'status'    => ['required', Rule::in(['active', 'inactive'])],
      'notes'     => 'nullable|string',
    ]);

    $data['tenant_id'] = $tenant->id;
    $data['status']    = $data['status'] ?? 'active';

    $client = Client::create($data);

    Activity::useLog('client')
      ->performedOn($client)
      ->causedBy(Auth::user())
      ->log("client_created: {$client->firstName} {$client->lastName}");

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact created successfully!');
  }

  /** GET /{tenant}/contacts/{contact} */
  public function show(Tenant $tenant, Client $contact)
  {
    $this->authorize('view', $contact);

    $contact->load(['userAccount', 'projects.phases', 'projects.agreements', 'projects.payments']);

    return view('contacts.view', [
      'tenant'  => $tenant->id,
      'client'  => $contact,
    ]);
  }

  /** GET /{tenant}/contacts/{contact}/edit */
  public function edit(Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    return view('contacts.edit', [
      'tenant' => $tenant->id,
      'client' => $contact,
    ]);
  }

  /** PUT/PATCH /{tenant}/contacts/{contact} */
  public function update(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $data = $request->validate([
      'firstName' => 'required|string|max:255',
      'lastName'  => 'required|string|max:255',
      'email'     => [
        'required',
        'email',
        Rule::unique('clients', 'email')
          ->ignore($contact->id)
          ->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'phone'     => 'nullable|string|max:50',
      'status'    => ['required', Rule::in(['active', 'inactive'])],
      'notes'     => 'nullable|string',
    ]);

    $contact->update($data);

    Activity::useLog('client')
      ->performedOn($contact)
      ->causedBy(Auth::user())
      ->log("client_updated: {$contact->firstName} {$contact->lastName}");

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact updated successfully!');
  }

  /** DELETE /{tenant}/contacts/{contact} */
  public function destroy(Tenant $tenant, Client $contact)
  {
    $this->authorize('delete', $contact);

    $name = "{$contact->firstName} {$contact->lastName}";
    $contact->delete();

    Activity::useLog('client')
      ->performedOn($contact)
      ->causedBy(Auth::user())
      ->log("client_deleted: {$name} (ID {$contact->id})");

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact deleted successfully!');
  }

  // -------- Client Portal --------
  public function portal()
  {
    if (!Auth::check() || Auth::user()->role !== 'client') {
      return redirect()->route('login');
    }

    $user = Auth::user();
    $client = Client::findOrFail($user->client_id);

    $client->load([
      'projects' => function ($query) {
        $query->with([
          'phases',
          'tasks' => function ($q) {
            $q->where('assign_type', 'client')
              ->orWhere('requires_approval', true)
              ->orWhere('client_visible', true);
          }
        ]);
      },
      'uploads'
    ]);

    $projects = $client->projects;
    $activeProjects = $projects->where('status', 'open');
    $completedProjects = $projects->where('status', 'closed');

    return view('clients.portal', [
      'activeProjects'   => $activeProjects,
      'completedProjects' => $completedProjects,
      'uploads'          => $client->uploads
    ]);
  }

  // Admin: resend login email
  // Route as: POST /{tenant}/contacts/{contact}/resend-login
  public function resendLoginEmail(Tenant $tenant, int $clientId)
  {
    $client = Client::where('tenant_id', $tenant->id)->findOrFail($clientId);

    $this->authorize('update', $client);

    $userAccount = $client->userAccount;

    if (!$userAccount) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'No user account found for this client.');
    }

    if (method_exists($userAccount, 'hasLoggedIn') && $userAccount->hasLoggedIn()) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'User has already logged in — email not resent.');
    }

    $tempPassword = Str::random(12);
    $userAccount->password = Hash::make($tempPassword);
    // $userAccount->requires_password_change = true;
    $userAccount->save();

    try {
      Mail::to($userAccount->email)->send(new ClientCredentialsMail($client, $tempPassword));

      Activity::useLog('client')
        ->performedOn($client)
        ->causedBy(Auth::user())
        ->log("client_credentials_resent: Resent login email to {$userAccount->email}");

      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('success_message', 'Login email resent successfully.');
    } catch (\Exception $e) {
      Log::error("Failed to send client credentials email: " . $e->getMessage());
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'Failed to send login email. Check logs.');
    }
  }

  public function viewTaskComments(Request $request, int $taskId)
  {
    $task = Task::findOrFail($taskId);
    $this->authorize('view', $task);

    $comments = $task->comments()->orderBy('created_at', 'asc')->get();

    if ($request->ajax()) {
      return view('clients.partials.comments-list', compact('comments'));
    }
    return view('clients.task-comments', compact('task', 'comments'));
  }

  public function viewProjectDetails($projectId)
  {
    $user = Auth::user();
    if (!$user || $user->role !== 'client' || !$user->client_id) {
      return redirect()->route('client.portal');
    }

    $project = Project::where('id', $projectId)
      ->where('client_id', $user->client_id)
      ->with(['phases', 'tasks'])
      ->firstOrFail();

    $clientVisibleTasks = $project->tasks->filter(function ($task) {
      return $task->assign_type === 'client'
        || $task->requires_approval
        || $task->client_visible;
    });

    $phaseGroups = $project->phases
      ->map(function ($phase) use ($clientVisibleTasks) {
        $tasks = $clientVisibleTasks->where('phase_id', $phase->id)->values();
        return [
          'id'         => $phase->id,
          'name'       => $phase->name,
          'tasks'      => $tasks,
          'sort_order' => $phase->sort_order,
        ];
      })
      ->sortBy('sort_order');

    $unassignedTasks = $clientVisibleTasks->whereNull('phase_id')->values();
    if ($unassignedTasks->isNotEmpty()) {
      $phaseGroups->push([
        'id'         => -1,
        'name'       => 'Other',
        'tasks'      => $unassignedTasks,
        'sort_order' => PHP_INT_MAX,
      ]);
    }

    $currentPhase = $phaseGroups->first(function ($group) {
      return collect($group['tasks'])->contains(function ($task) {
        return in_array($task->status, ['open', 'in-progress'], true);
      });
    });

    if (!$currentPhase) {
      $currentPhase = $phaseGroups->filter(fn($g) => collect($g['tasks'])->isNotEmpty())->last();
    }

    return view('clients.project-details', [
      'project'      => $project,
      'phaseGroups'  => $phaseGroups->values(),
      'currentPhase' => $currentPhase
    ]);
  }

  public function formThankYou(Request $request)
  {
    $taskId = $request->query('task_id');
    return view('clients.form-thank-you', ['task_id' => $taskId]);
  }
}
