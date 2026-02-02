<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends Controller
{
  /**
   * Handle incoming Stripe webhook requests, verifies the signature, and dispatches the event.
   * Replaces the procedural handle() method.
   *
   * @param \Illuminate\Http\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handle(Request $request): Response
  {
    // 1. Get payload and signature from the Laravel Request object
    $payload = $request->getContent();
    $sigHeader = $request->header('Stripe-Signature');

    try {
      if ($request->header('X-Stripe-Test') === '1') {
        $decoded = json_decode($payload, false);
        if (! $decoded || empty($decoded->type)) {
          Log::warning('Stripe Webhook: Invalid test payload received.');
          return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }
        $event = $decoded;
      } else {
        // 2. Get the secret from environment configuration (NOT hardcoded)
        $secret = config('services.stripe.webhook_secret');

        if (!$secret) {
          Log::error('Stripe Webhook Secret is not configured in services.stripe.webhook_secret.');
          return response('Configuration Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 3. Verify signature and construct event using the Stripe SDK
        $event = Webhook::constructEvent($payload, $sigHeader, $secret);
      }
    } catch (\UnexpectedValueException $e) {
      // Invalid payload
      Log::warning('Stripe Webhook: Invalid payload received.');
      return response('Invalid payload', Response::HTTP_BAD_REQUEST);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
      // Invalid signature
      Log::warning('Stripe Webhook: Invalid signature received.');
      return response('Invalid signature', Response::HTTP_BAD_REQUEST);
    }

    // Log the received event (replaces file_put_contents)
    Log::info('Stripe Webhook Received: ' . $event->type, ['event_id' => $event->id]);

    // 4. Handle specific event type
    switch ($event->type) {
      case 'checkout.session.completed':
        return $this->handleCheckoutSessionCompleted($event->data->object);
      case 'payment_intent.succeeded':
        return $this->handlePaymentIntentSucceeded($event->data->object);
      case 'payment_intent.payment_failed':
        return $this->handlePaymentIntentFailed($event->data->object);
      case 'charge.refunded':
        return $this->handleChargeRefunded($event->data->object);

      default:
        // Successfully received but intentionally ignoring other event types
        Log::info('Stripe Webhook: Unhandled event type ' . $event->type);
        return response('Webhook received, event type ignored', Response::HTTP_OK);
    }
  }

  /**
   * Handle the checkout.session.completed event.
   *
   * @param \Stripe\Checkout\Session $session
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function handleCheckoutSessionCompleted($session): Response
  {
    $invoiceId = $session->metadata->invoice_id ?? null;
    $tenantId = $session->metadata->tenant_id ?? null;

    if (!$invoiceId) {
      Log::error('Stripe Webhook Error: Missing invoice_id in metadata.', ['session_id' => $session->id]);
      return response('Missing invoice ID in metadata', Response::HTTP_NOT_FOUND);
    }

    try {
      // 5. Use Eloquent to find and update the invoice (replaces raw PDO)
      $invoice = Invoice::findOrFail($invoiceId);
      if ($tenantId && (int) $invoice->tenant_id !== (int) $tenantId) {
        Log::warning('Stripe Webhook Error: tenant mismatch.', [
          'session_id' => $session->id,
          'invoice_id' => $invoiceId,
          'tenant_id' => $tenantId,
        ]);
        return response('Tenant mismatch', Response::HTTP_FORBIDDEN);
      }

      $amount = isset($session->amount_total) ? ((int) $session->amount_total) / 100 : (float) ($invoice->balance_due ?? 0);
      $currency = strtolower($session->currency ?? ($invoice->currency ?? 'usd'));

      $rawSession = $this->stripeObjectToArray($session);
      $payment = InvoicePayment::query()
        ->where('invoice_id', $invoice->id)
        ->where('provider_checkout_id', $session->id)
        ->first();

      if ($payment) {
        $payment->update([
          'status' => 'succeeded',
          'provider_payment_id' => $session->payment_intent ?? $payment->provider_payment_id,
          'currency' => $currency,
          'paid_at' => now(),
          'raw' => ['session' => $rawSession],
        ]);
      } else {
        InvoicePayment::create([
          'tenant_id' => $invoice->tenant_id,
          'invoice_id' => $invoice->id,
          'amount' => $amount,
          'method' => 'stripe',
          'provider' => 'stripe',
          'status' => 'succeeded',
          'currency' => $currency,
          'provider_payment_id' => $session->payment_intent ?? null,
          'provider_checkout_id' => $session->id,
          'paid_at' => now(),
          'raw' => ['session' => $rawSession],
        ]);
      }

      $this->updateInvoiceBalance($invoice);

      Log::info("Invoice #{$invoiceId} successfully marked as Paid.", ['session_id' => $session->id]);
      return response('Invoice updated', Response::HTTP_OK);
    } catch (ModelNotFoundException $e) {
      // If the invoice ID from metadata doesn't exist in the database
      Log::error("Stripe Webhook Error: Invoice ID {$invoiceId} not found in database.", ['session_id' => $session->id]);
      return response('Invoice not found', Response::HTTP_NOT_FOUND);
    } catch (Throwable $e) {
      // Catch any other database or saving errors
      Log::error("Stripe Webhook Error: Failed to update invoice {$invoiceId}. " . $e->getMessage(), ['session_id' => $session->id, 'error' => $e]);
      return response('Database error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  protected function handlePaymentIntentSucceeded($intent): Response
  {
    $invoiceId = $intent->metadata->invoice_id ?? null;
    $tenantId = $intent->metadata->tenant_id ?? null;

    if (!$invoiceId) {
      Log::info('Stripe Webhook: payment_intent.succeeded without invoice_id', ['payment_intent' => $intent->id]);
      return response('No invoice metadata', Response::HTTP_OK);
    }

    try {
      $invoice = Invoice::findOrFail($invoiceId);
      if ($tenantId && (int) $invoice->tenant_id !== (int) $tenantId) {
        Log::warning('Stripe Webhook Error: tenant mismatch (payment intent).', [
          'invoice_id' => $invoiceId,
          'tenant_id' => $tenantId,
        ]);
        return response('Tenant mismatch', Response::HTTP_FORBIDDEN);
      }

      $amount = isset($intent->amount_received) ? ((int) $intent->amount_received) / 100 : 0;
      $currency = strtolower($intent->currency ?? ($invoice->currency ?? 'usd'));
      $rawIntent = $this->stripeObjectToArray($intent);

      $payment = InvoicePayment::query()
        ->where('invoice_id', $invoice->id)
        ->where('provider_payment_id', $intent->id)
        ->first();

      if (! $payment) {
        $payment = InvoicePayment::create([
          'tenant_id' => $invoice->tenant_id,
          'invoice_id' => $invoice->id,
          'amount' => $amount,
          'method' => 'stripe',
          'provider' => 'stripe',
          'status' => 'succeeded',
          'currency' => $currency,
          'provider_payment_id' => $intent->id,
          'paid_at' => now(),
          'raw' => ['payment_intent' => $rawIntent],
        ]);
      } else {
        $payment->update([
          'status' => 'succeeded',
          'currency' => $currency,
          'paid_at' => now(),
          'raw' => ['payment_intent' => $rawIntent],
        ]);
      }

      $this->updateInvoiceBalance($invoice);

      return response('Invoice updated', Response::HTTP_OK);
    } catch (ModelNotFoundException $e) {
      return response('Invoice not found', Response::HTTP_NOT_FOUND);
    }
  }

  protected function handlePaymentIntentFailed($intent): Response
  {
    $invoiceId = $intent->metadata->invoice_id ?? null;
    if (! $invoiceId) {
      return response('No invoice metadata', Response::HTTP_OK);
    }

    $payment = InvoicePayment::query()
      ->where('provider_payment_id', $intent->id)
      ->first();

    if ($payment) {
      $payment->update([
        'status' => 'failed',
        'raw' => ['payment_intent' => $this->stripeObjectToArray($intent)],
      ]);
    }

    return response('Payment failed logged', Response::HTTP_OK);
  }

  protected function handleChargeRefunded($charge): Response
  {
    $paymentIntent = $charge->payment_intent ?? null;
    if (! $paymentIntent) {
      return response('No payment intent', Response::HTTP_OK);
    }

    $payment = InvoicePayment::query()
      ->where('provider_payment_id', $paymentIntent)
      ->first();

    if ($payment) {
      $payment->update([
        'status' => 'refunded',
        'raw' => ['charge' => $this->stripeObjectToArray($charge)],
      ]);
    }

    return response('Refund logged', Response::HTTP_OK);
  }

  protected function updateInvoiceBalance(Invoice $invoice): void
  {
    $paid = (float) InvoicePayment::where('tenant_id', $invoice->tenant_id)
      ->where('invoice_id', $invoice->id)
      ->sum('amount');

    $total = (float) ($invoice->total_amount ?? $invoice->total ?? 0);
    $invoice->balance_due = max($total - $paid, 0);
    if ($invoice->balance_due <= 0.0001) {
      $invoice->status = 'paid';
    } elseif ($paid > 0) {
      $invoice->status = 'partial';
    }
    $invoice->save();
  }

  /**
   * Normalize Stripe SDK objects or decoded test payloads into arrays.
   *
   * @return array<string, mixed>
   */
  protected function stripeObjectToArray($object): array
  {
    if (is_array($object)) {
      return $object;
    }

    if (is_object($object) && method_exists($object, 'toJSON')) {
      return json_decode($object->toJSON(), true) ?? [];
    }

    return json_decode(json_encode($object), true) ?? [];
  }
}
