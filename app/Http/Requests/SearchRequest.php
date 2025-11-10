<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Assuming search is available to any authenticated user
    return auth()->check();
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // Ensure the query ('q') is present after trimming and is a string
      'q' => ['required', 'string', 'min:2'],
    ];
  }

  /**
   * Prepare the data for validation.
   * Replaces the manual trim($_GET['q'])
   */
  protected function prepareForValidation()
  {
    // Trim the query input before validation
    $this->merge([
      'q' => trim($this->input('q', '')),
    ]);
  }

  /**
   * Custom error messages.
   */
  public function messages()
  {
    // Replaces the $_SESSION['error_message'] logic
    return [
      'q.required' => 'Please enter a search term.',
      'q.min' => 'Your search term must be at least 2 characters long.',
    ];
  }
}
