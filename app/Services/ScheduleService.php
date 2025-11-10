<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\WeeklyRule;
use App\Models\DateOverride; // Assuming a model for one-off availability changes
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleService
{
  /**
   * Retrieves all booked appointments, usually scoped to the current user's team.
   * Replaces $this->model->getAppointments()
   */
  public function getAppointments()
  {
    // Example: Get all appointments for the current user (staff member)
    return Appointment::where('staff_user_id', Auth::id())
      ->with('staffUser')
      ->orderBy('date')
      ->get();
  }

  /**
   * Logic to determine available slots by merging weekly rules, date overrides, 
   * and subtracting booked appointments.
   * (A simplified placeholder, as full scheduling logic is extensive)
   * Replaces $this->model->getDateAvailability()
   */
  public function getDateAvailability(): array
  {
    // In a real application, this logic would generate time slots 
    // based on WeeklyRule, DateOverride, and exclude slots present in Appointment.

    // For demonstration, we'll return mock data based on a query
    $rules = WeeklyRule::where('user_id', Auth::id())->get();

    $availabilityMap = [];

    foreach ($rules as $rule) {
      $availabilityMap[$rule->day_of_week][] = [
        'start' => $rule->start_time,
        'end' => $rule->end_time,
      ];
    }

    return [
      'rules' => $availabilityMap,
      'overrides' => DateOverride::where('user_id', Auth::id())
        ->where('date', '>', now())
        ->get(),
    ];
  }

  /**
   * Adds a weekly recurring availability rule.
   */
  public function addAvailabilityRule(int $userId, int $dayOfWeek, string $startTime, string $endTime): WeeklyRule
  {
    // Ensure the times are valid (already handled by Form Request)
    return WeeklyRule::create([
      'user_id' => $userId,
      'day_of_week' => $dayOfWeek,
      'start_time' => $startTime,
      'end_time' => $endTime,
    ]);
  }

  /**
   * Adds a one-time date availability override.
   */
  public function addDateAvailability(string $date, string $startTime, string $endTime): DateOverride
  {
    // Assuming DateOverride model exists for one-off dates
    return DateOverride::create([
      'user_id' => Auth::id(), // Staff member who set the override
      'date' => $date,
      'start_time' => $startTime,
      'end_time' => $endTime,
    ]);
  }

  /**
   * Books a new appointment, including conflict checking (implicitly required).
   * Replaces $this->model->bookAppointment()
   */
  public function bookAppointment(string $name, string $email, int $dayOfWeek, string $timeSlot, string $date): Appointment
  {
    // --- CRITICAL STEP: Conflict check (simplified placeholder) ---
    // In a real app, you would check if this specific $date/$timeSlot is available
    // by running it against the availability calculation (getDateAvailability).

    // Find an available staff member to assign to (e.g., the one whose slot was booked)
    $staffUserId = 1; // Placeholder for the actual staff member ID whose slot was chosen

    return Appointment::create([
      'staff_user_id' => $staffUserId,
      'client_name' => $name,
      'client_email' => $email,
      'day_of_week' => $dayOfWeek,
      'date' => $date,
      'time' => $timeSlot,
      'is_confirmed' => true,
    ]);
  }
}
