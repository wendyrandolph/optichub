<?php
// app/Policies/ReportPolicy.php
namespace App\Policies;

use App\Models\User;
use Illuminate\View\View;
use App\Models\Tenant;
use App\Models\Lead;

class LeadInsightPolicy
{
  public function viewAny(User $user): bool
  {
    return in_array(strtolower((string)$user->role), [
      'provider',
      'super_admin',
      'admin'
    ], true);
  }
  public function view(User $user, Lead $lead): bool
  {
    return (int)$user->tenant_id === (int)$lead->tenant_id;
  }
}
