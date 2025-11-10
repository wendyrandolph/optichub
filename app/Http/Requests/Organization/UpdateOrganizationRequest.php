<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the PUT/PATCH request.
   */
  public function rules(): array
  {
    // Use 'sometimes' as fields are optional on update
    return [
      'name' => ['sometimes', 'required', 'string', 'max:255'],
      'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
      'location' => ['sometimes', 'nullable', 'string', 'max:255'],
      'website' => ['sometimes', 'nullable', 'url', 'max:255'],
      'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
      'notes' => ['sometimes', 'nullable', 'string'],
    ];
  }
}
