<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;

class TeamMemberPolicy
{
  public function before(User $user, $ability)
  {
    if (in_array(strtolower($user->role), ['super_admin', 'superadmin', 'provider'])) {
      return true;
    }
    return null;
  }

  public function viewAny(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function view(User $user, TeamMember $member): bool
  {
    return $user->tenant_id === $member->tenant_id;
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function update(User $user, TeamMember $member): bool
  {
    return $user->tenant_id === $member->tenant_id;
  }

  public function delete(User $user, TeamMember $member): bool
  {
    return $user->tenant_id === $member->tenant_id;
  }
}
