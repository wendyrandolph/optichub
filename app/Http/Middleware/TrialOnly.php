<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrialOnly
{
  public function handle(Request $request, Closure $next)
  {
    $user = $request->user();
    if (!$user || !$user->tenant) {
      return redirect()->route('login');
    }

    $tenant = $user->tenant; // relationship on User -> tenant()
    // Adjust these fields to your schema: subscription_status, trial_ends_at, trial_status, etc.
    $isTrialing = in_array(($tenant->subscription_status ?? ''), ['trialing', 'trial'], true);

    if (!$isTrialing) {
      // Not in trial anymore—send them to the normal billing/settings page
      return redirect()->route('tenant.settings.billing', ['tenant' => $tenant->id])
        ->with('flash_info', 'Your trial is over. Manage billing here.');
    }

    return $next($request);
  }
}
