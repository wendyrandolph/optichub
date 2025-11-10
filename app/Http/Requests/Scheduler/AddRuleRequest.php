<?php

namespace App\Http\Requests\Scheduler;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddRuleRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Only authenticated users (staff members) can add rules
    return Auth::check();
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // day_of_week: 0=Sunday, 6=Saturday
      'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
      'start_time' => ['required', 'date_format:H:i:s'],
      // end_time must be a valid time format and must occur after the start time on the same day
      'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
    ];
  }
}
