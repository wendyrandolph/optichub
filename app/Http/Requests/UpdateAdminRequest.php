<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Since this request is used for a route with Route Model Binding,
    // we can access the bound model (the Admin being updated).
    // Assuming the route parameter is named 'admin':
    $admin = $this->route('admin');

    // Use a policy to check if the user can update this specific admin
    return $this->user()->can('update', $admin);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
   */
  public function rules(): array
  {
    // Get the ID of the admin being updated from the route (used for the unique rule exception)
    $adminId = $this->route('admin')->id ?? null;

    return [
      'first_name' => ['sometimes', 'required', 'string', 'max:255'],
      'last_name' => ['sometimes', 'required', 'string', 'max:255'],
      // The email must be unique, but ignore the current admin's email
      'email' => [
        'sometimes',
        'required',
        'string',
        'email',
        'max:255',
        Rule::unique('admins')->ignore($adminId),
      ],
      // Password is optional on update, but if present, it must be validated
      'password' => ['nullable', 'string', 'min:8', 'confirmed'],
      'role' => ['sometimes', 'required', 'string', 'in:superadmin,editor,viewer'],
    ];
  }
}
