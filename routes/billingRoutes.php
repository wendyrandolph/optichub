<?php
// routes/billing.php

use App\Http\Controllers\TaxSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:admin')->prefix('billing')->name('billing.')->group(function () {

  /**
   * ------------------------------------------------------------
   * Subscription Activation
   * ------------------------------------------------------------
   */
  Route::post('/activate', function (\Illuminate\Http\Request $r) {
    $plan   = $r->validate(['plan' => 'required|string'])['plan'];
    $tenant = auth('admin')->user()->tenant;

    $sub = $tenant->currentSubscription;

    if ($sub) {
      $sub->update([
        'status'             => 'active',
        'plan_name'          => $plan,
        'current_period_end' => now()->addMonth(),
      ]);
    } else {
      \App\Models\Subscription::create([
        'tenant_id'          => $tenant->id,
        'plan_name'          => $plan,
        'status'             => 'active',
        'current_period_end' => now()->addMonth(),
      ]);
    }

    // Optional: sync with legacy column
    $tenant->update([
      'subscription_status' => 'active',
    ]);

    return redirect()
      ->route('admin.dashboard')
      ->with('status', 'Subscription active.');
  })->name('activate');
});
