<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantProvisioner;

class TrialController extends Controller
{
  public function showTrialForm(Request $request)
  {
    $type = $request->query('type');
    $allowed = ['creative', 'trades'];
    $workspaceType = in_array($type, $allowed) ? $type : 'creative';

    return view('marketing.trial', [
      'workspaceType' => $workspaceType,
      'leadSource' => $request->query('type'),
    ]);
  }

  public function start(Request $request)
  {
    $data = $request->validate([
      'company_name' => ['required', 'string', 'max:255'],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255', 'unique:users,email'],
      'password' => ['required', 'min:8'],
      'workspace_type' => ['nullable', 'in:creative,trades'],
      'lead_source' => ['nullable', 'string', 'max:255'],
    ]);

    // 1) Create tenant
    $tenant = Tenant::create([
      'name' => $data['company_name'],
      'subscription_status' => 'trialing',
      'trial_ends_at' => now()->addDays(14),
      'primary_color' => '#1C2E70',
      'secondary_color' => '#8FAF9A',
      'accent_color' => '#9A8FBF',
      'workspace_type' => $data['workspace_type'] ?? 'creative',
    ]);

    // 2) Create the admin user for this tenant
    $user = User::create([
      'tenant_id' => $tenant->id,
      'first_name' => $data['first_name'],
      'last_name' => $data['last_name'],
      'email' => $data['email'],
      'role' => 'admin', // matches your ENUM
      'password' => Hash::make($data['password']),
    ]);

    // 3) Log them in with the admin guard
    Auth::guard('admin')->login($user);

    if (($data['workspace_type'] ?? 'creative') === 'trades') {
      app(TenantProvisioner::class)->applyTradesDefaults($tenant);
    }

    // Optional lead source handling
    if (!empty($data['lead_source'])) {
      $tenant->settings()->updateOrCreate(
        ['key' => 'lead_source'],
        ['value' => $data['lead_source']]
      );
    }

    // 4) Redirect to onboarding
    return redirect()->route('onboarding.welcome');
  }
}
