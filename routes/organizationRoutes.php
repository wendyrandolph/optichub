<?php
// routes/tenantManagementRoutes.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
  OrganizationController,
  OpportunityController,
  TeamMemberController,
  ClientController,
  TimeEntryController
};
use App\Http\Middleware\CheckRole;
// Note the order: web -> tenant -> setTenantUrlDefaults -> auth -> checkrole
Route::middleware([
  'web',
  'tenant',
  'auth',
  CheckRole::class . ':provider,admin,super_admin,superadmin'
])
  ->prefix('{tenant}')
  ->as('tenant.')
  ->group(function () {

    Route::resource('organizations', OrganizationController::class)->names('organizations');
    Route::resource('opportunities', OpportunityController::class)->names('opportunities');
    Route::resource('team-members', TeamMemberController::class)->names('team-members');
    Route::resource('contacts', ClientController::class)
      ->parameters(['contacts' => 'contact'])
      ->names('contacts');

    // TIME ENTRIES — see note #2 below about controller method names
    Route::resource('time', TimeEntryController::class)
      ->parameters(['time' => 'entry'])
      ->names('time');
  });
