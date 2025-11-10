<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\CheckRole;

Route::middleware(['web', 'auth', 'tenant', CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->prefix('{tenant}')
  ->whereNumber('tenant')
  ->as('tenant.')
  ->group(function () {
    // Settings home
    Route::get('settings', [SettingsController::class, 'index'])
      ->name('settings.index');

    // Billing
    Route::get('settings/billing', [SettingsController::class, 'billing'])
      ->name('settings.billing');

    // Upgrade (optional, show to trialing)
    Route::get('settings/billing-upgrade', [SettingsController::class, 'upgradeForm'])
      ->name('settings.billing-upgrade');

    // Profile
    Route::get('settings/profile', [SettingsController::class, 'profileForm'])
      ->name('settings.profile');
    Route::post('settings/profile', [SettingsController::class, 'profileUpdate'])
      ->name('settings.profile.update');

    // API Keys
    Route::get('settings/api', [SettingsController::class, 'apiIndex'])
      ->name('settings.api.index');
    Route::post('settings/api/generate', [SettingsController::class, 'apiGenerate'])
      ->name('settings.api.generate');
    Route::post('settings/api/{keyId}/revoke', [SettingsController::class, 'apiRevoke'])
      ->name('settings.api.revoke');

    Route::get('admin/tenant/mail-settings', [\App\Http\Controllers\TenantMailSettingController::class, 'edit'])
      ->name('settings.mail.edit');
    Route::post('settings/mail', [\App\Http\Controllers\TenantMailSettingController::class, 'update'])
      ->name('settings.mail.update');
  });
