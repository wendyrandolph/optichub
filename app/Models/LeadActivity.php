<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Eloquent Model for the 'lead_activities' table.
 * Security is guaranteed by the parent Lead record, which is assumed to be tenant-scoped.
 */
class LeadActivity extends Model
{
  use HasFactory;
  // NOTE: HasTenantScope is NOT needed here. Security is delegated to the parent Lead.

  protected $table = 'lead_activities';

  // Typically, activity records benefit from timestamps
  public $timestamps = true;

  protected $fillable = [
    'lead_id',
    'activity_type',
    'description',
  ];

  /**
   * The attributes that should be cast to native types.
   */
  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  // --- Relationships ---

  /**
   * A lead activity belongs to a Lead.
   */
  public function lead(): BelongsTo
  {
    // Assumes a Lead model exists and has HasTenantScope enabled.
    return $this->belongsTo(Lead::class);
  }

  // --- Core CRUD & Retrieval Refactors ---

  /**
   * Creates a new lead activity entry.
   * Replaces the procedural create() method.
   *
   * @param int $leadId The ID of the lead this activity belongs to.
   * @param string $activityType The type of activity.
   * @param string $description A description of the activity.
   * @return self The newly created LeadActivity model instance.
   * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the associated lead is not accessible.
   */
  public static function createActivity(int $leadId, string $activityType, string $description): self
  {
    // 1. Security Check: Find the parent Lead. 
    // findOrFail() ensures the lead exists AND is visible to the current tenant.
    $lead = Lead::findOrFail($leadId);

    // 2. Creation: Eloquent handles the INSERT securely via the relationship.
    $activity = new self([
      'lead_id' => $leadId,
      'activity_type' => $activityType,
      'description' => $description,
    ]);

    // The relationship method is robust, but for simplicity with static methods, 
    // we can create directly, knowing the leadId is already validated.
    $activity->save();

    return $activity;
  }

  /**
   * Retrieves activities for a given lead ID.
   * Replaces the procedural getActivitiesByLead().
   *
   * @param int $leadId The ID of the lead to retrieve activities for.
   * @return \Illuminate\Database\Eloquent\Collection An ordered collection of lead activities.
   */
  public static function getActivitiesByLeadId(int $leadId)
  {
    // 1. Security Check: Find the parent Lead (tenant-scoped).
    $lead = Lead::find($leadId);

    if (!$lead) {
      // Lead is not found or not accessible to the current tenant.
      return collect([]); // Return an empty collection
    }

    // 2. Retrieval: Use the relationship to fetch the activities securely, ordered by creation date.
    return $lead->activities()->orderByDesc('created_at')->get();
  }

  /**
   * Deletes a single lead activity by its ID.
   * Ensures the associated lead is accessible to the current user before deletion.
   *
   * @param int $id The ID of the lead activity to delete.
   * @return bool True on successful deletion, false if not found.
   * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the associated lead is not accessible.
   */
  public static function deleteActivity(int $id): bool
  {
    // 1. Find the activity and load its lead relationship
    $activity = static::with('lead')->find($id);

    if (!$activity) {
      return false; // Activity not found
    }

    // 2. Security Check: Check if the loaded parent lead is null (meaning it's inaccessible).
    if (!$activity->lead) {
      throw new ModelNotFoundException(
        "Cannot delete activity: Associated lead not found or not accessible."
      );
    }

    // 3. Deletion. Access to the parent Lead has been validated.
    return $activity->delete();
  }
}
