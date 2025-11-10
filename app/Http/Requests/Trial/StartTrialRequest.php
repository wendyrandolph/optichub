<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class StartTrialRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Allow guests to access the trial route
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
   */
  public function rules(): array
  {
    return [
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
      'company' => ['required', 'string', 'max:255'],
    ];
  }

  /**
   * Prepare the data for validation.
   *
   * @return void
   */
  protected function prepareForValidation(): void
  {
    // Apply rate limiting based on the user's IP address
    $this->ensureIsNotRateLimited();
  }

  /**
   * Ensure the trial request is not rate limited.
   *
   * @throws \Illuminate\Validation\ValidationException
   */
  public function ensureIsNotRateLimited(): void
  {
    // Use a unique key for the rate limiter (e.g., ip address)
    $key = 'trial-start|' . $this->ip();

    // Allow 3 attempts every 5 minutes
    if (!RateLimiter::tooManyAttempts($key, 3, 300)) {
      // Log the attempt and proceed
      RateLimiter::hit($key);
      return;
    }

    $seconds = RateLimiter::availableIn($key);

    throw ValidationException::withMessages([
      'email' => [
        'Too many trial requests from your IP. Please try again in ' . $seconds . ' seconds.',
      ],
    ])->status(429);
  }
}
