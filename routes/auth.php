<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

Route::middleware(['web', 'guest', 'nocache'])->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware(['web', 'nocache'])
    ->name('logout');

if (app()->environment('local')) {
    Route::get('/_debug/auth', function () {
        return response()->json([
            'host' => request()->getHost(),
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'intended' => session('url.intended'),
            'guards' => [
                'admin'  => auth('admin')->check(),
                'client' => auth('client')->check(),
                'web'    => auth()->check(),
            ],
            'session_cookie' => config('session.cookie'),
            'session_domain' => config('session.domain'),
        ]);
    })->middleware(['nocache']);
}
