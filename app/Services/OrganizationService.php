<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OrganizationService
{
  /**
   * Handles the complex logic of starting a new user trial.
   *
   * @param string $email The email of the new user.
   * @param string $companyName The name of the organization.
   * @param array $options Optional configuration (plan, trialDays, etc.).
   * @return array Returns the organization ID and the setup token.
   * @throws Throwable
   */
  public function startTrial(string $email, string $companyName, array $options = []): array
  {
    // 1. Define configuration defaults
    $options = array_merge([
      'plan' => 'starter',
      'trialDays' => 14,
      'tokenHours' => 48,
    ], $options);

    // 2. Perform all database operations in a transaction
    return DB::transaction(function () use ($email, $companyName, $options) {

      // 3. Create the new Organization (Tenant)
      $organization = Organization::create([
        'name' => $companyName,
        'plan' => $options['plan'],
        'trial_ends_at' => now()->addDays($options['trialDays']),
        'is_active' => true,
      ]);

      // 4. Create the initial User for the organization
      $token = Str::random(64);
      $expiresAt = now()->addHours($options['tokenHours']);

      $user = User::create([
        'organization_id' => $organization->id,
        'email' => $email,
        'first_name' => 'Trial', // Default name
        'last_name' => 'Admin',  // Default name
        'role' => 'administrator',
        'setup_token' => $token,
        'token_expires_at' => $expiresAt,
        // Password will be set during onboarding/set-password route
      ]);

      // 5. Return necessary details for redirection
      return [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'token' => $token,
      ];
    });
  }
  // Note: The old placeholder setApiKey method was removed here
}
