<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\ProviderClientCompanyController;
use App\Http\Controllers\Admin\AutomationRuleController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SearchController;
use App\Http\Middleware\CheckRole;

/*
|--------------------------------------------------------------------------
| Admin Routes (Provider Zone)
|--------------------------------------------------------------------------
| These routes belong to YOU — the provider — and allow management of:
| 1) Tenants who subscribe to Optic Hub
| 2) Your own client companies (Causey Web Solutions clients)
| 3) Provider tools such as dashboard, users, settings
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Provider Dashboard / General Admin Pages
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

Route::get('/users', [AdminController::class, 'users'])
  ->name('users');

Route::get('/settings', [AdminController::class, 'settings'])
  ->name('settings');

// Automation rules (admin only)
Route::get('/automation-rules', [AutomationRuleController::class, 'index'])
  ->name('automation-rules.index');
Route::put('/automation-rules/{rule}', [AutomationRuleController::class, 'update'])
  ->name('automation-rules.update');
Route::post('/automation-rules/{rule}/test', [AutomationRuleController::class, 'test'])
  ->name('automation-rules.test');

Route::get('/profile', [AdminController::class, 'profile'])
  ->name('profile');
Route::post('/profile', [AdminController::class, 'updateProfile'])
  ->name('profile.update');

Route::get('/invoices', [AdminController::class, 'invoices'])
  ->name('invoices');

// Quick search for provider/admin (uses current user's tenant_id, if any)
Route::get('/search/quick', [SearchController::class, 'quick'])
  ->name('search.quick');

/*
        |--------------------------------------------------------------------------
        | TENANT MANAGEMENT (Your SaaS customers)
        |--------------------------------------------------------------------------
        */
Route::middleware('platform_owner')->group(function () {
  Route::get('tenants', [TenantController::class, 'index'])
    ->name('tenants.index');

  Route::get('tenants/create', [TenantController::class, 'create'])
    ->name('tenants.create');

  Route::post('tenants', [TenantController::class, 'store'])
    ->name('tenants.store');

  Route::get('tenants/{tenant}', [TenantController::class, 'show'])
    ->name('tenants.show');

  Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])
    ->name('tenants.edit');

  Route::put('tenants/{tenant}', [TenantController::class, 'update'])
    ->name('tenants.update');

  Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
    ->name('tenants.destroy');

  // Tenant Companies (platform-owner view)
  Route::get('tenants/{tenant}/companies', [\App\Http\Controllers\Admin\TenantCompanyController::class, 'index'])
    ->name('tenants.companies.index');
});


/*
        |--------------------------------------------------------------------------
        | Provider Admin Account Management (AdminProfileController)
        |--------------------------------------------------------------------------
        */
Route::get('/create', [AdminProfileController::class, 'createForm'])
  ->name('create');

Route::post('/create', [AdminProfileController::class, 'create'])
  ->name('store');

Route::get('/edit/{id}', [AdminProfileController::class, 'editForm'])
  ->name('edit');

Route::post('/update/{id}', [AdminProfileController::class, 'update'])
  ->name('update');

Route::post('/delete/{id}', [AdminProfileController::class, 'delete'])
  ->name('delete');



/*
        |--------------------------------------------------------------------------
        | PROVIDER'S OWN CLIENT COMPANIES (Causey Web Solutions clients)
        |--------------------------------------------------------------------------
        | These belong ONLY to the provider, not to tenants.
        |--------------------------------------------------------------------------
        */
Route::get('clients', [ProviderClientCompanyController::class, 'index'])
  ->name('clients.index');

Route::get('clients/create', [ProviderClientCompanyController::class, 'create'])
  ->name('clients.create');

Route::post('clients', [ProviderClientCompanyController::class, 'store'])
  ->name('clients.store');

// optional future:
// Route::get('clients/{id}/edit', ...)
// Route::put('clients/{id}', ...)
// Route::delete('clients/{id}', ...)


/*
        |--------------------------------------------------------------------------
        | Legacy ClientController Routes (still used?)
        |--------------------------------------------------------------------------
        | These are different from ProviderClientCompanyController:
        | They refer to *individual contacts*, not companies.
        |--------------------------------------------------------------------------
        */
Route::get('/clients/view/{id}', [ClientController::class, 'show'])
  ->whereNumber('id')
  ->name('clients.show');

Route::post('/clients/resend-login-email/{id}', [ClientController::class, 'resendLoginEmail'])
  ->whereNumber('id')
  ->name('clients.resend_login_email');
