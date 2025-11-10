<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a confirmed appointment booking made by a client.
 */
class Appointment extends Model
{
  use HasFactory;

  protected $fillable = [
    'client_name',
    'client_email',
    'day_of_week', // numeric 0-6
    'date',
    'time',
    'is_confirmed',
  ];

  /**
   * Casts to ensure dates/times are handled correctly by the database.
   */
  protected $casts = [
    'date' => 'date',
    'time' => 'datetime',
    'is_confirmed' => 'boolean',
  ];

  /**
   * Relationship to the User (staff member) who owns this appointment.
   * Assuming a staff_user_id column exists.
   */
  public function staffUser()
  {
    return $this->belongsTo(User::class, 'staff_user_id');
  }
}
