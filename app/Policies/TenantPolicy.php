<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Tenant;

class TenantPolicy
{
  use HandlesAuthorization;
  public function viewAny(User $user): bool
  {
    $role = strtolower($user->role ?? '');

    return in_array($role, [
      'admin',
      'super_admin',
      'superadmin',
      'tenant_admin',
      // add this:
      'provider',
    ], true);
  }

  public function view(User $user, Tenant $organization): bool
  {
    return in_array($user->role, ['provider', 'admin', 'super_admin', 'superadmin']);
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
  }
}
