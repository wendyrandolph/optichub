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
      // Assuming required fields for creation
      'name' => ['required', 'string', 'max:255'],
      'organization_id' => ['required', 'exists:organizations,id'],
      'status' => ['required', 'string', 'in:new,qualified,proposal,won,lost'],
      'amount' => ['nullable', 'numeric', 'min:0'],
      'close_date' => ['nullable', 'date'],
      'notes' => ['nullable', 'string'],
    ];
  }
}
