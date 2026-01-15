<?php

namespace App\Http\Controllers;

use App\Models\Tenant; // your only model here (represents an "organization")
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrganizationController extends Controller
{
  /**
   * Handle BOTH:
   * - Provider view:  GET /admin/tenants              (admin.tenants.index)
   * - Tenant view:    GET /{tenant}/companies      (tenant.companies.index)
   */
  public function index(Request $request, Tenant $tenant = null)
  {
    // -------------------------------------------------
    // 1. PROVIDER MODE → /admin/tenants
    // -------------------------------------------------
    if ($request->routeIs('admin.tenants.*')) {
      // List all tenants (your SaaS customers)
      $tenants = Tenant::orderBy('created_at', 'desc')->paginate(20);

      // Simple KPIs based on the current page collection
      $collection = $tenants->getCollection();

      $kpis = [
        'total'         => $tenants->total(), // total in paginator
        'active'        => $collection->where('subscription_status', 'active')->count(),
        'trialing'      => $collection->where('subscription_status', 'trialing')->count(),
        'with_branding' => $collection->filter(function ($t) {
          return !empty($t->primary_color) ||
            !empty($t->secondary_color) ||
            !empty($t->accent_color) ||
            !empty($t->logo_path);
        })->count(),
      ];

      return view('admin.tenants.index', [
        'tenants' => $tenants,
        'kpis'    => $kpis,
      ]);
    }

    // -------------------------------------------------
    // 2. TENANT MODE → /{tenant}/companies
    //    route: tenant.companies.index
    //    Here {tenant} is bound to App\Models\Tenant by id
    // -------------------------------------------------
    if (! $tenant) {
      abort(404, 'Tenant not found.');
    }

    $query = Tenant::where('id', $tenant->id);

    if ($search = $request->get('q')) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('industry', 'like', "%{$search}%")
          ->orWhere('location', 'like', "%{$search}%");
      });
    }

    $companies = $query->orderBy('name')->paginate(25);

    $orgCollection = $companies->getCollection();

    $kpis = [
      'total'       => $companies->total(),
      'updated30'   => 0,         // you can wire these up later if you like
      'with_site'   => $orgCollection->whereNotNull('website')->count(),
      'with_phone'  => $orgCollection->whereNotNull('phone')->count(),
      'by_industry' => $orgCollection
        ->groupBy('industry')
        ->map->count()
        ->sortDesc(),
    ];

    return view('tenant.companies.index', [
      'companies' => $companies,
      'kpis'          => $kpis,
    ]);
  }

  /** GET /{tenant}/companies/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Tenant::class);

    return view('tenant.companies.create', [
      'tenant' => $tenant,
    ]);
  }

  /** POST /{tenant}/companies */
  public function store(StoreOrganizationRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Tenant::class);

    $data = $request->validated();

    try {
      // Create a new "organization" (which is a Tenant record in your world)
      $organization = Tenant::create($data);

      return Redirect::route('tenant.companies.show', [
        'tenant'       => $tenant,         // model or id
        'organization' => $organization,   // model or id
      ])->with('success', 'Organization created successfully.');
    } catch (\Throwable $e) {
      Log::error('[companies.store] ' . $e->getMessage());

      return Redirect::route('tenant.companies.create', [
        'tenant' => $tenant,
      ])->withInput()->with('error', 'Failed to create organization.');
    }
  }

  /** GET /{tenant}/companies/{organization} */
  public function show(Request $request, Tenant $tenant)
  {
    // PROVIDER: admin.tenants.show  (no {organization} in route)
    if ($request->routeIs('admin.tenants.show')) {
      // Here, $tenant is the SaaS tenant (workspace)
      // Reuse the premium tenant show page we built.

      $hasBranding = !empty($tenant->primary_color)
        || !empty($tenant->secondary_color)
        || !empty($tenant->accent_color)
        || !empty($tenant->logo_path);

      $status = strtolower($tenant->subscription_status ?? 'inactive');
      $statusLabel = match ($status) {
        'active'    => 'Active',
        'trialing'  => 'Trialing',
        'paused'    => 'Paused',
        'canceled'  => 'Canceled',
        default     => ucfirst($status),
      };

      $stats = [
        'clients'   => method_exists($tenant, 'contacts') ? $tenant->contacts()->count() : null,
        'projects'  => method_exists($tenant, 'projects') ? $tenant->projects()->count() : null,
        'invoices'  => method_exists($tenant, 'invoices') ? $tenant->invoices()->count() : null,
        'users'     => method_exists($tenant, 'users') ? $tenant->users()->count() : null,
      ];

      return view('admin.tenants.show', [
        'tenant'      => $tenant,
        'hasBranding' => $hasBranding,
        'status'      => $status,
        'statusLabel' => $statusLabel,
        'stats'       => $stats,
      ]);
    }

    // TENANT: tenant.companies.show  (has {tenant} + {organization})
    if (! $organization) {
      abort(404, 'Organization not found.');
    }

    // Optional safety: enforce that org belongs to tenant
    if ($organization->tenant_id !== $tenant->id) {
      abort(404, 'This organization does not belong to this tenant.');
    }

    return view('tenant.companies.show', [
      'tenant'       => $tenant,
      'organization' => $organization,
    ]);
  }

  /** GET /{tenant}/companies/{organization}/edit */
  public function edit(Tenant $tenant)
  {
    $planOptions = [
      'starter'    => 'Starter',
      'pro'        => 'Pro',
      'studio'     => 'Studio',
      'enterprise' => 'Enterprise',
    ];

    $statusOptions = [
      'active'    => 'Active',
      'trialing'  => 'Trialing',
      'paused'    => 'Paused',
      'canceled'  => 'Canceled',
    ];

    return view('admin.tenants.edit', [
      'tenant'        => $tenant,
      'planOptions'   => $planOptions,
      'statusOptions' => $statusOptions,
    ]);
  }


  /** PUT/PATCH /{tenant}/companies/{organization} */
  public function update(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'name'               => 'required|string|max:255',
      'website'            => 'nullable|string|max:255',
      'subscription_status' => 'required|string|in:active,trialing,paused,canceled',
      'plan_name'          => 'nullable|string|max:100',
      'primary_color'      => 'nullable|string|max:20',
      'secondary_color'    => 'nullable|string|max:20',
      'accent_color'       => 'nullable|string|max:20',

    ]);


    $tenant->update($data);

    return redirect()->route('admin.tenants.show', $tenant)
      ->with('success', 'Tenant updated successfully.');
  }


  /** DELETE /{tenant}/companies/{organization} */
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

      return Redirect::route('tenant.companies.index', [
        'tenant' => $tenant,
      ])->with('success', 'Organization deleted.');
    } catch (\Throwable $e) {
      Log::error('[companies.destroy] ' . $e->getMessage());

      return Redirect::route('tenant.companies.show', [
        'tenant'       => $tenant,
        'organization' => $organization,
      ])->with('error', 'Delete failed: ' . $e->getMessage());
    }
  }
}
