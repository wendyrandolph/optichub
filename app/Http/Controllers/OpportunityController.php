<?php

namespace App\Http\Controllers;

use App\Models\Tenant;                           // {tenant} param
use App\Models\Tenant as Organization;          // <- use Tenant records as "organizations"
use App\Models\Opportunity;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class OpportunityController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  /** GET /{tenant}/opportunities */
  public function index(\App\Models\Tenant $tenant)
  {
    $q   = request('q');
    $st  = request('stage');
    $so  = request('sort', 'recent');

    $opps = \App\Models\Opportunity::query()
      ->where('tenant_id', $tenant->id)
      ->when($q, fn($qb) => $qb->where(function ($w) use ($q) {
        $w->where('title', 'like', "%{$q}%")
          ->orWhere('organization_name', 'like', "%{$q}%");
      }))
      ->when($st, fn($qb) => $qb->where('stage', $st))
      ->when($so === 'title_asc',  fn($qb) => $qb->orderBy('title'))
      ->when($so === 'title_desc', fn($qb) => $qb->orderByDesc('title'))
      ->when($so === 'value_desc', fn($qb) => $qb->orderByDesc('estimated_value'))
      ->when($so === 'value_asc',  fn($qb) => $qb->orderBy('estimated_value'))
      ->when($so === 'close_asc',  fn($qb) => $qb->orderBy('close_date'))
      ->when($so === 'close_desc', fn($qb) => $qb->orderByDesc('close_date'))
      ->when($so === 'recent',     fn($qb) => $qb->latest('updated_at'))
      ->paginate(20);

    return view('opportunities.index', [
      'tenant'        => $tenant,
      'opportunities' => $opps,
      // 'stages' => ['New','Qualified','Proposal','Negotiation','Won','Lost'], // optional override
    ]);
  }


  /** GET /{tenant}/opportunities/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Opportunity::class);

    // If your opportunities belong to an "organization" that is actually a Tenant row:
    $organizations = Tenant::where('id', $tenant->id)
      ->get(['id', 'name']);

    return view('opportunities.create', [
      'tenant'        => $tenant,
      'organizations' => $organizations,
    ]);
  }

  /** POST /{tenant}/opportunities */
  public function store(StoreOpportunityRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Opportunity::class);

    $data               = $request->validated();
    $data['tenant_id']  = $tenant->id;

    $opportunity = Opportunity::create($data);

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity created successfully.');
  }

  /** GET /{tenant}/opportunities/{opportunity}/edit */
  public function edit(Tenant $tenant, Opportunity $opportunity): View
  {
    $this->authorize('update', $opportunity);

    $organizations = Organization::where('tenant_id', $tenant->id)
      ->get(['id', 'name']);

    return view('opportunities.edit', [
      'tenant'        => $tenant,
      'opportunity'   => $opportunity,
      'organizations' => $organizations,
    ]);
  }

  /** PUT/PATCH /{tenant}/opportunities/{opportunity} */
  public function update(UpdateOpportunityRequest $request, Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('update', $opportunity);

    $opportunity->update($request->validated());

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity updated successfully.');
  }

  /** DELETE /{tenant}/opportunities/{opportunity} */
  public function destroy(Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('delete', $opportunity);

    $opportunity->delete();

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity deleted.');
  }

  /** (Optional) GET /{tenant}/opportunities/{opportunity} */
  public function show(Tenant $tenant, Opportunity $opportunity): View
  {
    $this->authorize('view', $opportunity);

    return view('opportunities.show', [
      'tenant'      => $tenant,
      'opportunity' => $opportunity,
    ]);
  }
}
