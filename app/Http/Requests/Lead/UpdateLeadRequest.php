<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->can('update', $this->route('lead')) ?? false;
  }

  public function rules(): array
  {
    return [
      'name'         => ['required', 'string', 'max:255'],
      'first_name'   => ['nullable', 'string', 'max:100'],
      'last_name'    => ['nullable', 'string', 'max:100'],
      'email'        => ['nullable', 'email', 'max:255'],
      'phone'        => ['nullable', 'string', 'max:255'],
      'company'      => ['nullable', 'string', 'max:160'],
      'status'       => ['required', 'string', 'in:new,contacted,qualified,unqualified,converted,archived'],
      'priority'     => ['nullable', 'string', 'in:low,normal,high'],
      'source'       => ['nullable', 'string', 'max:100'],
      'source_url'   => ['nullable', 'string', 'max:512'],
      'form_name'    => ['nullable', 'string', 'max:160'],
      'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
      'company_id'   => ['nullable', 'integer', 'exists:client_companies,id'],
      'notes'        => ['nullable', 'string'],
      'message'      => ['nullable', 'string'],
    ];
  }
}
