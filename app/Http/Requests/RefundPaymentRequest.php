<?php
// app/Http/Requests/RefundPaymentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }
  public function rules(): array
  {
    return [
      'amount' => ['nullable', 'integer', 'min:1'],
    ];
  }
}
