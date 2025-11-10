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
    return in_array($user->role, ['provider', 'admin', 'super_admin', 'superadmin'])
      // or: $user->hasAnyRole(['provider','admin','super_admin','superadmin'])
    ;
  }

  public function view(User $user, Tenant $organization): bool
  {
    return in_array($user->role, ['provider', 'admin', 'super_admin', 'superadmin']);
  }
}
