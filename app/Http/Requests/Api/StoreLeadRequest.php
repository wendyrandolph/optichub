<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   * Authorization is handled by the 'scope:leads:write' middleware applied in the controller.
   */
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
      // 'name' is required for creation, replacing the old manual check
      'name' => ['required', 'string', 'max:255'],

      // Email validation replaces filter_var
      'email' => ['nullable', 'email', 'max:255'],

      // Other fields, ensuring they are strings and checking max length
      'phone' => ['nullable', 'string', 'max:50'],
      'source' => ['nullable', 'string', 'max:100'],
      'notes' => ['nullable', 'string'],

      // Assuming 'status' must be one of a predefined set
      'status' => ['nullable', 'string', 'in:new,contacted,interested,client,closed,lost']
    ];
  }
}
