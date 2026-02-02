<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Lead;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\LeadConversionService;
use App\Models\Client;
use App\Models\LeadEvent;
use App\Models\Opportunity;
use App\Models\Project;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;



class LeadController extends Controller
{
  public function __construct(protected LeadConversionService $conversionService)
  {
    $this->middleware('auth');
  }

  /** GET /{tenant}/leads */
  public function index(Tenant $tenant)
  {
    $this->authorize('viewLeadInsights');
    $this->authorize('viewAny', Lead::class);

    $q = trim((string) request('q', ''));
    $status = trim((string) request('status', ''));
    $priority = trim((string) request('priority', ''));
    $ownerId = request('owner_id');
    $sort = trim((string) request('sort', 'newest'));

    $leadsQuery = Lead::where('tenant_id', $tenant->id)
      ->with(['owner:id,first_name,last_name,email', 'ownerUser:id,first_name,last_name,email'])
      ->when(is_numeric($ownerId), function ($query) use ($ownerId) {
        $query->where(function ($sub) use ($ownerId) {
          $sub->where('owner_user_id', (int) $ownerId)
            ->orWhere('owner_id', (int) $ownerId);
        });
      })
      ->when($q !== '', function ($query) use ($q) {
        $query->where(function ($sub) use ($q) {
          $sub->where('name', 'like', "%{$q}%")
            ->orWhere('first_name', 'like', "%{$q}%")
            ->orWhere('last_name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('company', 'like', "%{$q}%")
            ->orWhere('message', 'like', "%{$q}%")
            ->orWhere('notes', 'like', "%{$q}%")
            ->orWhere('source', 'like', "%{$q}%");
        });
      })
      ->when($status !== '', fn($query) => $query->where('status', $status))
      ->when($priority !== '', fn($query) => $query->where('priority', $priority));

    if ($sort === 'oldest') {
      $leadsQuery->orderBy('submitted_at')->orderBy('created_at');
    } elseif ($sort === 'priority') {
      $leadsQuery->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
        ->orderByDesc('submitted_at')
        ->orderByDesc('created_at');
    } else {
      $leadsQuery->orderByDesc('submitted_at')->orderByDesc('created_at');
    }

    $leads = $leadsQuery->paginate(20)->appends([
      'q' => $q,
      'status' => $status,
      'priority' => $priority,
      'owner_id' => $ownerId,
      'sort' => $sort,
    ]);

    return view('leads.index', [
      'tenant' => $tenant,
      'leads' => $leads,
      'owners' => User::where('tenant_id', $tenant->id)
        ->whereIn('role', ['admin', 'employee', 'provider', 'super_admin', 'superadmin'])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get(['id', 'first_name', 'last_name', 'email']),
      'statusOptions' => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'],
      'priorityOptions' => ['low', 'normal', 'high'],
      'sortOptions' => ['newest', 'oldest', 'priority'],
    ]);
  }
  public function show(Tenant $tenant, Lead $lead): View
  {
    $this->authorize('viewLeadInsights');
    // authorize if you have a LeadPolicy
    $this->authorize('view', $lead);

    // safety: ensure lead belongs to the route tenant
    if ($lead->tenant_id !== $tenant->id) {
      abort(404);
    }

    // anything else you want to load:
    // $lead->load(['owner', 'activities']);

    $events = $lead->leadEvents()->latest()->get();

    return view('leads.show', [
      'tenant' => $tenant,
      'lead'   => $lead,
      'events' => $events,
      'owners' => User::where('tenant_id', $tenant->id)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get(['id', 'first_name', 'last_name', 'email']),
      'statusOptions' => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'],
      'priorityOptions' => ['low', 'normal', 'high'],
    ]);
  }
  /** GET /{tenant}/leads/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Lead::class);

    $owners = User::where('tenant_id', $tenant->id)
      ->whereIn('role', ['admin', 'employee', 'provider', 'super_admin', 'superadmin'])
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->get(['id', 'first_name', 'last_name', 'email']);

    $companies = \App\Models\ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $sources = ['website', 'referral', 'manual', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'];
    $priorities = ['low', 'normal', 'high'];

    return view('leads.create', compact('tenant', 'owners', 'companies', 'sources', 'statuses', 'priorities'));
  }

  /** POST /{tenant}/leads */
  public function store(StoreLeadRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Lead::class);

    $data = $request->validated();
    $data['tenant_id'] = $tenant->id;
    if (empty($data['owner_user_id'])) {
      $data['owner_user_id'] = null;
    }
    if (empty($data['submitted_at'])) {
      $data['submitted_at'] = now();
    }
    if (empty($data['status_changed_at'])) {
      $data['status_changed_at'] = now();
    }

    $lead = Lead::create($data);

    LeadEvent::create([
      'tenant_id' => $tenant->id,
      'lead_id' => $lead->id,
      'type' => 'status_change',
      'payload' => [
        'from' => null,
        'to' => $lead->status,
      ],
      'created_by_user_id' => auth()->id(),
    ]);

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Lead created successfully!');
  }

