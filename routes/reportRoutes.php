<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Middleware\CheckRole;


// Note the order: web -> tenant -> setTenantUrlDefaults -> auth -> checkrole
Route::middleware([
  'web',
  'tenant',
  'auth',
  CheckRole::class . ':provider,admin,super_admin,superadmin'
])
  ->prefix('{tenant}/admin/reports/')
  ->as('tenant.admin.reports.')
  ->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportsController::class, 'index'])->name('index');
    Route::get('/export', [\App\Http\Controllers\ReportsController::class, 'export'])->name('export');

    // Finance
    Route::get('/invoices', [ReportsController::class, 'invoices'])->name('invoices');
    Route::get('/collected', [ReportsController::class, 'collected'])->name('collected');
    Route::get('/forecast', [ReportsController::class, 'forecast'])->name('forecast');
    Route::get('/ar-aging', [ReportsController::class, 'arAging'])->name('ar_aging');

    // Operations
    Route::get('/tasks-due', [ReportsController::class, 'tasksDue'])->name('tasks.due');
    Route::get('/tasks/on-time', [ReportsController::class, 'tasksOnTime'])->name('tasks-on-time');
    Route::get('/projects-stale', [ReportsController::class, 'projectsStale'])->name('projects_stale');

    // CRM / Email
    Route::get('/leads/new', [ReportsController::class, 'leadsNew'])->name('leads.new');
    Route::get('/emails/activity', [ReportsController::class, 'emailsActivity'])->name('emails.activity');
  });
