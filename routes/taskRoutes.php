<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::middleware(['web', 'auth', 'tenant'])
  ->prefix('{tenant:id}')
  ->as('tenant.')
  ->scopeBindings()
  ->group(function () {
    Route::resource('tasks', TaskController::class)->names('tasks');
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.add');
  });
