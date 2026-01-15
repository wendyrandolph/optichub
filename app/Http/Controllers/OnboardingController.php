<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\ClientCompany;
use App\Models\Project;
use App\Models\Contact;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
  protected function currentTenant(): ?Tenant
  {
    $user = Auth::guard('admin')->user();

    if (! $user) {
      return null;
    }

    return $user->tenant ?? null;
  }

  // STEP 1: Welcome
  public function welcome()
  {
    $user   = Auth::guard('admin')->user();
    $tenant = $this->currentTenant();

    return view('onboarding.welcome', compact('user', 'tenant'));
  }

  public function storeWorkspace(Request $request)
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    $data = $request->validate([
      'workspace_type' => ['required', 'in:creative,trades'],
    ]);

    $tenant->update([
      'workspace_type' => $data['workspace_type'],
    ]);

    if ($data['workspace_type'] === 'trades') {
      app(TenantProvisioner::class)->applyTradesDefaults($tenant);
    }

    return redirect()->route('onboarding.brand');
  }

  // STEP 2: Brand colors
  // STEP 2: Brand colors
  public function brand()
  {
    $tenant = $this->currentTenant();

    return view('onboarding.brand', [
      'tenant' => $tenant,

      'primary_color' => blank($tenant->primary_color)
        ? '#1C2E70'
        : $tenant->primary_color,

      'secondary_color' => blank($tenant->secondary_color)
        ? '#8FAF9A'
        : $tenant->secondary_color,

      'accent_color' => blank($tenant->accent_color)
        ? '#9A8FBF'
        : $tenant->accent_color,
    ]);
  }

  public function storeBrand(Request $request)
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    $data = $request->validate([
      'primary_color'   => ['required', 'string', 'max:20'],
      'secondary_color' => ['required', 'string', 'max:20'],
      'accent_color'    => ['required', 'string', 'max:20'],
    ]);

    $tenant->update($data);

    return redirect()->route('onboarding.logo');
  }

  // STEP 3: Logo upload
  public function logo()
  {
    $tenant = $this->currentTenant();

    return view('onboarding.logo', compact('tenant'));
  }

  public function storeLogo(Request $request)
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    $data = $request->validate([
      'logo' => ['nullable', 'image', 'max:2048'], // 2 MB
    ]);

    if ($request->hasFile('logo')) {
      $path = $request->file('logo')->store('tenant-logos', 'public');
      $tenant->update(['logo_path' => $path]);
    }

    return redirect()->route('onboarding.client');
  }

  public function client()
  {
    $tenant = $this->currentTenant();

    return view('onboarding.client', [
      'tenant' => $tenant,
    ]);
  }

  public function storeClient(Request $request)
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    // 1) Persist tenant preference
    $defaultType = $request->input('default_client_type', 'person');
    if (! in_array($defaultType, ['person', 'company'], true)) {
      $defaultType = 'person';
    }
    $promptSetting = $request->input('client_type_prompt', 'use_default');
    if (! in_array($promptSetting, ['use_default', 'ask_each_time'], true)) {
      $promptSetting = 'use_default';
    }
    $tenant->update([
      'default_client_type' => $defaultType,
      'client_type_prompt'  => $promptSetting,
    ]);

    // 2) Effective client type
    $effectiveType = $promptSetting === 'ask_each_time'
      ? $request->input('client_type', $defaultType)
      : $defaultType;
    $effectiveType = $effectiveType === 'company' ? 'company' : 'person';

    // 3) Validation
    $rules = [
      'default_client_type' => ['required', 'in:person,company'],
      'client_type_prompt'  => ['required', 'in:use_default,ask_each_time'],
      'client_type'         => ['nullable', 'in:person,company'],
      'contact_first_name'  => ['required', 'string', 'max:80'],
      'contact_last_name'   => ['required', 'string', 'max:80'],
      'contact_email'       => ['nullable', 'email', 'max:120', 'required_without:contact_phone'],
      'contact_phone'       => ['nullable', 'string', 'max:50', 'required_without:contact_email'],
    ];

    if ($effectiveType === 'company') {
      $rules = array_merge($rules, [
        'company_name' => ['required', 'string', 'max:255'],
        'industry'     => ['nullable', 'string', 'max:255'],
        'website'      => ['nullable', 'string', 'max:255'],
        'phone'        => ['nullable', 'string', 'max:50'],
      ]);
    }

    $data = $request->validate($rules);

    // 4) Create company if needed
    $companyId = null;
    if ($effectiveType === 'company') {
      $company = ClientCompany::create([
        'tenant_id'    => $tenant->id,
        'client_type'  => 'company',
        'company_name' => $data['company_name'],
        'industry'     => $data['industry'] ?? null,
        'website'      => $data['website'] ?? null,
        'phone'        => $data['phone'] ?? null,
      ]);
      $companyId = $company->id;
    } else {
      $displayName = trim(($data['contact_first_name'] ?? '') . ' ' . ($data['contact_last_name'] ?? ''));
      $company = ClientCompany::create([
        'tenant_id'    => $tenant->id,
        'client_type'  => 'person',
        'company_name' => $displayName !== '' ? $displayName : 'Client',
        'industry'     => $data['industry'] ?? null,
        'website'      => $data['website'] ?? null,
        'phone'        => $data['phone'] ?? null,
      ]);
      $companyId = $company->id;
    }

    // 5) Create primary contact (always)
    Contact::create([
      'tenant_id'         => $tenant->id,
      'client_company_id' => $companyId,
      'firstName'         => $data['contact_first_name'],
      'lastName'          => $data['contact_last_name'],
      'email'             => $data['contact_email'],
      'phone'             => $data['contact_phone'],
      'status'            => 'active',
    ]);

    return redirect()->route('onboarding.project');
  }

  // STEP 5: First project
  public function project()
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    $contacts = Contact::where('tenant_id', $tenant->id)->with('company')->orderBy('firstName')->get();

    if ($contacts->isEmpty()) {
      return redirect()
        ->route('onboarding.client')
        ->with('status', 'Add a client contact first — projects must be linked to a client.');
    }

    return view('onboarding.project', [
      'tenant' => $tenant,
      'contacts' => $contacts,
    ]);
  }

  public function storeProject(Request $request)
  {
    $tenant = $this->currentTenant();
    if (! $tenant) {
      abort(403);
    }

    $data = $request->validate([
      'project_name' => ['required', 'string', 'max:255'],
      'contact_id'  => [
        'required',
        'integer',
        Rule::exists('contacts', 'id')->where('tenant_id', $tenant->id),
      ],
      'description' => ['nullable', 'string'],
    ]);

    $contact = Contact::where('tenant_id', $tenant->id)->findOrFail($data['contact_id']);
    $companyId = $contact->client_company_id;

    Project::create([
      'tenant_id'        => $tenant->id,
      'contact_id'       => $data['contact_id'],
      'client_company_id'=> $companyId,
      'project_name'     => $data['project_name'],
      'status'           => 'open',
      'description'      => $data['description'] ?? null,
    ]);

    return redirect()->route('onboarding.team');
  }

  // STEP 6: Team (light touch)
  public function team()
  {
    $tenant = $this->currentTenant();
    $user   = Auth::guard('admin')->user();

    $isTrial = false;
    if ($tenant) {
      $isTrial = ($tenant->subscription_status === 'trialing')
        || ($tenant->trial_ends_at && now()->lt($tenant->trial_ends_at));
    }

    return view('onboarding.team', [
      'tenant' => $tenant,
      'user' => $user,
      'isTrial' => $isTrial,
    ]);
  }

  public function storeTeam(Request $request)
  {
    $tenant = $this->currentTenant();
    $isTrial = ($tenant->subscription_status === 'trialing')
      || ($tenant->trial_ends_at && now()->lt($tenant->trial_ends_at));

    // If trialing, skip sending invites; optionally store for later
    if ($isTrial) {
      // Optionally, store pending invite for later use
      if ($request->filled('email')) {
        // placeholder for persistence if desired, e.g., session or a future table
      }
      return redirect()->route('onboarding.finish');
    }

    // Non-trial: invitation logic could be added here. For now, just continue.
    return redirect()->route('onboarding.finish');
  }

  // STEP 7: Finish
  public function finish()
  {
    $tenant = $this->currentTenant();
    if ($tenant && ! $tenant->onboarding_completed_at) {
      $tenant->onboarding_completed_at = now();
      $tenant->save();
    }

    $adminUser = Auth::guard('admin')->user();
    $routeName = $adminUser
      ? 'admin.dashboard'
      : (Auth::check() ? 'dashboard' : 'login');

    return redirect()
      ->route($routeName)
      ->with('status', 'Welcome to Renlo — your workspace is ready.');
  }
}
