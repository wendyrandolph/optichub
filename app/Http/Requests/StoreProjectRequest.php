<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_name'      => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'status'            => ['nullable', 'in:open,closed,in_progress,on_hold'],
            'color'             => ['nullable', 'string', 'max:10'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'budgeted_hours'    => ['nullable', 'numeric', 'min:0'],
            'project_fee_total'  => ['nullable', 'numeric', 'min:0'],
            'external_costs'     => ['nullable', 'numeric', 'min:0'],
            'target_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'billing_model'      => ['nullable', Rule::in(['fixed', 'hourly'])],
            'owner_id'          => ['nullable', 'integer'],
            'template_id'       => ['nullable', 'integer'],
            'client_id'         => [
                'nullable',
                Rule::exists('contacts', 'id')->where(fn ($q) => $q->where('tenant_id', $this->route('tenant')?->id)),
            ],
            'client_company_id' => ['nullable', 'integer'],
            'uses_phases'       => ['sometimes', 'boolean'],
        ];
    }
}
