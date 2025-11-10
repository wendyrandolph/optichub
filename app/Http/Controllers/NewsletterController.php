<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NewsletterController extends Controller
{
  public function subscribe(Request $request)
  {
    $data = $request->validate([
      'email' => ['required', 'email', 'max:255'],
    ]);

    // Stub: save to DB/service later.
    // For now, pretend it worked:
    return back()->with('success', 'Thanks! You’re on the list.');
  }
}
