<?php

// app/Policies/OpportunityPolicy.php
namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
  public function before(User $user, string $ability): bool|null
  {
    return in_array(strtolower((string)$user->role), ['super_admin', 'superadmin'], true) ? true : null;
  }

  public function viewAny(User $user): bool
  {
    \Log::debug('OppPolicy@viewAny', ['uid' => $user->id, 'tenant_id' => $user->tenant_id]);
    return !empty($user->tenant_id);
  }

  public function view(User $user, Opportunity $opp): bool
  {
    return $user->tenant_id === $opp->tenant_id;
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function update(User $user, Opportunity $opp): bool
  {
    return $user->tenant_id === $opp->tenant_id;
  }

  public function delete(User $user, Opportunity $opp): bool
  {
    return $user->tenant_id === $opp->tenant_id;
  }
}
