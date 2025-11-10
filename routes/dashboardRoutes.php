<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckRole;

Route::middleware(['web', 'auth', 'tenant'])
  ->prefix('{tenant}')
  ->as('tenant.')
  ->scopeBindings()
  ->whereNumber('tenant')
  ->group(function () {
    Route::get('dashboards', [DashboardController::class, 'index'])
      ->name('dashboards.index');
    Route::get('dashboards/tasks', [DashboardController::class, 'tasks'])->name('dashboards.tasks');
    Route::get('dashboards/time', [DashboardController::class, 'time'])->name('dashboards.time');
    Route::get('dashboards/opportunities', [DashboardController::class, 'opportunities'])->name('dashboards.opportunities');
    Route::get('dashboards/leads', [DashboardController::class, 'leads'])->name('dashboards.leads');
  });
