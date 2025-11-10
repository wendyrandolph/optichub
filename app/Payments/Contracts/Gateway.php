<?php

namespace App\Payments\Contracts;

use App\Payments\Requests\InitPaymentRequest;
use App\Payments\Requests\CapturePaymentRequest;
use App\Payments\Requests\RefundPaymentRequest;

interface Gateway
{
  /** Start a payment and return a redirect URL or hosted page URL */
  public function init(InitPaymentRequest $request): array; // ['redirect_url' => '...','provider_ref'=>'...']

  /** Handle capture/confirmation (after redirect or webhook) */
  public function capture(CapturePaymentRequest $request): array; // ['status'=>'paid|failed|pending','provider_txn_id'=>'...']

  /** Issue a refund */
  public function refund(RefundPaymentRequest $request): array; // ['status'=>'refunded','provider_refund_id'=>'...']

  /** Optional: verify webhook signature and return normalized payload */
  public function verifyWebhook(array $headers, string $rawBody): array; // throws on invalid
}