  /** GET /{tenant}/leads/{lead}/edit */
  public function edit(Tenant $tenant, Lead $lead): View
  {
    $this->authorize('update', $lead);

    $owners = User::where('tenant_id', $tenant->id)
      ->whereIn('role', ['admin', 'employee', 'provider', 'super_admin', 'superadmin'])
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->get(['id', 'first_name', 'last_name', 'email']);

    $companies = \App\Models\ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $sources = ['website', 'referral', 'manual', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'];
    $priorities = ['low', 'normal', 'high'];

    return view('leads.edit', compact('tenant', 'lead', 'owners', 'companies', 'sources', 'statuses', 'priorities'));
  }



  /** PUT/PATCH /{tenant}/leads/{lead} */
  public function update(UpdateLeadRequest $request, Tenant $tenant, Lead $lead): RedirectResponse
  {


    $this->authorize('update', $lead);

    $data = $request->validated();
    if (empty($data['owner_user_id'])) {
      $data['owner_user_id'] = null;
    }

    $previousStatus = $lead->status;
    $previousOwner = $lead->owner_user_id;

    if (array_key_exists('status', $data) && $data['status'] !== $lead->status) {
      $data['status_changed_at'] = now();
    }

    $lead->update($data);

    if (array_key_exists('status', $data) && $data['status'] !== $previousStatus) {
      LeadEvent::create([
        'tenant_id' => $tenant->id,
        'lead_id' => $lead->id,
        'type' => 'status_change',
        'payload' => [
          'from' => $previousStatus,
          'to' => $data['status'],
        ],
        'created_by_user_id' => auth()->id(),
      ]);
    }

    if (array_key_exists('owner_user_id', $data) && $data['owner_user_id'] !== $previousOwner) {
      LeadEvent::create([
        'tenant_id' => $tenant->id,
        'lead_id' => $lead->id,
        'type' => 'assignment',
        'payload' => [
          'from' => $previousOwner,
          'to' => $data['owner_user_id'],
        ],
        'created_by_user_id' => auth()->id(),
      ]);
    }

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Lead updated successfully!');
  }

  public function storeNote(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
  {
    $this->authorize('update', $lead);
    if ($lead->tenant_id !== $tenant->id) {
      abort(404);
    }

    $data = $request->validate([
      'note' => ['required', 'string', 'max:2000'],
    ]);

    LeadEvent::create([
      'tenant_id' => $tenant->id,
      'lead_id' => $lead->id,
      'type' => 'note',
      'payload' => ['note' => $data['note']],
      'created_by_user_id' => auth()->id(),
    ]);

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Note added.');
  }

  public function convert(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
  {
    $this->authorize('update', $lead);
    if ($lead->tenant_id !== $tenant->id) {
      abort(404);
    }

    $data = $request->validate([
      'contact_id' => ['nullable', 'integer'],
      'create_opportunity' => ['nullable', 'boolean'],
      'create_project' => ['nullable', 'boolean'],
      'opportunity_title' => ['nullable', 'string', 'max:255'],
      'project_title' => ['nullable', 'string', 'max:255'],
    ]);

    $contact = null;
    if (!empty($data['contact_id'])) {
      $contact = Client::query()
        ->where('tenant_id', $tenant->id)
        ->where('id', $data['contact_id'])
        ->first();
    }

    if (!$contact) {
      [$firstName, $lastName] = $this->splitName($lead->name);
      $contact = Client::create([
        'tenant_id' => $tenant->id,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $lead->email,
        'phone' => $lead->phone,
        'status' => 'active',
        'role' => 'client',
      ]);
    }

    $opportunityId = null;
    if (!empty($data['create_opportunity'])) {
      $opportunity = Opportunity::create([
        'tenant_id' => $tenant->id,
        'title' => ($data['opportunity_title'] ?? '') ?: ($lead->name ? "Lead: {$lead->name}" : 'New Opportunity'),
        'stage' => 'new',
        'stage_changed_at' => now(),
        'owner_id' => $lead->owner_user_id ?? $lead->owner_id,
        'lead_id' => $lead->id,
        'company_id' => $lead->company_id,
      ]);
      $opportunityId = $opportunity->id;
    }

    $projectId = null;
    if (!empty($data['create_project'])) {
      $project = Project::create([
        'tenant_id' => $tenant->id,
        'contact_id' => $contact->id,
        'project_name' => ($data['project_title'] ?? '') ?: ($lead->name ? "Project: {$lead->name}" : 'New Project'),
        'status' => 'active',
      ]);
      $projectId = $project->id;
    }

    $lead->update([
      'status' => 'converted',
      'status_changed_at' => now(),
      'converted_contact_id' => $contact->id,
      'converted_opportunity_id' => $opportunityId,
      'converted_project_id' => $projectId,
    ]);

    LeadEvent::create([
      'tenant_id' => $tenant->id,
      'lead_id' => $lead->id,
      'type' => 'conversion',
      'payload' => [
        'contact_id' => $contact->id,
        'opportunity_id' => $opportunityId,
        'project_id' => $projectId,
      ],
      'created_by_user_id' => auth()->id(),
    ]);

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Lead converted.');
  }

  /** DELETE /{tenant}/leads/{lead} */
  public function destroy(Tenant $tenant, Lead $lead): RedirectResponse
  {
    $this->authorize('delete', $lead);

    if ($lead->tenant_id !== $tenant->id) {
      abort(404);
    }

    $lead->deleted_by = auth()->id();
    $lead->save();

    ActivityLog::record(
      $tenant->id,
      auth()->id(),
      $lead,
      'lead.deleted',
      'Lead deleted',
      [
        'lead_name' => $lead->name,
        'lead_status' => $lead->status,
      ]
    );

    $lead->delete();

    return redirect()
      ->route('tenant.leads.index', ['tenant' => $tenant])
      ->with('success', 'Lead deleted successfully!');
  }

  private function splitName(?string $name): array
  {
    $name = trim((string) $name);
    if ($name === '') {
      return ['Lead', ''];
    }

    $parts = preg_split('/\\s+/', $name, 2);
    return [$parts[0] ?? 'Lead', $parts[1] ?? ''];
  }
}
