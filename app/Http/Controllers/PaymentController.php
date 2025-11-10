<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Payments\ProviderFactory;
use App\Models\Payment; // assumes payments table/model (see notes)

class PaymentController extends Controller
{
  public function __construct(private ProviderFactory $factory) {}

  /**
   * POST /api/payments/init
   * Body: amount (int, minor units), currency (string), metadata (array), manual (bool)
   * Returns: { id, status, clientSecret?, redirectUrl? }
   */
  public function init(InitPaymentRequest $req)
  {
    $tenantId = (string) $req->user()->tenant_id;

    // Resolve tenant's configured gateway
    $provider = $this->factory->makeForTenant($tenantId);

    // Optional: Idempotency to avoid dupes on retries
    $key = $req->header('X-Idempotency-Key') ?: Str::uuid()->toString();

    return DB::transaction(function () use ($req, $provider, $tenantId, $key) {
      // Create provider-side intent/transaction
      $result = $provider->createPaymentIntent([
        'amount'   => (int) $req->validated('amount'),
        'currency' => $req->validated('currency'),
        'metadata' => $req->validated('metadata') ?? [],
        'capture'  => $req->boolean('manual') ? 'manual' : 'automatic',
        'idempotency_key' => $key,
      ]);

      // Persist normalized payment record
      $payment = Payment::create([
        'tenant_id'            => $tenantId,
        'provider'             => $provider::id(),
        'provider_payment_id'  => $result['id'],
        'amount'               => (int) $req->validated('amount'),
        'currency'             => $req->validated('currency'),
        'status'               => $result['status'],
        'metadata'             => $req->validated('metadata') ?? [],
        'idempotency_key'      => $key,
      ]);

      return response()->json([
        'paymentId'   => $payment->id,
        'providerId'  => $result['id'],
        'status'      => $result['status'],
        'clientSecret' => $result['clientSecret'] ?? null,
        'redirectUrl' => $result['redirectUrl'] ?? null,
      ]);
    });
  }

  /**
   * POST /api/payments/{providerPaymentId}/capture
   * Body: amount? (int, minor units)
   */
  public function capture(CapturePaymentRequest $req, string $providerPaymentId)
  {
    $tenantId = (string) $req->user()->tenant_id;
    $provider = $this->factory->makeForTenant($tenantId);

    $res = $provider->capture($providerPaymentId, $req->validated('amount'));

    // Update local record
    Payment::where('tenant_id', $tenantId)
      ->where('provider', $provider::id())
      ->where('provider_payment_id', $providerPaymentId)
      ->update(['status' => $res['status'] ?? 'requires_capture']);

    return response()->json(['ok' => true, 'status' => $res['status'] ?? null]);
  }

  /**
   * POST /api/payments/{providerPaymentId}/refund
   * Body: amount? (int, minor units)
   */
  public function refund(RefundPaymentRequest $req, string $providerPaymentId)
  {
    $tenantId = (string) $req->user()->tenant_id;
    $provider = $this->factory->makeForTenant($tenantId);

    $res = $provider->refund($providerPaymentId, $req->validated('amount'));

    // Optional: update local status if full refund
    if ($req->validated('amount') === null) {
      Payment::where('tenant_id', $tenantId)
        ->where('provider', $provider::id())
        ->where('provider_payment_id', $providerPaymentId)
        ->update(['status' => 'refunded']);
    }

    return response()->json(['refundId' => $res['refundId'] ?? null]);
  }
}
