<?php

namespace App\Http\Requests\TaskTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskTemplateRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Since this is a simple internal tool, we rely on 'auth' middleware
    // to ensure the user is logged in.
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // Title is required and must be a string
      'title' => ['required', 'string', 'max:255'],

      // Description is required and must be a string
      'description' => ['required', 'string'],
    ];
  }
}
