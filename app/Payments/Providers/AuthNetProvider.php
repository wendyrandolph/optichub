<?php

namespace App\Payments\Providers;

use App\Payments\Contracts\PaymentProvider;
use App\Payments\Requests\InitPaymentRequest;
use App\Payments\Requests\CapturePaymentRequest;
use App\Payments\Requests\RefundPaymentRequest;

class AuthNetProvider implements PaymentProvider
{
  public function __construct(
    protected string $apiLoginId,
    protected string $transactionKey,
    protected ?string $signatureKey = null,
    protected bool $sandbox = true,
  ) {}

  public function init(InitPaymentRequest $r): array
  {
    // Common pattern: use Accept Hosted (hosted payment page), generate token, redirect
    // Return URL or hosted form URL
    return [
      'redirect_url' => $this->buildHostedPaymentUrl($r),
      'provider_ref' => null,
    ];
  }

  public function capture(CapturePaymentRequest $r): array
  {
    // Use the transaction ID from the callback or query later by invoice/order number
    return [
      'status'          => 'paid', // or pending/failed
      'provider_txn_id' => $r->providerPaymentId,
    ];
  }

  public function refund(RefundPaymentRequest $r): array
  {
    // Call refund transaction API
    return [
      'status'             => 'refunded',
      'provider_refund_id' => 'AUTHNET-REF-123',
    ];
  }

  public function verifyWebhook(array $headers, string $rawBody): array
  {
    // Validate signature if using transaction webhooks
    return json_decode($rawBody, true) ?: [];
  }

  protected function buildHostedPaymentUrl(InitPaymentRequest $r): string
  {
    // Pseudo; swap with real tokenization call
    return route('payments.external.redirect', [
      'invoice' => $r->invoiceId,
      'tenant'  => $r->tenantId,
      'via'     => 'authorizenet',
    ]);
  }
}
