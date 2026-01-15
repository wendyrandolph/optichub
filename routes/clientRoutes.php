<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\ClientFileController;
use App\Http\Controllers\ClientPortal\ProjectMessagesController;
use App\Http\Controllers\ClientPortal\ProjectController as PortalProjectController;
use App\Http\Controllers\ClientSettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Client\ProjectConversationController;
use App\Http\Controllers\ProjectController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:client'])
  ->name('portal.')
  ->group(function () {

    // DASHBOARD: /portal -> route('portal.dashboard')
    Route::get('/', [ClientController::class, 'portal'])
      ->name('dashboard');

    // Client projects
    Route::get('/projects', [PortalProjectController::class, 'index'])
      ->name('projects.index');


    // Client invoices
    Route::get('/invoices', [ClientInvoiceController::class, 'index'])
      ->name('invoices.index');

    Route::get('/invoices/{invoice}', [ClientInvoiceController::class, 'show'])
      ->name('invoices.show');

    Route::get('/invoices/{invoice}/pdf', [ClientInvoiceController::class, 'pdf'])
      ->name('invoices.pdf');

    Route::post('/invoices/{invoice}/pay', [ClientInvoiceController::class, 'pay'])
      ->name('invoices.pay');


    Route::post('/invoices/{invoice}/download', [ClientInvoiceController::class, 'download'])
      ->name('invoices.download');


    Route::get('/form-thank-you', [ClientController::class, 'formThankYou'])
      ->name('form.thank_you');


    Route::get('/project/{id}', [ClientController::class, 'viewProjectDetails'])
      ->name('projects.show');

    Route::get('/projects/{project}/messages', [ProjectMessagesController::class, 'show'])
      ->name('projects.messages.index');

    Route::post('/projects/{project}/messages', [ProjectMessagesController::class, 'store'])
      ->name('projects.messages.store');

    Route::get('projects/{project}/conversation', [ProjectConversationController::class, 'show'])
      ->name('projects.conversation.show');

    Route::post('projects/{project}/conversation/approve', [ProjectConversationController::class, 'approve'])
      ->name('portal.projects.conversation.approve');

    Route::post('projects/{project}/conversation/changes', [ProjectConversationController::class, 'requestChanges'])
      ->name('portal.projects.conversation.changes');

    Route::get('/task/{task_id}', [ClientController::class, 'viewTaskComments'])
      ->name('tasks.comments.index');

    Route::post('/task/{task_id}/comment', [TaskController::class, 'addClientComment'])
      ->name('tasks.comments.store');

    Route::get('/task/{task_id}/upload', [TaskController::class, 'showUploadForm'])
      ->name('tasks.upload.create');

    Route::post('/task/{task_id}/upload', [TaskController::class, 'handleUpload'])
      ->name('tasks.upload.store');

    Route::get('/forgot-password', function () {
      return view('auth.passwords.email');
    })->name('password.request');

    //Client Files
    Route::get('/files', [ClientFileController::class, 'index'])
      ->name('files.index');

    Route::get('/files/{file}/download', [ClientFileController::class, 'download'])
      ->name('files.download');
    Route::get('/messages', [ProjectMessagesController::class, 'index'])
      ->name('messages.index');


    // Account Settings 
    Route::get('/settings', [ClientSettingsController::class, 'edit'])
      ->name('settings.edit');

    Route::put('/settings/profile', [ClientSettingsController::class, 'updateProfile'])
      ->name('settings.profile.update');

    Route::put('/settings/password', [ClientSettingsController::class, 'updatePassword'])
      ->name('settings.password.update');
  });
