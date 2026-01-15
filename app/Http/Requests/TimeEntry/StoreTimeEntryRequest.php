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
      'user_id' => ['required', 'exists:users,id'],
      'project_id' => ['required', 'exists:projects,id'],
      'task_id' => ['nullable', 'exists:tasks,id'],
      'date' => ['nullable', 'date'],
      'hours' => ['required', 'numeric', 'gt:0'],
      'hourly_rate' => ['nullable', 'numeric', 'min:0'],
      'notes' => ['nullable', 'string', 'max:500'],
      'billable' => ['nullable', 'boolean'],
    ];
  }
}
