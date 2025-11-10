<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LeadController extends Controller
{
  public function __construct(protected LeadConversionService $conversionService)
  {
    $this->middleware('auth');
  }

  /** GET /{tenant}/leads */
  public function index(Tenant $tenant): View
  {
    $this->authorize('viewAny', Lead::class);

    $leads = Lead::where('tenant_id', $tenant->id)
      ->with(['owner:id,first_name,last_name,username,email'])
      ->latest()
      ->paginate(20);

    return view('leads.index', compact('tenant', 'leads'));
  }
  public function show(Tenant $tenant, Lead $lead): View
  {
    // authorize if you have a LeadPolicy
    $this->authorize('view', $lead);

    // safety: ensure lead belongs to the route tenant
    if ($lead->tenant_id !== $tenant->id) {
      abort(404);
    }

    // anything else you want to load:
    // $lead->load(['owner', 'activities']);

    return view('leads.show', [
      'tenant' => $tenant,
      'lead'   => $lead,
    ]);
  }
  /** GET /{tenant}/leads/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Lead::class);

    $owners = User::where('tenant_id', $tenant->id)
      ->whereIn('role', ['admin', 'employee', 'provider', 'super_admin', 'superadmin'])
      ->orderBy('username')
      ->get(['id', 'first_name', 'last_name', 'username', 'email']);

    $sources = ['web', 'referral', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];

    return view('leads.create', compact('tenant', 'owners', 'sources', 'statuses'));
  }

  /** POST /{tenant}/leads */
  public function store(StoreLeadRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Lead::class);

    $data = $request->validated();
    $data['tenant_id'] = $tenant->id;
    if (empty($data['owner_id'])) {
      $data['owner_id'] = null;
    }

    $lead = Lead::create($data);

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
      ->orderBy('username')
      ->get(['id', 'first_name', 'last_name', 'username', 'email']);

    $sources = ['web', 'referral', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];

    return view('leads.edit', compact('tenant', 'lead', 'owners', 'sources', 'statuses'));
  }

  /** PUT/PATCH /{tenant}/leads/{lead} */
  public function update(UpdateLeadRequest $request, Tenant $tenant, Lead $lead): RedirectResponse
  {
    $this->authorize('update', $lead);

    $data = $request->validated();
    if (empty($data['owner_id'])) {
      $data['owner_id'] = null;
    }

    $lead->update($data);

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Lead updated successfully!');
  }
}
