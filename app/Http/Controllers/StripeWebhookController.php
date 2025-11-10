<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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

    // 2. Get the secret from environment configuration (NOT hardcoded)
    $secret = config('services.stripe.webhook_secret');

    if (!$secret) {
      Log::error('Stripe Webhook Secret is not configured in services.stripe.webhook_secret.');
      return response('Configuration Error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    try {
      // 3. Verify signature and construct event using the Stripe SDK
      $event = Webhook::constructEvent($payload, $sigHeader, $secret);
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

    if (!$invoiceId) {
      Log::error('Stripe Webhook Error: Missing invoice_id in metadata.', ['session_id' => $session->id]);
      return response('Missing invoice ID in metadata', Response::HTTP_NOT_FOUND);
    }

    try {
      // 5. Use Eloquent to find and update the invoice (replaces raw PDO)
      $invoice = Invoice::findOrFail($invoiceId);

      $invoice->status = 'Paid';
      $invoice->paid_at = now(); // Laravel helper for current timestamp
      $invoice->save();

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
}
