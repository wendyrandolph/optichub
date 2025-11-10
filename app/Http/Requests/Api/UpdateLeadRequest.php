<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the PUT/PATCH request.
   */
  public function rules(): array
  {
    return [
      // Using 'sometimes' means these fields are optional, but if present, they must meet the rules.
      'name' => ['sometimes', 'required', 'string', 'max:255'],
      'email' => ['sometimes', 'nullable', 'email', 'max:255'],
      'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
      'source' => ['sometimes', 'nullable', 'string', 'max:100'],
      'notes' => ['sometimes', 'nullable', 'string'],
      'status' => ['sometimes', 'nullable', 'string', 'in:new,contacted,interested,client,closed,lost']
    ];
  }
}
