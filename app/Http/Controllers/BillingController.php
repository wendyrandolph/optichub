<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Stripe;

class BillingController extends Controller
{
  /**
   * Redirects the authenticated user to the Stripe Customer Billing Portal.
   * Replaces BillingController::portal()
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function portal(): RedirectResponse
  {
    // 1. Authentication and Authorization (Auth::require() replacement)
    // Middleware handles the "ensure user logged in" part. 
    // We ensure the user is linked to a Tenant/Organization.
    $user = Auth::user();

    if (!$user || !$user->tenant_id) {
      // Should be caught by middleware, but a fail-safe check is good.
      return Redirect::route('dashboard')->with('error', 'Authentication failed or organization not found.');
    }

    // 2. Fetch Customer ID (SubscriptionRepo::stripeCustomerIdForTenant replacement)
    // Assuming your Tenant/Subscription model has a 'stripe_customer_id' column
    // or a static method to retrieve it.
    $customerId = $user->tenant->stripe_customer_id;

    if (!$customerId) {
      return Redirect::route('dashboard')->with('error', 'Billing not configured for this organization.');
    }

    // 3. Stripe Configuration and API Call
    // Replaces \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET'))
    Stripe::setApiKey(config('services.stripe.secret'));

    try {
      $session = BillingPortalSession::create([
        'customer' => $customerId,
        // Use Laravel's URL facade to generate a secure, absolute return URL
        'return_url' => URL::route('billing.return'),
      ]);

      // 4. Redirect (Replaces header('Location: ...') and exit;)
      return Redirect::away($session->url);
    } catch (\Stripe\Exception\ApiErrorException $e) {
      Log::error("Stripe Billing Portal Error: " . $e->getMessage());
      return Redirect::route('dashboard')->with('error', 'Could not access the billing portal. Please try again.');
    }
  }
}
