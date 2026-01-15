<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityController;

Route::middleware(['web', 'auth:admin', \App\Http\Middleware\CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->prefix('admin')
  ->name('admin.')
  ->group(function () {
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('activity/{type}/{id}', [ActivityController::class, 'showRelated'])->name('activity.related');
  });
