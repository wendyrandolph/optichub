<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
  public function authorize(): bool
  {
    // You can add a policy/ability check here if you want
    return true;
  }

  protected function prepareForValidation(): void
  {
    $this->merge([
      'status' => strtolower((string) $this->input('status', 'draft')),
    ]);
  }

  public function rules(): array
  {
    $tenantId = (int)($this->user()->tenant_id ?? 0);

    return [
      'contact_id'     => ['required', 'integer', 'exists:contacts,id'],
      'invoice_number' => [
        'nullable',
        'string',
        'max:255',
        // If your invoices table has tenant_id, this keeps numbers unique per-tenant
        Rule::unique('invoices', 'invoice_number')->where(fn($q) => $q->where('tenant_id', $tenantId)),
      ],
      'issue_date'     => ['nullable', 'date'],
      'due_date'       => ['nullable', 'date', 'after_or_equal:issue_date'],
      'status'         => ['required', 'string', 'max:32'],
      'notes'          => ['nullable', 'string'],
      'tax_rate'       => ['nullable', 'numeric', 'min:0'],
      'discount_type'  => ['nullable', 'in:none,percent,fixed'],
      'discount_value' => ['nullable', 'numeric', 'min:0'],
      'trade_job_id'   => ['nullable', 'integer', Rule::exists('trade_jobs', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],

      // Line items
      'items'                 => ['array'],
      'items.*.id'            => ['nullable', 'integer', 'exists:invoice_line_items,id'],
      'items.*.name'          => ['required', 'string', 'max:255'],
      'items.*.description'   => ['nullable', 'string'],
      'items.*.quantity'      => ['required', 'numeric', 'min:0'],
      'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
      'items.*.position'      => ['nullable', 'integer', 'min:0'],
      'items.*.is_taxable'    => ['nullable', 'boolean'],
      'items.*.service_date'  => ['nullable', 'date'],
      'items.*.source_type'   => ['nullable', 'string', 'max:50'],
      'items.*.source_id'     => ['nullable', 'integer'],
    ];
  }

  public function messages(): array
  {
    return [
      'items.required' => 'Please add at least one line item.',
    ];
  }
}
