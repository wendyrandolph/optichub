<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
  public static function log(Model $subject, string $action, ?string $description = null, array $props = []): ActivityLog
  {
    $user   = Auth::user();
    $tenant = $user?->tenant_id;

    return ActivityLog::record(
      tenantId: (int) $tenant,
      userId: $user?->id,
      subject: $subject,
      action: $action,
      description: $description,
      properties: $props + ['ip' => request()->ip()]
    );
  }
}
