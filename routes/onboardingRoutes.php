<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\BillingController;

/**
 * Public (token-based) onboarding routes
 * - These should NOT require auth
 */
Route::prefix('onboarding')->name('onboarding.')->middleware('guest')->group(function () {
  Route::get('setup-password',  [OnboardingController::class, 'setupPasswordForm'])
    ->name('setup-password'); // <-- matches your redirect in TrialController
  Route::post('setup-password', [OnboardingController::class, 'handleSetupPassword'])
    ->name('setup-password.store');
  // Request a new activation link (public)
  Route::get('request-link',  [OnboardingController::class, 'requestLinkForm'])->name('request_link.form');
  Route::post('request-link', [OnboardingController::class, 'handleRequestLink'])->name('request_link.store');
  Route::get('link-sent',     [OnboardingController::class, 'linkSent'])->name('link_sent');
});

/**
 * Auth-required onboarding steps (finish company profile, api key, finish)
 * Pick the guard that makes sense (admin for your internal onboarding)
 */
Route::prefix('onboarding')->name('onboarding.')->middleware(['auth:admin'])->group(function () {
  // onboarding flow
  Route::get('welcome', [OnboardingController::class, 'welcome'])->name('welcome');
  Route::post('workspace', [OnboardingController::class, 'storeWorkspace'])->name('workspace.store');

  Route::get('brand', [OnboardingController::class, 'brand'])->name('brand');
  Route::post('brand', [OnboardingController::class, 'storeBrand'])->name('brand.store');

  Route::get('logo', [OnboardingController::class, 'logo'])->name('logo');
  Route::post('logo', [OnboardingController::class, 'storeLogo'])->name('logo.store');

  Route::get('client', [OnboardingController::class, 'client'])->name('client');
  Route::post('client', [OnboardingController::class, 'storeClient'])->name('client.store');

  Route::get('project', [OnboardingController::class, 'project'])->name('project');
  Route::post('project', [OnboardingController::class, 'storeProject'])->name('project.store');

  Route::get('team', [OnboardingController::class, 'team'])->name('team');
  Route::post('team', [OnboardingController::class, 'storeTeam'])->name('team.store');

  Route::get('finish', [OnboardingController::class, 'finish'])->name('finish');
});

/**
 * Billing portal (requires auth; choose the right guard)
 */
Route::get('/billing/portal', [BillingController::class, 'portal'])
  ->middleware(['auth:client']) // or ['auth:admin'] depending on who should access it
  ->name('billing.portal');
