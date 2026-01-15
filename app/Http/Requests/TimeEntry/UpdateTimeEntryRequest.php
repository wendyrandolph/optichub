<?php

namespace App\Http\Requests\TimeEntry;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // We rely on the controller's update method to authorize the user 
    // against the specific TimeEntry model instance.
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // Use 'sometimes' because not every field needs to be updated in a PATCH request
      'user_id' => ['sometimes', 'required', 'exists:users,id'],
      'project_id' => ['sometimes', 'required', 'exists:projects,id'],
      'task_id' => ['sometimes', 'nullable', 'exists:tasks,id'],
      'date' => ['sometimes', 'date'],
      'hours' => ['sometimes', 'numeric', 'gt:0'],
      'hourly_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'notes' => ['nullable', 'string', 'max:500'],
      'start_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
      'end_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after:start_time'],
      'billable' => ['sometimes', 'nullable', 'boolean'],
    ];
  }
}
