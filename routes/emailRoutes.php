<?php

use Illuminate\Support\Facades\Route;
// routes/emailRoutes.php (or inside your existing tenant group)
use App\Http\Controllers\EmailController;
use App\Http\Middleware\CheckRole;

Route::middleware(['web', 'auth', 'tenant', CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->prefix('{tenant}')
  ->whereNumber('tenant')
  ->as('tenant.')
  ->group(function () {
    Route::resource('emails', EmailController::class)->names('emails');

    Route::get('emails/compose', [\App\Http\Controllers\EmailComposeController::class, 'create'])
      ->name('emails.create');
    Route::post('emails/compose', [\App\Http\Controllers\EmailComposeController::class, 'store'])
      ->name('emails.store');
  });
