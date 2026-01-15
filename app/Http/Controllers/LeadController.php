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
  public function index(Tenant $tenant)
  {
    $this->authorize('viewLeadInsights');
    $this->authorize('viewAny', Lead::class);

    $q = request('q', '');
    $status = request('status', '');
    $ownerId = request('owner_id');
    $minAge = request('min_age_days');
    $maxAge = request('max_age_days');
    $minAge = is_numeric($minAge) ? (int) $minAge : null;
    $maxAge = is_numeric($maxAge) ? (int) $maxAge : null;
    $ageExpr = "GREATEST(DATEDIFF(CURDATE(), COALESCE(status_changed_at, updated_at, created_at)), 0)";

    $leads = Lead::where('tenant_id', $tenant->id)
      ->with(['owner:id,first_name,last_name,username,email'])
      ->when(is_numeric($ownerId), fn($query) => $query->where('owner_id', (int) $ownerId))
      ->when($q !== '', function ($query) use ($q) {
        $query->where(function ($sub) use ($q) {
          $sub->where('name', 'like', "%{$q}%")
            ->orWhere('first_name', 'like', "%{$q}%")
            ->orWhere('last_name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orWhere('source', 'like', "%{$q}%");
        });
      })
      ->when($status !== '', fn($query) => $query->where('status', $status))
      ->when($minAge !== null, fn($query) => $query->whereRaw("$ageExpr >= ?", [$minAge]))
      ->when($maxAge !== null, fn($query) => $query->whereRaw("$ageExpr <= ?", [$maxAge]))
      ->orderByDesc('updated_at')
      ->paginate(20)
      ->appends([
        'q' => $q,
        'status' => $status,
        'owner_id' => $ownerId,
        'min_age_days' => $minAge,
        'max_age_days' => $maxAge,
      ]);

    return view('leads.index', compact('tenant', 'leads'));
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

    $companies = \App\Models\ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $sources = ['web', 'referral', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];

    return view('leads.create', compact('tenant', 'owners', 'companies', 'sources', 'statuses'));
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

    $companies = \App\Models\ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $sources = ['web', 'referral', 'ads', 'email', 'event', 'other'];
    $statuses = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];

    return view('leads.edit', compact('tenant', 'lead', 'owners', 'companies', 'sources', 'statuses'));
  }



  /** PUT/PATCH /{tenant}/leads/{lead} */
  public function update(UpdateLeadRequest $request, Tenant $tenant, Lead $lead): RedirectResponse
  {


    $this->authorize('update', $lead);

    $data = $request->validated();
    if (empty($data['owner_id'])) {
      $data['owner_id'] = null;
    }

    if (array_key_exists('status', $data) && $data['status'] !== $lead->status) {
      $data['status_changed_at'] = now();
    }

    $lead->update($data);

    return redirect()
      ->route('tenant.leads.show', ['tenant' => $tenant, 'lead' => $lead])
      ->with('success', 'Lead updated successfully!');
  }
}
