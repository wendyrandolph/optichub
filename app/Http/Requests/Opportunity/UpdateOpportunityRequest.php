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
      // Use 'sometimes' so fields are optional, but if present, they must be valid
      'name' => ['sometimes', 'required', 'string', 'max:255'],
      'organization_id' => ['sometimes', 'required', 'exists:organizations,id'],
      'status' => ['sometimes', 'required', 'string', 'in:new,qualified,proposal,won,lost'],
      'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'close_date' => ['sometimes', 'nullable', 'date'],
      'notes' => ['sometimes', 'nullable', 'string'],
    ];
  }
}
