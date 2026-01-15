<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

// These routes inherit the /app/{tenant} prefix and auth from routes/web.php
Route::resource('emails', EmailController::class)->names('emails');

Route::get('emails/compose', [\App\Http\Controllers\EmailComposeController::class, 'create'])
  ->name('emails.create');
Route::post('emails/compose', [\App\Http\Controllers\EmailComposeController::class, 'store'])
  ->name('emails.store');
