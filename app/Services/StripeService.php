<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Invoice as StripeInvoice;

class StripeService
{
  public function __construct(protected ?string $secret)
  {
    if ($this->secret) {
      Stripe::setApiKey($this->secret);
    }
  }

  public function enabled(): bool
  {
    return !empty($this->secret);
  }

  /**
   * Example: create or fetch a hosted invoice URL for your local Invoice model.
   * Return null if Stripe not configured.
   */
  public function hostedInvoiceUrl(object $invoice): ?string
  {
    if (!$this->enabled()) return null;

    // You can map your local $invoice to a Stripe invoice here.
    // Minimal safe example: if you already store a Stripe invoice id:
    if (!empty($invoice->stripe_invoice_id)) {
      $si = StripeInvoice::retrieve($invoice->stripe_invoice_id);
      return $si->hosted_invoice_url ?? null;
    }

    // Otherwise, return null until you implement full sync.
    return null;
  }
}
