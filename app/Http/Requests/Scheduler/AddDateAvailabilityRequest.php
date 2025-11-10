<?php

namespace App\Http\Requests\Scheduler;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddDateAvailabilityRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Only authenticated users (staff members) can add overrides
    return Auth::check();
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // available_date must be today or later and in Y-m-d format
      'available_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
      'start_time' => ['required', 'date_format:H:i:s'],
      // end_time must be a valid time format and must occur after the start time
      'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
    ];
  }
}
