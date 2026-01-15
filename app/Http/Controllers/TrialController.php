<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantProvisioner;

class TrialController extends Controller
{
  public function showTrialForm()
  {
    return view('marketing.trial');
  }

  public function start(Request $request)
  {
    $data = $request->validate([
      'company_name' => ['required', 'string', 'max:255'],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255', 'unique:users,email'],
      'password' => ['required', 'min:8'],
      'username' => ['nullable', 'string', 'max:50', 'unique:users,username'],
      'workspace_type' => ['nullable', 'in:creative,trades'],
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

    // 2) Prepare username (sanitize + ensure unique)
    $baseUsername = $data['username'] ?: Str::before($data['email'], '@') ?: 'owner';
    $baseUsername = preg_replace('/[^a-z0-9_.-]+/i', '-', $baseUsername);
    $baseUsername = trim($baseUsername, '-_.');
    if ($baseUsername === '') {
      $baseUsername = 'owner';
    }

    $username = $baseUsername;
    $counter = 1;

    while (User::where('username', $username)->exists()) {
      $username = $baseUsername . '-' . $counter;
      $counter++;
    }

    // 3) Create the admin user for this tenant
    $user = User::create([
      'tenant_id' => $tenant->id,
      'first_name' => $data['first_name'],
      'last_name' => $data['last_name'],
      'email' => $data['email'],
      'role' => 'admin', // matches your ENUM
      'username' => $username,
      'password' => Hash::make($data['password']),
    ]);

    // 4) Log them in with the admin guard
    Auth::guard('admin')->login($user);

    if (($data['workspace_type'] ?? 'creative') === 'trades') {
      app(TenantProvisioner::class)->applyTradesDefaults($tenant);
    }

    // 5) Redirect to onboarding
    return redirect()->route('onboarding.welcome');
  }
}
