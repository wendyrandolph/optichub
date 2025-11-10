<?php
// app/Http/Requests/CapturePaymentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapturePaymentRequest extends FormRequest
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
