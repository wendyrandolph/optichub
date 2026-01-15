<?php
// app/Services/OrganizationService.php
namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationService
{
  public function __construct(
    protected SubscriptionService $subscriptions
  ) {}

  /**
   * Create tenant + admin user + onboarding token, then start trial.
   * Return: ['organization_id','user_id','token']
   */
  public function startTrial(string $email, string $company): array
  {
    return DB::transaction(function () use ($email, $company) {
      // 1) Create Tenant (organization)
      $tenant = Tenant::create([
        'name' => $company,
        'slug' => Str::slug($company) . '-' . Str::random(6),
        // optional defaults...
        'subscription_status' => 'trialing', // if you’re keeping this column
        'trial_started_at' => now(),
        'trial_ends_at' => now()->addDays(14),
        'workspace_type' => 'creative',
      ]);

      // 2) Create the initial admin user
      $user = User::create([
        'tenant_id' => $tenant->id,
        'email' => $email,
        'role' => 'admin',
        'name' => $company . ' Admin',
        'password' => null, // will be set in setup-password step
      ]);

      // 3) Create onboarding token (however you store it)
      $token = Str::uuid()->toString();
      \App\Models\OnboardingToken::create([
        'user_id' => $user->id,
        'email' => $email,
        'token' => $token,
      ]);

      // 4) Start the Subscription trial (single source of truth)
      $this->subscriptions->startTrial($tenant, plan: 'trial', days: 14);

      return [
        'organization_id' => $tenant->id,
        'user_id' => $user->id,
        'token' => $token,
      ];
    });
  }
}
