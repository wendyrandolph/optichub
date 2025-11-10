<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

// It is critical that you paste these routes inside the group that defines
// the 'admin' prefix and the 'admin.' name prefix, for example:

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
  // PASTE ALL THE ROUTES BELOW HERE:

  // Tenant Management Routes (Resource-like routes)
  Route::get('tenants', [AdminController::class, 'indexTenants'])->name('tenants.index');
  Route::get('tenants/create', [AdminController::class, 'createTenant'])->name('tenants.create');
  Route::post('tenants', [AdminController::class, 'storeTenant'])->name('tenants.store');
  Route::get('tenants/{tenant}', [AdminController::class, 'showTenant'])->name('tenants.show');
  Route::delete('tenants/{tenant}', [AdminController::class, 'destroyTenant'])->name('tenants.destroy');

  // These routes link up the other placeholder methods in your AdminController for completeness
  Route::get('reports', [AdminController::class, 'reports'])->name('reports');
  Route::get('settings', [AdminController::class, 'settings'])->name('settings');


  // END OF PASTE BLOCK
});

// You may need to uncomment the Route::get('reports', ...) lines if they are not yet defined.
