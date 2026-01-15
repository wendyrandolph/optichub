<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
  public function authorize(): bool
  {
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
    // When updating, ignore the current invoice’s id for uniqueness
    $invoiceId = (int)($this->route('invoice')?->id ?? 0);

    return [
      'contact_id'      => ['required', 'integer', 'exists:contacts,id'],
      'invoice_number' => [
        'required',
        'string',
        'max:255',
        Rule::unique('invoices', 'invoice_number')
          ->ignore($invoiceId)
          ->where(fn($q) => $q->where('tenant_id', $tenantId)),
      ],
      'issue_date'     => ['required', 'date'],
      'due_date'       => ['required', 'date', 'after_or_equal:issue_date'],
      'status'         => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'partial'])],
      'notes'          => ['nullable', 'string'],

      'items'                      => ['required', 'array', 'min:1'],
      'items.*.description'        => ['required', 'string', 'max:1000'],
      'items.*.quantity'           => ['required', 'integer', 'min:1'],
      'items.*.unit_price'         => ['required', 'numeric', 'min:0'],
    ];
  }
}
