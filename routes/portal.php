<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientPortal\PortalAccessController;

Route::name('portal.')->group(function () {
    Route::get('access/{token}', [PortalAccessController::class, 'show'])
      ->name('access.show');

    Route::post('access/{token}/approve', [PortalAccessController::class, 'approve'])
      ->name('access.approve');
});
