<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder Service to handle complex subscription/billing logic.
 * Assumes the Subscription model exists.
 */
class SubscriptionService
{
  /**
   * Initializes a trial subscription for a given tenant.
   */
  public function startTrial(Tenant $tenant, string $plan, int $days): Subscription
  {
    Log::info("Starting {$days}-day trial for Tenant #{$tenant->id} on plan: {$plan}");

    // Assumes a Subscription model exists to record this information
    $subscription = Subscription::create([
      'tenant_id' => $tenant->id,
      'plan_name' => $plan,
      'status' => 'trialing',
      'trial_ends_at' => now()->addDays($days),
      'current_period_end' => now()->addDays($days),
    ]);

    return $subscription;
  }
}
