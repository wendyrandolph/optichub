<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a one-time date availability or unavailability override
 * set by a staff member, overriding the recurring WeeklyRule.
 */
class DateOverride extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',       // The staff member who set the override
    'date',
    'start_time',    // e.g., '09:00:00'
    'end_time',      // e.g., '17:00:00'
    // You might also include a 'type' if you distinguish between
    // 'available' and 'unavailable' overrides, but we assume 'available' here.
  ];

  /**
   * Casts to ensure dates/times are handled correctly by the database.
   */
  protected $casts = [
    'date' => 'date',
  ];

  /**
   * Relationship to the User (staff member) who owns this override.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
