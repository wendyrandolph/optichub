<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeadController;
use App\Http\Middleware\CheckRole;

Route::middleware(['web', 'auth', 'tenant', CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->prefix('{tenant}')
  ->as('tenant.')
  ->whereNumber('tenant')
  ->group(function () {
    Route::resource('leads', LeadController::class)
      ->parameters(['leads' => 'lead'])
      ->names('leads'); // tenant.leads.index, tenant.leads.create, ...

    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
      ->name('leads.convert');
  });
