<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the POST request.
   */
  public function rules(): array
  {
    return [
      'title'            => ['required', 'string', 'max:255'],
      'organization_id'  => ['nullable', 'integer', 'exists:tenants,id'], // or organizations table if you use one
      'stage'            => ['nullable', 'string', 'max:100'],
      'estimated_value'  => ['nullable', 'numeric', 'min:0'],
      'probability'      => ['nullable', 'integer', 'between:0,100'],
      'expected_revenue' => ['nullable', 'numeric', 'min:0'],
      'close_date'       => ['nullable', 'date'],
      'notes'            => ['nullable', 'string'],
    ];
  }


  /**
   * Replaces the manual trim() calls.
   */
  protected function prepareForValidation()
  {
    $this->merge([
      'name'     => trim($this->name ?? ''),
      'industry' => trim($this->industry ?? ''),
      'location' => trim($this->location ?? ''),
      'website'  => trim($this->website ?? ''),
      'phone'    => trim($this->phone ?? ''),
      'notes'    => trim($this->notes ?? ''),
    ]);
  }
}
