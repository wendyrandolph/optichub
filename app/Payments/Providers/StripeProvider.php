<?php

namespace App\Payments\Providers;

use App\Payments\Contracts\PaymentProvider;
use App\Payments\Requests\InitPaymentRequest;
use App\Payments\Requests\CapturePaymentRequest;
use App\Payments\Requests\RefundPaymentRequest;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent as StripePI;

class StripeProvider implements PaymentProvider
{
  public function __construct(
    protected ?string $apiKey = null,
    protected ?string $connectedAccountId = null, // if using Connect
  ) {
    if ($this->apiKey) {
      Stripe::setApiKey($this->apiKey);
    }
  }

  public function init(InitPaymentRequest $r): array
  {
    // Example using Checkout Session
    $params = [
      'mode' => 'payment',
      'line_items' => [[
        'price_data' => [
          'currency' => $r->currency,
          'product_data' => ['name' => $r->description ?: ('Invoice #' . $r->invoiceNumber)],
          'unit_amount' => (int) round($r->amount * 100),
        ],
        'quantity' => 1,
      ]],
      'success_url' => $r->successUrl,
      'cancel_url'  => $r->cancelUrl,
      'metadata'    => [
        'invoice_id' => $r->invoiceId,
        'tenant_id'  => $r->tenantId,
      ],
    ];

    $opts = $this->connectedAccountId ? ['stripe_account' => $this->connectedAccountId] : [];
    $session = StripeSession::create($params, $opts);

    return [
      'redirect_url'  => $session->url,
      'provider_ref'  => $session->id,
    ];
  }

  public function capture(CapturePaymentRequest $r): array
  {
    $opts = $this->connectedAccountId ? ['stripe_account' => $this->connectedAccountId] : [];
    $pi   = StripePI::retrieve($r->providerPaymentId, $opts);

    $status = match ($pi->status) {
      'succeeded' => 'paid',
      'requires_payment_method', 'requires_action', 'processing' => 'pending',
      default => 'failed',
    };

    return [
      'status'           => $status,
      'provider_txn_id'  => $pi->id,
      'amount_received'  => $pi->amount_received / 100,
      'currency'         => strtoupper($pi->currency),
    ];
  }

  public function refund(RefundPaymentRequest $r): array
  {
    $opts = $this->connectedAccountId ? ['stripe_account' => $this->connectedAccountId] : [];
    $refund = \Stripe\Refund::create([
      'payment_intent' => $r->providerPaymentId,
      'amount'         => (int) round($r->amount * 100),
    ], $opts);

    return [
      'status'              => $refund->status === 'succeeded' ? 'refunded' : 'pending',
      'provider_refund_id'  => $refund->id,
    ];
  }

  public function verifyWebhook(array $headers, string $rawBody): array
  {
    // You can implement signature verification if you store endpoint secrets per tenant
    return json_decode($rawBody, true) ?: [];
  }
}
