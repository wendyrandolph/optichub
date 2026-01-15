<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
  public function subscribe(Request $request)
  {
    $data = $request->validate([
      'email' => ['required', 'email'],
    ]);

    // Example: save locally or forward to Mailchimp, etc.
    // \App\Models\Subscriber::create($data);
    // or: app(\App\Services\NewsletterService::class)->subscribe($data['email']);

    Log::info("New newsletter subscription: {$data['email']}");

    return back()->with('status', 'Thanks for subscribing! Check your inbox.');
  }
}
