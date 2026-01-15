<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
  /**
   * Get the path the user should be redirected to when they are not authenticated.
   */
  protected function redirectTo(Request $request): ?string
  {
    // If the request expects JSON (e.g., an API request), return null (no redirect).
    // Otherwise, redirect to the named 'login' route.
    if ($request->expectsJson()) {
      return null;
    }

    if ($request->is('portal') || $request->is('portal/*')) {
      return route('portal.login');
    }

    return route('login');
  }
}
