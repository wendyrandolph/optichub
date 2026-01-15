<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
  public function start(Request $request)
  {
    $data = $request->validate(['plan' => 'required|string']);
    // reuse your helper if you want
    $priceId = \DB::table('plans')->where('code', $data['plan'])->value('stripe_price_id');

    if (! $priceId) {
      return back()->withErrors(['plan' => 'Unknown plan code']);
    }

    // Your existing helper call:
    $url = \App\Helpers\StripeHelper::createSubscriptionCheckout(
      $priceId,
      $data['plan'],
      route('checkout.welcome', [], false) . '?session_id={CHECKOUT_SESSION_ID}',
      route('marketing.pricing')
    );

    return redirect()->away($url);
  }

  public function welcome(Request $request)
  {
    $sessionId = $request->query('session_id');
    return view('marketing.welcome', compact('sessionId'));
  }
}
