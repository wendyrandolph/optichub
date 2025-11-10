<?php
// app/Policies/ReportPolicy.php
namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy
{
  public function viewAny(User $user): bool
  {
    return in_array(strtolower((string)$user->role), [
      'provider',
      'super_admin',
      'admin'
    ], true);
  }
}
