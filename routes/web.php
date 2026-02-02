<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ClientMagicLinkController;
use App\Http\Controllers\PublicTradeQuoteController;
use App\Http\Controllers\TenantHomeController;
use App\Http\Controllers\LeadInboxController;
use App\Http\Controllers\ProposalController;

// Public/marketing
require __DIR__ . '/publicRoutes.php';

// Auth (login/logout/reset)
require __DIR__ . '/auth.php';

// Provider/Admin console
Route::prefix('admin')
  ->as('admin.')
  ->middleware(['web', 'nocache', 'auth:admin'])
  ->group(function () {
    require __DIR__ . '/adminRoutes.php';
    require __DIR__ . '/activityRoutes.php';
  });

// Tenant workspace
Route::prefix('app/{tenant}')
  ->as('tenant.')
  ->middleware(['web', 'nocache', 'auth:web,admin', 'tenant'])
  ->group(function () {
    Route::get('/', [TenantHomeController::class, 'index'])->name('home');
    require __DIR__ . '/tenant.php';
    require __DIR__ . '/invoiceRoutes.php';
    Route::middleware(['workspace:trades'])->group(function () {
      require __DIR__ . '/tradesRoutes.php';
    });
    require __DIR__ . '/reportRoutes.php';
    require __DIR__ . '/onboardingRoutes.php';
    require __DIR__ . '/trial.php';
    require __DIR__ . '/settingsRoutes.php';
    require __DIR__ . '/dashboardRoutes.php';
    require __DIR__ . '/emailRoutes.php';
  });

// Client portal
Route::prefix('portal')
  ->middleware(['web', 'nocache', 'guest:client'])
  ->group(function () {
    Route::get('/login', [ClientMagicLinkController::class, 'create'])->name('portal.login');
    Route::post('/magic-link', [ClientMagicLinkController::class, 'store'])
      ->middleware('throttle:portal-magic-link')
      ->name('portal.magic.send');
    Route::get('/magic/expired', [ClientMagicLinkController::class, 'expired'])
      ->name('portal.magic.expired');
  });

// Public trade quote acceptance
Route::get('/quote/{token}', [PublicTradeQuoteController::class, 'show'])
  ->middleware(['web', 'nocache'])
  ->name('public.trade-quotes.show');
Route::post('/quote/{token}/accept', [PublicTradeQuoteController::class, 'accept'])
  ->middleware(['web', 'nocache'])
  ->name('public.trade-quotes.accept');

Route::post('/lead-inbox/{inbox_key}', [LeadInboxController::class, 'store'])
  ->middleware(['web', 'nocache', 'throttle:lead-inbox'])
  ->name('public.leads.inbox');

// Public proposal viewing + approval
Route::get('/proposal/{token}', [\App\Http\Controllers\ProposalPublicController::class, 'show'])
  ->middleware(['web', 'nocache'])
  ->name('proposal.public.show');
Route::post('/proposal/{token}/sign', [\App\Http\Controllers\ProposalPublicController::class, 'sign'])
  ->middleware(['web', 'nocache'])
  ->name('proposal.public.sign');

// Legacy routes (kept for backward compatibility)
Route::get('/proposals/{token}', [\App\Http\Controllers\ProposalPublicController::class, 'show'])
  ->middleware(['web', 'nocache'])
  ->name('proposals.client.show');
Route::post('/proposals/{token}/accept', [ProposalController::class, 'accept'])
  ->middleware(['web', 'nocache'])
  ->name('proposals.client.accept');
Route::post('/proposals/{token}/reject', [ProposalController::class, 'reject'])
  ->middleware(['web', 'nocache'])
  ->name('proposals.client.reject');

Route::prefix('portal')
  ->middleware(['web', 'nocache'])
  ->group(function () {
    Route::get('/magic/{token}', [ClientMagicLinkController::class, 'consume'])
      ->name('portal.magic.consume');
  });

Route::prefix('portal')
  ->middleware(['web', 'nocache', 'auth:client', 'client.tenant'])
  ->group(function () {
    require __DIR__ . '/clientRoutes.php';
    require __DIR__ . '/portal.php';
  });
