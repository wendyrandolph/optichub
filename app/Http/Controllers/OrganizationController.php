<?php

namespace App\Http\Controllers;

use App\Models\Tenant; // your only model here (represents an "organization")
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrganizationController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    // If you have a TenantPolicy that covers these abilities, you can also do:
    // $this->authorizeResource(Tenant::class, 'organization');
    // (Works because the resource param is {organization})
  }

  /** GET /{tenant}/organizations */
  public function index(\App\Models\Tenant $tenant)
  {
    $q  = request('q');
    $so = request('sort', 'recent');

    // Base list (tweak the visibility logic as needed)
    $orgsQuery = \App\Models\Tenant::query()
      ->when($q, fn($qb) => $qb->where(function ($w) use ($q) {
        $w->where('name', 'like', "%{$q}%")
          ->orWhere('industry', 'like', "%{$q}%")
          ->orWhere('location', 'like', "%{$q}%");
      }));

    $orgs = (clone $orgsQuery)
      ->when($so === 'name_asc',  fn($qb) => $qb->orderBy('name'))
      ->when($so === 'name_desc', fn($qb) => $qb->orderByDesc('name'))
      ->when($so === 'city_asc',  fn($qb) => $qb->orderBy('location'))
      ->when($so === 'recent',    fn($qb) => $qb->latest('updated_at'))
      ->paginate(20);

    // KPIs (overall – not filtered by search/sort)
    $all = \App\Models\Tenant::query();

    $total        = (clone $all)->count();
    $updated30    = (clone $all)->where('updated_at', '>=', Carbon::now()->subDays(30))->count();
    $withSite     = (clone $all)->whereNotNull('website')->where('website', '!=', '')->count();
    $withPhone    = (clone $all)->whereNotNull('phone')->where('phone', '!=', '')->count();
    $byIndustry   = (clone $all)->select('industry', DB::raw('COUNT(*) as c'))
      ->groupBy('industry')->orderByDesc('c')->limit(6)->pluck('c', 'industry');

    return view('organizations.index', [
      'tenant'         => $tenant,
      'organizations'  => $orgs,
      'kpis'           => [
        'total'      => $total,
        'updated30'  => $updated30,
        'with_site'  => $withSite,
        'with_phone' => $withPhone,
        'by_industry' => $byIndustry,
      ],
    ]);
  }


  /** GET /{tenant}/organizations/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Tenant::class);

    return view('organizations.create', [
      'tenant' => $tenant,
    ]);
  }

  /** POST /{tenant}/organizations */
  public function store(StoreOrganizationRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Tenant::class);

    $data = $request->validated();

    try {
      // Create a new "organization" (which is a Tenant record in your world)
      $organization = Tenant::create($data);

      return Redirect::route('tenant.organizations.show', [
        'tenant'       => $tenant,         // model or id
        'organization' => $organization,   // model or id
      ])->with('success', 'Organization created successfully.');
    } catch (\Throwable $e) {
      Log::error('[organizations.store] ' . $e->getMessage());

      return Redirect::route('tenant.organizations.create', [
        'tenant' => $tenant,
      ])->withInput()->with('error', 'Failed to create organization.');
    }
  }

  /** GET /{tenant}/organizations/{organization} */
  public function show(Tenant $tenant, Tenant $organization): View
  {
    $this->authorize('view', $organization);

    // Example eager loads if you have these relationships on Tenant:
    // $organization->load(['clients.projects.payments']);

    // If those relationships live on Tenant-as-Organization:
    $clients    = method_exists($organization, 'clients') ? $organization->clients : collect();
    $projects   = $clients->flatMap(fn($c) => $c->projects ?? collect());
    $totalPaid  = $projects->flatMap(fn($p) => $p->payments ?? collect())->sum('amount');

    return view('organizations.show', [
      'tenant'        => $tenant,
      'organization'  => $organization,
      'clients'       => $clients,
      'projects'      => $projects,
      'totalPaid'     => $totalPaid,
    ]);
  }

  /** GET /{tenant}/organizations/{organization}/edit */
  public function edit(Tenant $tenant, Tenant $organization): View
  {
    $this->authorize('update', $organization);

    return view('organizations.edit', [
      'tenant'        => $tenant,
      'organization'  => $organization,
    ]);
  }

  /** PUT/PATCH /{tenant}/organizations/{organization} */
  public function update(UpdateOrganizationRequest $request, Tenant $tenant, Tenant $organization): RedirectResponse
  {
    $this->authorize('update', $organization);

    $organization->update($request->validated());

    return Redirect::route('tenant.organizations.show', [
      'tenant'       => $tenant,
      'organization' => $organization,
    ])->with('success', 'Organization updated successfully.');
  }

  /** DELETE /{tenant}/organizations/{organization} */
  public function destroy(Tenant $tenant, Tenant $organization): RedirectResponse
  {
    $this->authorize('delete', $organization);

    try {
      // Optional guard if you keep dependent counts on this model
      if (method_exists($organization, 'dependentCounts')) {
        $counts = $organization->dependentCounts();
        $total  = array_sum($counts);
        if ($total > 0) {
          return Redirect::back()->with(
            'error',
            "Cannot delete. Remove or reassign related records first."
          );
        }
      }

      $organization->delete();

      return Redirect::route('tenant.organizations.index', [
        'tenant' => $tenant,
      ])->with('success', 'Organization deleted.');
    } catch (\Throwable $e) {
      Log::error('[organizations.destroy] ' . $e->getMessage());

      return Redirect::route('tenant.organizations.show', [
        'tenant'       => $tenant,
        'organization' => $organization,
      ])->with('error', 'Delete failed: ' . $e->getMessage());
    }
  }
}
