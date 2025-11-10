<?php

namespace App\Services;

use App\Models\Organization; // Assuming this model exists
use App\Models\User;         // Assuming this model exists
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class TenantAccessService
{
  // States that permit access to the application
  public const PASS_STATES = ['active', 'beta', 'trialing', 'unknown'];

  protected Organization $organizationModel;
  protected User $userModel;

  /**
   * Inject Models for testability.
   */
  public function __construct(Organization $organizationModel, User $userModel)
  {
    $this->organizationModel = $organizationModel;
    $this->userModel = $userModel;
  }

  /**
   * Checks if the current tenant has active access.
   * This is the primary gate check method.
   */
  public function hasAccess(?int $tenantId = null): bool
  {
    $status = $this->getStatus($tenantId);
    return in_array($status['state'], self::PASS_STATES, true);
  }

  /**
   * Determines the current access status for the authenticated tenant.
   * Replaces TenantAccess::status().
   *
   * @param int|null $tenantId Explicit Tenant ID (optional, defaults to Auth::user()->tenant_id).
   * @return array{state: string, days_left: int|null}
   */
  public function getStatus(?int $tenantId = null): array
  {
    // 1. Determine Tenant ID (Assumes tenant_id is on the User model)
    if (!$tenantId && Auth::check() && property_exists(Auth::user(), 'tenant_id')) {
      $tenantId = Auth::user()->tenant_id;
    }

    if (!$tenantId) {
      // Check for anonymous or non-tenant user (e.g., system admin, or guest)
      return ['state' => Auth::check() ? 'anon' : 'unknown', 'days_left' => null];
    }

    // 2. Retrieve Organization data using Eloquent
    // Assumes Organization model handles casting for dates automatically
    $org = $this->organizationModel->find($tenantId);

    if (!$org) {
      return ['state' => 'unknown', 'days_left' => null];
    }

    // 3. Get Status Components
    $userIsBeta = $this->checkUserIsBeta();

    $now = Carbon::now();
    // Use optional helper for graceful date retrieval
    $trialEnds = optional($org->trial_ends_at)->endOfDay();
    $betaUntil = optional($org->beta_until)->endOfDay();
    $subscription = strtolower(trim((string)$org->subscription_status));


    // BETA check precedence
    if ($userIsBeta || ($betaUntil && $now->lessThanOrEqualTo($betaUntil))) {
      // Check days left only if beta has an end date
      $daysLeft = $betaUntil ? max(0, $now->diffInDays($betaUntil, false)) : null;
      return [
        'state'     => 'beta',
        'days_left' => $daysLeft,
      ];
    }

    // ACTIVE check
    if ($subscription === 'active') {
      return ['state' => 'active', 'days_left' => null];
    }

    // TRIAL check
    if ($trialEnds && $now->lessThanOrEqualTo($trialEnds)) {
      $daysLeft = max(0, $now->diffInDays($trialEnds, false));
      return ['state' => 'trialing', 'days_left' => $daysLeft];
    }

    // EXPIRED
    return ['state' => 'expired', 'days_left' => 0];
  }

  /**
   * Checks if the currently authenticated user has a 'beta' flag.
   * Replaces TenantAccess::userIsBeta()
   */
  protected function checkUserIsBeta(): bool
  {
    if (!Auth::check()) {
      return false;
    }

    // Assumes 'is_beta' column exists on the User model
    return (bool)Auth::user()->is_beta;
  }
}
