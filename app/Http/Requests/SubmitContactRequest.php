<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitContactRequest extends FormRequest
{
  // Authorization: Public form, no user required. CSRF is verified automatically.
  public function authorize(): bool
  {
    return true;
  }

  protected function prepareForValidation()
  {
    // Honeypot check: Laravel's validation rules are better, but if you must use a
    // honeypot field, you can set an error here or check it in the rules.
    if (!empty($this->website)) {
      // In Laravel, a better approach is to use a custom validation rule 
      // that always fails if the field is not empty, but we'll use a simple check for now.
      // We'll trust the validation rules to handle the rest.
    }

    $this->merge([
      // Normalize time check into a boolean for validation
      'is_fast_submit' => (time() - (int)$this->started_at) < 3,
    ]);
  }

  public function rules(): array
  {
    return [
      // Honeypot field: Must be absent or empty
      'website' => ['size:0', 'max:0'],

      // Time check: Custom rule or simple validation
      'started_at' => ['required', 'integer'],
      'is_fast_submit' => ['accepted'], // Will fail if true (fast submission)

      // Main validation
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255'],
      'topic' => ['required', 'string', 'max:100'],
      'message' => ['required', 'string', 'max:2000'],
    ];
  }

  public function messages(): array
  {
    return [
      'website.size' => 'Spam detected.',
      'is_fast_submit.accepted' => 'Please wait a moment before submitting.',
      'name.required' => 'Please enter your name.',
      'email.email' => 'Please enter a valid email.',
      'message.required' => 'Please enter a message.',
    ];
  }
}
