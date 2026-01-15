<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Tenant;

class ClientPolicy
{
  public function before(User $user, $ability)
  {
    return in_array(strtolower((string)$user->role), ['super_admin', 'superadmin', 'provider'], true) ? true : null;
  }
  public function viewAny($user): bool
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


  public function view(User $user, Contact|Client $contact): bool
  {
    return $user->tenant_id === $contact->tenant_id;
  }

  public function create(User $user): bool
  {
    return !empty($user->tenant_id);
  }

  public function update(User $user, Contact|Client $contact): bool
  {
    return $user->tenant_id === $contact->tenant_id;
  }

  public function delete(User $user, Contact|Client $contact): bool
  {
    return $user->tenant_id === $contact->tenant_id;
  }
}
