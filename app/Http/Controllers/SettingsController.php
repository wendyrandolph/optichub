<?php


namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Tenant;
use App\Models\Phase;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\Settings\ApiGenerateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use ILluminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;

class SettingsController extends Controller
{

  protected ApiKey $apiKeyModel;
  protected Tenant $tenantModel;

  public function __construct(ApiKey $apiKeyModel, Tenant $tenantModel)
  {

    // Only keep auth here; apply tenant/role on the routes (cleaner & testable)
    $this->middleware('auth:web,admin');
    $this->apiKeyModel = $apiKeyModel;
    $this->tenantModel = $tenantModel;
  }

  private function tenantId(): int
  {
    return (int) (Auth::user()->tenant_id ?? 0);
  }

  /** GET /{tenant}/settings */
  // app/Http/Controllers/SettingsController.php

  public function index()
  {
    Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    return view('admin.settings.index');
  }

  public function billing()
  {
    Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    return view('admin.settings.billing');
  }

  public function billingUpdate(Request $request, Tenant $tenant): RedirectResponse
  {
    $data = $request->validate([
      'allow_partial_payments' => ['nullable', 'boolean'],
    ]);

    $tenant->update([
      'allow_partial_payments' => (bool) ($data['allow_partial_payments'] ?? false),
    ]);

    return redirect()
      ->route('tenant.settings.billing', ['tenant' => $tenant->id])
      ->with('status', 'Billing settings updated.');
  }

  public function upgradeForm(): \Illuminate\View\View
  {
    Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    $plans = [
      ['code' => 'starter', 'name' => 'Starter', 'price' => 1900, 'features' => ['Up to 3 projects', 'Email support']],
      ['code' => 'growth', 'name' => 'Growth', 'price' => 4900, 'features' => ['Unlimited projects', 'Priority support', 'API access']],
      ['code' => 'business', 'name' => 'Business', 'price' => 9900, 'features' => ['SLA support', 'Custom onboarding']],
    ];
    return view('admin.settings.billing-upgrade', compact('plans'));
  }

  public function apiIndex(): View
  {
    $tenantId    = $this->tenantId();
    $newPlainKey = session('flash_new_key');
    $keys        = $this->apiKeyModel->listActiveByTenant($tenantId);

    return view('admin.settings.api-keys', [
      'keys'        => $keys,
      'newPlainKey' => $newPlainKey,
      'apiKeyContext' => 'settings',
    ]);
  }
  public function apiGenerate(): RedirectResponse
  {
    $tenantId = $this->tenantId();

    // create a new key and get its plain value once
    [, $plain] = ApiKey::issue($tenantId, 'Settings generated', auth()->id());

    return redirect()
      ->route('tenant.settings.api.index', ['tenant' => $tenantId])
      ->with('flash_success', 'New API key generated.')
      ->with('flash_new_key', $plain); // show once in the UI
  }

  /** POST /{tenant}/settings/api/{keyId}/revoke */
  public function apiRevoke(Tenant $tenant, string $keyId): RedirectResponse
  {
    $tenantId = $tenant->getKey();

    ApiKey::revokeKey($tenantId, $keyId);

    return redirect()
      ->route('tenant.settings.api.index', ['tenant' => $tenantId])
      ->with('flash_success', 'API key revoked.');
  }


  public function profileForm(): View
  {

    // Resolve tenant ID the same way you do in profileUpdate()
    $tenantId = $this->tenantId(); // or cast $tenant if you're passing it in

    $tenantModel = Tenant::findOrFail($tenantId);

    // Build an "org" array for the view (matches your Blade keys)
    $org = [
      'name'            => $tenantModel->name,
      'website'         => $tenantModel->website,
      'phone'           => $tenantModel->phone,
      'support_email'   => $tenantModel->support_email,
      'primary_color'   => $tenantModel->primary_color,
      'secondary_color' => $tenantModel->secondary_color,
      'accent_color'    => $tenantModel->accent_color,
      'logo_url'        => $tenantModel->logo_path ? asset('storage/' . $tenantModel->logo_path) : null,
      'brand_tagline'   => $tenantModel->brand_tagline,    // <—
      'invoice_footer'  => $tenantModel->invoice_footer,   // <—
    ];

    $phaseTemplate = Phase::where('tenant_id', $tenantModel->id)
      ->whereNull('project_id')
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get(['id', 'name']);

    return view('admin.settings.profile', [
      'tenant' => $tenantModel,
      'org'    => $org,
      'phaseTemplate' => $phaseTemplate,
    ]);
  }
  public function profileUpdate(ProfileUpdateRequest $request)
  {
    //dd('profileUpdate hit', $request->all(), $request->validated());

    $tenantId = $this->tenantId();
    $tenant   = $this->tenantModel->with([])->findOrFail($tenantId);

    $data = $request->validated();

    // handle logo upload
    if ($request->hasFile('logo')) {
      // optional: delete old logo
      if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
        Storage::disk('public')->delete($tenant->logo_path);
      }

      $path = $request->file('logo')->store('tenant-logos', 'public');
      $data['logo_path'] = $path;
    }

