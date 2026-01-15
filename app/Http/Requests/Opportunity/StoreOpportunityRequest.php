<?php

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Rely on the controller's policy check for 'create' permission
    return true;
  }

  /**
   * Get the validation rules that apply to the POST request.
   */
  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'stage' => ['required', 'string', 'in:new,qualified,proposal,negotiation,won,lost'],
      'estimated_value' => ['nullable', 'numeric', 'min:0'],
      'expected_close_date' => ['nullable', 'date'],
      'probability' => ['nullable', 'integer', 'between:0,100'],
      'owner_id' => ['nullable', 'exists:users,id'],
      'lead_id' => ['required', 'exists:leads,id'],
      'company_id' => ['nullable', 'exists:client_companies,id'],
      'notes' => ['nullable', 'string'],
      'next_step' => ['nullable', 'string'],
      'next_followup_at' => ['nullable', 'date', 'required_unless:stage,won,lost'],
      'lost_reason' => ['nullable', 'string', 'required_if:stage,lost'],
      'create_followup_task' => ['sometimes', 'boolean'],
      'add_activity_note' => ['sometimes', 'boolean'],
    ];
  }
}
