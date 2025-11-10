<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class LeadPolicy
{
  public function before(User $user, string $ability): ?bool
  {
    // normalize just in case
    $role = strtolower((string) $user->role);
    $org  = strtolower((string) $user->organization_type);

    if (
      in_array($role, ['admin', 'provider'], true)   // ← include provider
      && in_array($org, ['provider', 'saas_tenant'], true)
    ) {
      return true;
    }
    return null;
  }

  public function viewAny(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function view(User $user, Lead $lead): bool
  {
    return (int)$user->tenant_id === (int)$lead->tenant_id;
  }
  public function create(User $user): bool
  {
    $role = strtolower((string) $user->role);
    $org  = strtolower((string) $user->organization_type);

    return in_array($org, ['provider', 'saas_tenant'], true)
      && in_array($role, ['admin', 'employee', 'provider'], true); // ← include provider
  }

  public function update(User $user, invoice $invoice): bool
  {
    return $user->tenant_id === $invoice->tenant_id;
  }

  public function delete(User $user, invoice $invoice): bool
  {
    return $user->tenant_id === $invoice->tenant_id;
  }
}
