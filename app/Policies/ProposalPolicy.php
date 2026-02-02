<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
  public function viewAny(User $user): bool
  {
    return $this->isTenantUser($user);
  }

  public function view(User $user, Proposal $proposal): bool
  {
    return $this->isTenantUser($user)
      && (int) $proposal->tenant_id === (int) $user->tenant_id;
  }

  public function create(User $user): bool
  {
    return $this->isTenantUser($user);
  }

  public function update(User $user, Proposal $proposal): bool
  {
    return $this->view($user, $proposal);
  }

  public function delete(User $user, Proposal $proposal): bool
  {
    return $this->view($user, $proposal);
  }

  protected function isTenantUser(User $user): bool
  {
    if (! $user->tenant_id) {
      return false;
    }

    return ($user->role ?? '') !== 'client';
  }
}
