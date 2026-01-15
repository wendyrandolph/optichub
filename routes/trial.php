<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrialController;

Route::middleware('guest')->group(function () {
  // Show the “Start Trial” form
  Route::get('/trial', [TrialController::class, 'showTrialForm'])->name('trial.show');

  // Handle form submit
  Route::post('/trial', [TrialController::class, 'start'])->name('trial.start');
});
