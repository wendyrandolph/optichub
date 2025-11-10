<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityController;
use App\Http\Middleware\CheckRole;


Route::middleware(['auth', CheckRole::class . ':admin,super_admin,provider'])
  ->get('/home', [AdminProfileController::class, 'index'])
  ->name('home');

// In app/Http/Kernel.php, define an alias once:
// 'role' => \App\Http\Middleware\CheckRole::class,

Route::middleware(['auth', 'verified', CheckRole::class . ':admin,super_admin,provider'])
  ->prefix('admin')
  ->as('admin.') // <-- gives every route the 'admin.' name prefix
  ->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users',     [AdminController::class, 'users'])->name('users');

    Route::get('/settings',  [AdminController::class, 'settings'])->name('settings');

    // Admin profile management
    Route::get('/create',        [AdminProfileController::class, 'createForm'])->name('create');
    Route::post('/create',       [AdminProfileController::class, 'create'])->name('store');
    Route::get('/edit/{id}',     [AdminProfileController::class, 'editForm'])->name('edit');
    Route::post('/update/{id}',  [AdminProfileController::class, 'update'])->name('update');
    Route::post('/delete/{id}',  [AdminProfileController::class, 'delete'])->name('delete');

    // These were named 'client.*' but live under /admins — rename to keep names consistent
    Route::get('/profile',  [AdminController::class, 'profile'])->name('profile');
    Route::get('/invoices', [AdminController::class, 'invoices'])->name('invoices');
  });
