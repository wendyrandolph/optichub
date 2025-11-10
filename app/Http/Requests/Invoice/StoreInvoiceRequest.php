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

  public function rules(): array
  {
    $tenantId = (int)($this->user()->tenant_id ?? 0);

    return [
      'client_id'      => ['required', 'integer', 'exists:clients,id'],
      'invoice_number' => [
        'required',
        'string',
        'max:255',
        // If your invoices table has tenant_id, this keeps numbers unique per-tenant
        Rule::unique('invoices', 'invoice_number')->where(fn($q) => $q->where('tenant_id', $tenantId)),
      ],
      'issue_date'     => ['required', 'date'],
      'due_date'       => ['required', 'date', 'after_or_equal:issue_date'],
      'status'         => ['required', Rule::in(['Draft', 'Sent', 'Paid'])],
      'notes'          => ['nullable', 'string'],

      // Line items
      'items'                      => ['required', 'array', 'min:1'],
      'items.*.description'        => ['required', 'string', 'max:1000'],
      'items.*.quantity'           => ['required', 'integer', 'min:1'],
      'items.*.unit_price'         => ['required', 'numeric', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'items.required' => 'Please add at least one line item.',
    ];
  }
}
