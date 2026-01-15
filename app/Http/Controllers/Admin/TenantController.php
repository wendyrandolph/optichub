<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class TenantController extends Controller
{
  /**
   * Handle BOTH:
   * - Provider view:  GET /admin/tenants              (admin.tenants.index)
   * - Tenant view:    GET /{provider}/tenant      (provider.tenant.index)
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
    // 2. TENANT MODE → /{tenant}/tenant
    //    route: tenant.tenant.index
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

    $tenant = $query->orderBy('name')->paginate(25);

    $orgCollection = $tenant->getCollection();

    $kpis = [
      'total'       => $tenant->total(),
      'updated30'   => 0,         // you can wire these up later if you like
      'with_site'   => $orgCollection->whereNotNull('website')->count(),
      'with_phone'  => $orgCollection->whereNotNull('phone')->count(),
      'by_industry' => $orgCollection
        ->groupBy('industry')
        ->map->count()
        ->sortDesc(),
    ];

    return view('tenant.tenant.index', [
      'tenant' => $tenant,
      'kpis'          => $kpis,
    ]);
  }

  /** GET /{tenant}/tenant/create */
  public function create(Request $request, Tenant $tenant = null): View
  {
    $this->authorize('create', Tenant::class);

    if ($request->routeIs('admin.tenants.*')) {
      return view('admin.tenants.create', [
        'tenant' => null,
        'organization' => new Tenant(),
      ]);
    }

    return view('tenant.companies.create', [
      'tenant' => $tenant,
    ]);
  }

  /** POST /{tenant}/tenant */
  public function store(StoreOrganizationRequest $request, Tenant $tenant = null): RedirectResponse
  {
    $this->authorize('create', Tenant::class);

    $data = $request->validated();

    try {
      // Create a new "organization" (which is a Tenant record in your world)
      $organization = Tenant::create($data);

      if ($request->routeIs('admin.tenants.*')) {
        return Redirect::route('admin.tenants.show', [
          'tenant' => $organization,
        ])->with('success', 'Tenant created successfully.');
      }

      return Redirect::route('tenant.tenant.show', [
        'tenant'       => $tenant,         // model or id
        'organization' => $organization,   // model or id
      ])->with('success', 'Organization created successfully.');
    } catch (\Throwable $e) {
      Log::error('[tenant.store] ' . $e->getMessage());

      if ($request->routeIs('admin.tenants.*')) {
        return Redirect::route('admin.tenants.create')
          ->withInput()
          ->with('error', 'Failed to create tenant.');
      }

      return Redirect::route('tenant.tenant.create', [
        'tenant' => $tenant,
      ])->withInput()->with('error', 'Failed to create organization.');
    }
  }

  /** GET /{tenant}/tenant/{organization} */
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

    // TENANT: tenant.tenant.show  (has {tenant} + {organization})
    if (! $organization) {
      abort(404, 'Organization not found.');
    }

    // Optional safety: enforce that org belongs to tenant
    if ($organization->tenant_id !== $tenant->id) {
      abort(404, 'This organization does not belong to this tenant.');
    }

    return view('tenant.tenant.show', [
      'tenant'       => $tenant,
      'organization' => $organization,
    ]);
  }

  /** GET /{tenant}/tenant/{organization}/edit */
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


  /** PUT/PATCH /{tenant}/tenant/{organization} */
  public function update(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'name'               => 'required|string|max:255',
      'website'            => 'nullable|string|max:255',
      'timezone'           => 'nullable|string|max:100',
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


  /** DELETE /admin/tenants/{tenant} */
  public function destroy(Tenant $tenant): RedirectResponse
  {
    try {
      // Optional guard if you keep dependent counts on this model
      if (method_exists($tenant, 'dependentCounts')) {
        $counts = $tenant->dependentCounts();
        $total  = array_sum($counts);
        if ($total > 0) {
          return Redirect::back()->with(
            'error',
            "Cannot delete. Remove or reassign related records first."
          );
        }
      }

      $tenant->delete();

      return Redirect::route('admin.tenants.index')
        ->with('success', 'Tenant deleted.');
    } catch (\Throwable $e) {
      Log::error('[tenant.destroy] ' . $e->getMessage());

      return Redirect::route('admin.tenants.show', $tenant)
        ->with('error', 'Delete failed: ' . $e->getMessage());
    }
  }
}
