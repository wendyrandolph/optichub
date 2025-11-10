<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
  public function before(User $user, $ability)
  {
    return in_array(strtolower((string)$user->role), ['super_admin', 'superadmin', 'provider'], true) ? true : null;
  }

  public function viewAny(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function view(User $user, invoice $invoice): bool
  {
    return $user->tenant_id === $invoice->tenant_id;
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
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