    $data['default_uses_phases'] = $request->boolean('default_uses_phases', false);

    $tenant->fill($data);
    $tenant->save();

    // Sync tenant phase template (project_id null)
    $phaseNames = collect($request->input('phases', []))
      ->map(fn($n) => trim((string) $n))
      ->filter()
      ->unique()
      ->values()
      ->take(5);

    Phase::where('tenant_id', $tenantId)->whereNull('project_id')->delete();
    foreach ($phaseNames as $idx => $name) {
      Phase::create([
        'tenant_id' => $tenantId,
        'project_id' => null,
        'name' => $name,
        'sort_order' => $idx + 1,
      ]);
    }

    return redirect()
      ->route('tenant.settings.profile', ['tenant' => $tenantId])
      ->with('flash_success', 'Profile updated successfully.');
  }

  public function clientPreferencesForm()
  {
    $tenantId = $this->tenantId();
    $tenant   = Tenant::findOrFail($tenantId);

    return view('admin.settings.clients', [
      'tenant' => $tenant,
    ]);
  }

  public function clientPreferencesUpdate(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'default_client_type' => ['required', 'in:person,company'],
      'client_type_prompt'  => ['required', 'in:use_default,ask_each_time'],
    ]);

    $tenant->update($data);

    return redirect()
      ->route('tenant.settings.clients', ['tenant' => $tenant->id])
      ->with('status', 'Client preferences updated.');
  }

  /**
   * Pinned shortcuts form
   */
  public function pinsForm(Request $request): View
  {
    $tenantId = $this->tenantId();
    $userId = auth()->id();

    $pref = UserPreference::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
    $current = $pref?->pinned_nav ?? [];

    $options = [
      'tenant.projects.index'     => 'Projects',
      'tenant.tasks.index'        => 'Tasks',
      'tenant.contacts.index'     => 'Clients',
      'tenant.calendar.index'     => 'Calendar',
      'tenant.time.index'         => 'Time',
      'tenant.invoices.index'     => 'Invoices',
      'tenant.opportunities.index'=> 'Opportunities',
      'tenant.emails.index'       => 'Emails',
      'tenant.emails.create'      => 'Compose Email',
    ];

    return view('admin.settings.pins', [
      'tenantId' => $tenantId,
      'current'  => $current,
      'options'  => $options,
    ]);
  }

  public function pinsUpdate(Request $request)
  {
    $tenantId = $this->tenantId();
    $userId = auth()->id();

    $options = [
      'tenant.projects.index',
      'tenant.tasks.index',
      'tenant.contacts.index',
      'tenant.calendar.index',
      'tenant.time.index',
      'tenant.invoices.index',
      'tenant.opportunities.index',
      'tenant.emails.index',
      'tenant.emails.create',
    ];

    $data = $request->validate([
      'pins' => 'array|max:4',
      'pins.*' => 'nullable|string',
    ]);

    $pins = collect($data['pins'] ?? [])
      ->filter()
      ->unique()
      ->values()
      ->take(4)
      ->filter(fn($route) => in_array($route, $options, true))
      ->all();

    $pref = UserPreference::firstOrNew([
      'user_id' => $userId,
      'tenant_id' => $tenantId,
    ]);

    $pref->pinned_nav = $pins;
    $pref->save();

    return redirect()
      ->route('tenant.settings.pins', ['tenant' => $tenantId])
      ->with('flash_success', 'Pinned shortcuts updated.');
  }
}
