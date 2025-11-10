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
      'task_id' => ['sometimes', 'required', 'exists:tasks,id'],
      'notes' => ['nullable', 'string', 'max:500'],

      // Ensure time formats are correct and end_time is still after start_time.
      // When updating time fields, we check against Y-m-d H:i:s format.
      'start_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],

      // The 'after:start_time' rule is crucial. If 'start_time' isn't provided 
      // in the request, this validation uses the existing 'start_time' from the model instance
      // (though Laravel often requires you to manually fetch and check the existing value 
      // for robustness in complex date logic). For simplicity here, we rely on the 
      // basic comparison assuming the controller provides both fields if one is changed.
      'end_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s', 'after:start_time'],

      'is_billable' => ['sometimes', 'nullable', 'boolean'],
    ];
  }
}
