<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\LeadApiController;
use App\Http\Controllers\Api\EventApiController;

Route::prefix('v1')->group(function () {
  // Public ping (no key)
  Route::get('/ping', [ApiController::class, 'ping'])->name('api.v1.ping');

  // Protected routes (require X-Api-Key header)
  Route::middleware('apikey')->group(function () {
    // Authenticated ping (confirms key + tenant)
    Route::get('/auth/ping', [ApiController::class, 'authPing'])->name('api.v1.auth-ping');

    // Leads
    Route::get('/leads',        [LeadApiController::class, 'index'])->name('api.v1.leads.index');
    Route::get('/leads/{id}',   [LeadApiController::class, 'show'])->name('api.v1.leads.show');
    Route::post('/leads',       [LeadApiController::class, 'store'])->name('api.v1.leads.store');
    Route::put('/leads/{id}',   [LeadApiController::class, 'update'])->name('api.v1.leads.update');
    Route::delete('/leads/{id}', [LeadApiController::class, 'destroy'])->name('api.v1.leads.destroy');

    // Events
    Route::get('/events', [EventApiController::class, 'index'])->name('api.v1.events.index');
  });
});
