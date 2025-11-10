<!-- routes/web.php -->

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Middleware\CheckRole;

require __DIR__ . '/adminRoutes.php';
require __DIR__ . '/authRoutes.php';
require __DIR__ . '/dashboardRoutes.php';
require __DIR__ . '/activityRoutes.php';
require __DIR__ . '/projectRoutes.php';
require __DIR__ . '/leadsRoutes.php';
require __DIR__ . '/taskRoutes.php';
require __DIR__ . '/settingsRoutes.php';
require __DIR__ . '/reportRoutes.php';
require __DIR__ . '/marketingRoutes.php';
require __DIR__ . '/organizationRoutes.php';
require __DIR__ . '/tenantManagementRoutes.php';
require __DIR__ . '/invoiceRoutes.php';
require __DIR__ . '/emailRoutes.php';

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// --- Static pages (simple) ---
Route::view('/privacy', 'static.privacy')->name('privacy');
Route::view('/terms', 'static.terms')->name('terms');
Route::view('/security', 'static.security')->name('security');
Route::view('/changelog', 'static.changelog')->name('changelog');

// --- Status page (human) ---
Route::get('/status', [\App\Http\Controllers\StatusController::class, 'show'])->name('status');

// --- Newsletter subscribe (stub) ---
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])
  ->name('newsletter.subscribe');



/*
|--------------------------------------------------------------------------
| Tenant-Scoped Application Routes
|--------------------------------------------------------------------------
| Keep your working login + dashboard routes intact.
*/
Route::middleware(['auth'])
  ->prefix('{tenant}')
  ->as('tenant.')
  ->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('team-members', TeamMemberController::class)
      ->names('team-members');
    // Example tenant resources can be added here
    // Route::resource('posts', PostController::class);
  });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::permanentRedirect('/admins/dashboard', '/dashboard');
  });
