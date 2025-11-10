<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Auth;      // ← add this
use Illuminate\Http\Request;              // ← and this


// 1. GUEST ROUTES
Route::middleware('guest')->group(function () {
  // Login Form
  Route::get('/login', [LoginController::class, 'create'])->name('login');

  // Handle Login Submission
  Route::post('/login', [LoginController::class, 'store']);

  // --- RESET PASSWORD ROUTES (Standardized) ---
  // Show Reset Password Form
  Route::get('/account/reset-password', [ResetPasswordController::class, 'resetPasswordForm'])
    ->name('password.request'); // Naming convention for requesting reset

  // Handle Password Reset Submission
  Route::post('/account/reset-password', [ResetPasswordController::class, 'handlePasswordReset'])
    ->name('password.email'); // Naming convention for sending the reset email


});
Route::post('/logout', function (Request $request) {
  Auth::guard('web')->logout();          // explicit guard, just in case
  $request->session()->invalidate();     // kill session
  $request->session()->regenerateToken(); // new CSRF token
  return redirect('/');                  // send to public homepage (NOT named 'home')
})->middleware('web')->name('logout');
