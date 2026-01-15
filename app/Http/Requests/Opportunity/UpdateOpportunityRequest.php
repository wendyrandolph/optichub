<?php

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Rely on the controller's policy check for 'update' permission
    return true;
  }

  /**
   * Get the validation rules that apply to the PUT/PATCH request.
   */
  public function rules(): array
  {
    return [
      'title' => ['sometimes', 'required', 'string', 'max:255'],
      'stage' => ['sometimes', 'required', 'string', 'in:new,qualified,proposal,negotiation,won,lost'],
      'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'expected_close_date' => ['sometimes', 'nullable', 'date'],
      'probability' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
      'owner_id' => ['sometimes', 'nullable', 'exists:users,id'],
      'lead_id' => ['sometimes', 'required', 'exists:leads,id'],
      'company_id' => ['sometimes', 'nullable', 'exists:client_companies,id'],
      'notes' => ['sometimes', 'nullable', 'string'],
      'next_step' => ['sometimes', 'nullable', 'string'],
      'next_followup_at' => ['sometimes', 'nullable', 'date', 'required_unless:stage,won,lost'],
      'lost_reason' => ['sometimes', 'nullable', 'string', 'required_if:stage,lost'],
      'create_followup_task' => ['sometimes', 'boolean'],
      'add_activity_note' => ['sometimes', 'boolean'],
    ];
  }
}
