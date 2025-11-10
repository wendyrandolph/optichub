<?php
// app/Http/Requests/InitPaymentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitPaymentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }
  public function rules(): array
  {
    return [
      'amount'   => ['required', 'integer', 'min:1'],
      'currency' => ['required', 'string', 'size:3'],
      'metadata' => ['sometimes', 'array'],
      'manual'   => ['sometimes', 'boolean'],
    ];
  }
}
