<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLoggerService
{
  /**
   * Logs an activity event associated with a user and a related model.
   *
   * @param string $relatedType The model class name (e.g., App\Models\Project::class).
   * @param int $relatedId The ID of the related model instance.
   * @param string $action A short verb describing the action (e.g., 'created', 'updated', 'deleted').
   * @param string $message An optional detailed message about the activity.
   */
  public function log(string $relatedType, int $relatedId, string $action, string $message = ''): void
  {
    // 1. Safely retrieve the logged-in user and ID using Laravel's Auth facade.
    $user = Auth::user();
    $userId = Auth::id();

    // If no user is logged in, we cannot log the activity (or handle guests differently if needed).
    if (!$userId) {
      // Optionally log a warning here if a log attempt was made without a user
      return;
    }

    // 2. Use the Eloquent model to create the record, replacing the old procedural logic.
    ActivityLog::create([
      'related_type' => $relatedType,
      'related_id' => $relatedId,
      'action' => $action,
      'user_id' => $userId,
      // Get role directly from the user object (assuming it's a property/attribute)
      'role' => $user->role ?? 'unknown',
      'message' => $message,
    ]);
  }
}
