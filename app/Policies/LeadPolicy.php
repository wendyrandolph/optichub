<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lead;

class LeadPolicy
{
  public function before(User $user, string $ability): ?bool
  {
    $role = strtolower((string) $user->role);
    $org  = strtolower((string) $user->organization_type);

    if (
      in_array($role, ['admin', 'provider'], true) &&
      in_array($org, ['provider', 'saas_tenant'], true)
    ) {
      return true;
    }

    return null;
  }

  public function viewAny(User $user): bool
  {
    $role = strtolower((string) $user->role);

    return in_array($role, [
      'admin',
      'super_admin',
      'superadmin',
      'tenant_admin',
      'provider',
      'employee',
    ], true);
  }

  public function view(User $user, Lead $lead): bool
  {
    return (int) $user->tenant_id === (int) $lead->tenant_id;
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function update(User $user, Lead $lead): bool
  {
    return (int) $user->tenant_id === (int) $lead->tenant_id;
  }

  public function delete(User $user, Lead $lead): bool
  {
    return (int) $user->tenant_id === (int) $lead->tenant_id;
  }
}
