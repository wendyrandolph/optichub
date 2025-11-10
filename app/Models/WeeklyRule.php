<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a recurring weekly availability rule set by a staff member.
 */
class WeeklyRule extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'day_of_week', // numeric 0-6 (Sun-Sat)
    'start_time',  // e.g., '09:00:00'
    'end_time',    // e.g., '17:00:00'
  ];

  // Relationship to the staff member who set the rule
  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
