<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->can('create', \App\Models\Lead::class) ?? false;
  }

  public function rules(): array
  {
    return [
      'name'         => ['required', 'string', 'max:255'],
      'email'        => ['nullable', 'email', 'max:255'],
      'phone'        => ['nullable', 'string', 'max:255'],
      'status'       => ['required', 'string', 'in:new,contacted,interested,client,closed,lost'],
      'source'       => ['nullable', 'string', 'max:100'],
      'owner_id'     => ['nullable', 'integer', 'exists:users,id'],
      'notes'        => ['nullable', 'string'],
    ];
  }
}
