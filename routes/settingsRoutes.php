<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaxSettingsController;
use App\Http\Controllers\Tenant\PaymentSettingsController;
use App\Http\Controllers\Tenant\RegisteredUserController;
use App\Http\Controllers\Tenant\LeadSettingsController;

// Inherit /app/{tenant} prefix from routes/web.php
Route::prefix('settings')
  ->as('settings.')
  ->group(function () {

    // Settings home
    Route::get('/', [SettingsController::class, 'index'])
      ->name('index');

    // Billing
    Route::get('/billing', [SettingsController::class, 'billing'])
      ->name('billing');
    Route::put('/billing', [SettingsController::class, 'billingUpdate'])
      ->name('billing.update');

    // Upgrade (optional, show to trialing)
    Route::get('/billing-upgrade', [SettingsController::class, 'upgradeForm'])
      ->name('billing-upgrade');

    // Profile
    Route::get('/profile', [SettingsController::class, 'profileForm'])
      ->name('profile');
    Route::post('/profile', [SettingsController::class, 'profileUpdate'])
      ->name('profile.update');


    // API Keys
    Route::get('/api', [SettingsController::class, 'apiIndex'])
      ->name('api.index');
    Route::post('/api/generate', [SettingsController::class, 'apiGenerate'])
      ->name('api.generate');
    Route::post('/api/{keyId}/revoke', [SettingsController::class, 'apiRevoke'])
      ->name('api.revoke');

    Route::get('/mail-settings', [\App\Http\Controllers\TenantMailSettingController::class, 'edit'])
      ->name('mail.edit');
    Route::post('/mail-settings', [\App\Http\Controllers\TenantMailSettingController::class, 'update'])
      ->name('mail.update');

    Route::get('/leads', [LeadSettingsController::class, 'index'])
      ->name('leads.index');
    Route::patch('/leads', [LeadSettingsController::class, 'update'])
      ->name('leads.update');
    Route::post('/leads/regenerate', [LeadSettingsController::class, 'regenerate'])
      ->name('leads.regenerate');

    /**
     * ------------------------------------------------------------
     * TAX SETTINGS (NEW)
     * ------------------------------------------------------------
     */
    // existing rate routes (you already have these)
    Route::post('/rates', [TaxSettingsController::class, 'storeRate'])
      ->name('rates.store');

    Route::put('/rates/{taxRate}', [TaxSettingsController::class, 'updateRate'])
      ->name('rates.update');

    Route::delete('/rates/{taxRate}', [TaxSettingsController::class, 'destroyRate'])
      ->name('rates.destroy');

    // ✅ NEW: Tax settings page + update
    Route::get('/tax', [TaxSettingsController::class, 'edit'])
      ->name('tax.index');

    Route::put('/tax', [TaxSettingsController::class, 'update'])
      ->name('tax.update');

    // Client preferences
    Route::get('/clients', [SettingsController::class, 'clientPreferencesForm'])
      ->name('clients');
    Route::put('/clients', [SettingsController::class, 'clientPreferencesUpdate'])
      ->name('clients.update');

    Route::get('/registered-users', [RegisteredUserController::class, 'index'])
      ->name('users.index');

    // Payments (client invoice payments)
    Route::get('/payments', [PaymentSettingsController::class, 'index'])
      ->name('payments.index')
      ->middleware('capability:PAYMENTS_FRAMEWORK');
    Route::get('/payments/stripe/connect', [PaymentSettingsController::class, 'connectStripe'])
      ->name('payments.stripe.connect')
      ->middleware('capability:PAYMENTS_STRIPE');
    Route::get('/payments/stripe/refresh', [PaymentSettingsController::class, 'refreshStripe'])
      ->name('payments.stripe.refresh')
      ->middleware('capability:PAYMENTS_STRIPE');
    Route::post('/payments/stripe/disconnect', [PaymentSettingsController::class, 'disconnectStripe'])
      ->name('payments.stripe.disconnect')
      ->middleware('capability:PAYMENTS_STRIPE');
    Route::post('/payments/stripe/status', [PaymentSettingsController::class, 'updateStripeStatus'])
      ->name('payments.stripe.status')
      ->middleware('capability:PAYMENTS_STRIPE');
    Route::get('/payments/wave/connect', [PaymentSettingsController::class, 'connectWave'])
      ->name('payments.wave.connect')
      ->middleware('capability:PAYMENTS_WAVE');
    Route::post('/payments/manual', [PaymentSettingsController::class, 'storeManualMethod'])
      ->name('payments.manual.store')
      ->middleware('capability:PAYMENTS_EXTERNAL_LINK');
    Route::patch('/payments/manual/{integration}', [PaymentSettingsController::class, 'updateManualMethod'])
      ->name('payments.manual.update')
      ->middleware('capability:PAYMENTS_EXTERNAL_LINK');
    Route::delete('/payments/manual/{integration}', [PaymentSettingsController::class, 'destroyManualMethod'])
      ->name('payments.manual.destroy')
      ->middleware('capability:PAYMENTS_EXTERNAL_LINK');

    // Pinned shortcuts
    Route::get('/pins', [SettingsController::class, 'pinsForm'])
      ->name('pins');
    Route::post('/pins', [SettingsController::class, 'pinsUpdate'])
      ->name('pins.update');

    // Automations (creative proposals)
    Route::get('/automations', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'index'])
      ->name('automations.index');
    Route::get('/automations/create', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'create'])
      ->name('automations.create');
    Route::post('/automations', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'store'])
      ->name('automations.store');
    Route::get('/automations/{rule}/edit', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'edit'])
      ->name('automations.edit');
    Route::put('/automations/{rule}', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'update'])
      ->name('automations.update');
    Route::delete('/automations/{rule}', [\App\Http\Controllers\Tenant\AutomationSettingsController::class, 'destroy'])
      ->name('automations.destroy');

    // Mailbox Sync (Phase 1 stub)
    Route::get('/mailbox', [\App\Http\Controllers\MailboxSettingsController::class, 'index'])
      ->name('mailbox');
    Route::get('/mailbox/connect', [\App\Http\Controllers\MailboxSettingsController::class, 'connect'])
      ->name('mailbox.connect');
    Route::get('/mailbox/callback', [\App\Http\Controllers\MailboxSettingsController::class, 'callback'])
      ->name('mailbox.callback');
    Route::post('/mailbox/disconnect', [\App\Http\Controllers\MailboxSettingsController::class, 'disconnect'])
      ->name('mailbox.disconnect');
    Route::post('/mailbox/sync', [\App\Http\Controllers\MailboxSettingsController::class, 'sync'])
      ->name('mailbox.sync');
  });
