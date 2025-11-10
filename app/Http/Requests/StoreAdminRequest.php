<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Use a policy or gate here to check if the current user can create an admin.
    // Assuming Laravel's Gate or Policy is set up for 'admin' creation:
    return $this->user()->can('create', \App\Models\Admin::class);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
   */
  public function rules(): array
  {
    return [
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
      'password' => ['required', 'string', 'min:8', 'confirmed'], // 'confirmed' checks for password_confirmation field
      'role' => ['required', 'string', 'in:superadmin,editor,viewer'], // Example roles
    ];
  }
}
