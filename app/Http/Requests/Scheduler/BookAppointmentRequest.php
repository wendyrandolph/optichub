<?php

namespace App\Http\Requests\Scheduler;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Booking is a public action, no authentication required
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'client_name' => ['required', 'string', 'max:255'],
      'client_email' => ['required', 'email', 'max:255'],
      'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
      // time_slot needs to be a valid time (e.g., 09:00:00)
      'time_slot' => ['required', 'date_format:H:i:s'],
      // date must be in Y-m-d format and cannot be in the past
      'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
      // Note: Validation for whether the slot is actually available is handled 
      // by the business logic in the ScheduleService, not here.
    ];
  }
}
