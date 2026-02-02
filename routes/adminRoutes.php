<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\TenantUserDirectoryController;
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

/*
|--------------------------------------------------------------------------
| Provider Support Console
|--------------------------------------------------------------------------
*/
Route::middleware(\App\Http\Middleware\EnsureProviderSupportAccess::class)->group(function () {
  Route::get('/support', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])
    ->name('support.index');
  Route::get('/support/create', [\App\Http\Controllers\Admin\SupportTicketController::class, 'create'])
    ->name('support.create');
  Route::post('/support', [\App\Http\Controllers\Admin\SupportTicketController::class, 'store'])
    ->name('support.store');
  Route::get('/support/kb', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'index'])
    ->name('support.kb.index');
  Route::post('/support/kb/categories', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'storeCategory'])
    ->name('support.kb.categories.store');
  Route::delete('/support/kb/categories/{category}', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'destroyCategory'])
    ->name('support.kb.categories.destroy');
  Route::get('/support/kb/create', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'create'])
    ->name('support.kb.create');
  Route::post('/support/kb', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'store'])
    ->name('support.kb.store');
  Route::get('/support/kb/{article}/edit', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'edit'])
    ->name('support.kb.edit');
  Route::put('/support/kb/{article}', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'update'])
    ->name('support.kb.update');
  Route::delete('/support/kb/{article}', [\App\Http\Controllers\Admin\SupportKnowledgeBaseController::class, 'destroy'])
    ->name('support.kb.destroy');

  Route::get('/support/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])
    ->whereNumber('ticket')
    ->name('support.show');
  Route::post('/support/{ticket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class, 'updateStatus'])
    ->whereNumber('ticket')
    ->name('support.status');
  Route::post('/support/{ticket}/messages', [\App\Http\Controllers\Admin\SupportTicketController::class, 'addMessage'])
    ->whereNumber('ticket')
    ->name('support.messages.store');
});

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

  Route::get('tenants/subscriptions', [TenantSubscriptionController::class, 'subscriptionsIndex'])
    ->name('tenants.subscriptions.index');
  Route::get('tenants/usage', [TenantSubscriptionController::class, 'usageIndex'])
    ->name('tenants.usage.index');
  Route::get('tenants/logins', [TenantSubscriptionController::class, 'loginsIndex'])
    ->name('tenants.logins.index');
  Route::get('tenants/invoices', [TenantSubscriptionController::class, 'invoicesIndex'])
    ->name('tenants.invoices.index');
  Route::get('tenants/users', [TenantUserDirectoryController::class, 'index'])
    ->name('tenants.users.index');

  Route::get('tenants/{tenant}', [TenantController::class, 'show'])
    ->name('tenants.show');

  Route::get('tenants/{tenant}/subscriptions', [TenantSubscriptionController::class, 'subscriptionsShow'])
    ->name('tenants.subscriptions.show');
  Route::get('tenants/{tenant}/usage', [TenantSubscriptionController::class, 'usageShow'])
    ->name('tenants.usage.show');
  Route::get('tenants/{tenant}/logins', [TenantSubscriptionController::class, 'loginsShow'])
    ->name('tenants.logins.show');
  Route::get('tenants/{tenant}/invoices', [TenantSubscriptionController::class, 'invoicesShow'])
    ->name('tenants.invoices.show');

  Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])
    ->name('tenants.edit');

  Route::put('tenants/{tenant}', [TenantController::class, 'update'])
    ->name('tenants.update');

  Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
    ->name('tenants.destroy');

  Route::get('tenants/{tenant}/contracts/templates', [\App\Http\Controllers\ContractTemplateController::class, 'index'])
    ->name('tenants.contracts.templates.index');
  Route::get('tenants/{tenant}/contracts/templates/create', [\App\Http\Controllers\ContractTemplateController::class, 'create'])
    ->name('tenants.contracts.templates.create');
  Route::post('tenants/{tenant}/contracts/templates', [\App\Http\Controllers\ContractTemplateController::class, 'store'])
    ->name('tenants.contracts.templates.store');
  Route::get('tenants/{tenant}/contracts/templates/{template}/edit', [\App\Http\Controllers\ContractTemplateController::class, 'edit'])
    ->name('tenants.contracts.templates.edit');
  Route::put('tenants/{tenant}/contracts/templates/{template}', [\App\Http\Controllers\ContractTemplateController::class, 'update'])
    ->name('tenants.contracts.templates.update');
  Route::delete('tenants/{tenant}/contracts/templates/{template}', [\App\Http\Controllers\ContractTemplateController::class, 'destroy'])
    ->name('tenants.contracts.templates.destroy');

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
