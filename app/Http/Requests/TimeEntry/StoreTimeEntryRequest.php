<?php

namespace App\Http\Requests\TimeEntry;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Typically, any authenticated user can create a time entry.
    // You might add a check here, e.g., return $this->user()->can('create', TimeEntry::class);
    return auth()->check();
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // Ensure necessary IDs exist in their respective tables
      'user_id' => ['required', 'exists:users,id'],
      'project_id' => ['required', 'exists:projects,id'],
      'task_id' => ['required', 'exists:tasks,id'],
      'notes' => ['nullable', 'string', 'max:500'],

      // Time fields must be present and follow a datetime format
      'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
      'end_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time'],

      // Optional: If you track billable status
      'is_billable' => ['nullable', 'boolean'],
    ];
  }
}
