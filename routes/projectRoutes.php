<?php
// routes/projectRoutes.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Middleware\CheckRole;

use App\Http\Controllers\AdminController;

Route::middleware(['auth'])->group(function () {
  Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
  Route::get('/team',    [TeamMemberController::class, 'index'])->name('team');
  Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});


/**
 * Tenant-aware Projects
 * URLs: /{tenant:id}/projects/...
 * Names: tenant.projects.*
 */
Route::middleware(['web', 'auth', 'tenant'])   // your tenant middleware that resolves the id → Tenant model
  ->prefix('{tenant}')
  ->name('tenant.')
  ->scopeBindings()
  ->group(function () {

    Route::resource('projects', ProjectController::class);
    Route::prefix('projects')->as('projects.')->group(function () {
      // Read (list/show) — any authenticated user (policy still enforces per-record access)
      Route::get('/',            [ProjectController::class, 'index'])->name('index');
      Route::get('/{project}',   [ProjectController::class, 'show'])->name('show');

      // Create/Store — restricted by role
      Route::middleware([CheckRole::class . ':provider,admin,super_admin,superadmin,tenant'])->group(function () {
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/',      [ProjectController::class, 'store'])->name('store');
      });

      // Edit/Update/Delete — stricter admin set
      Route::middleware([CheckRole::class . ':provider,admin,super_admin,superadmin'])->group(function () {
        Route::get('/{project}/edit',        [ProjectController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}',          [ProjectController::class, 'destroy'])->name('destroy');



        // Agreements & payments (keep)
        Route::post('/save-agreement',      [ProjectController::class, 'saveAgreement'])->name('agreement.save');
        Route::post('/add-payment',         [ProjectController::class, 'addPayment'])->name('payment.add');
        Route::post('/update-payment',      [ProjectController::class, 'updatePayment'])->name('payment.update');
        Route::post('/delete-payment/{id}', [ProjectController::class, 'deletePayment'])->name('payment.delete');
      });

      // Legacy filter endpoint
      Route::get('/filter', [ProjectController::class, 'getFilteredProjectsHtml'])->name('filter');
    });

    // Search — admin/provider only (tenant-aware URL)
    Route::middleware([CheckRole::class . ':provider,admin,super_admin,superadmin'])
      ->get('/search', [SearchController::class, 'index'])
      ->name('search');
  });
