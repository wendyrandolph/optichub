<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportsController;

// Tenant-scoped reports; routes/web.php already applies /app/{tenant} prefix + auth/web
Route::prefix('reports')
  ->as('reports.')
  ->middleware(['no-trades-reports'])
  ->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('/export', [ReportsController::class, 'export'])->name('export');

    // Finance
    Route::get('/invoices', [ReportsController::class, 'invoices'])->name('invoices');
    Route::get('/collected', [ReportsController::class, 'collected'])->name('collected');
    Route::get('/forecast', [ReportsController::class, 'forecast'])->name('forecast');
    Route::get('/ar-aging', [ReportsController::class, 'arAging'])->name('ar_aging');

    // Operations
    Route::get('/tasks-due', [ReportsController::class, 'tasksDue'])->name('tasks.due');
    Route::get('/tasks/on-time', [ReportsController::class, 'tasksOnTime'])->name('tasks-on-time');
    Route::get('/projects-stale', [ReportsController::class, 'projectsStale'])->name('projects_stale');

    // CRM / Email
    Route::get('/leads/new', [ReportsController::class, 'leadsNew'])->name('leads.new');
    Route::get('/emails/activity', [ReportsController::class, 'emailsActivity'])->name('emails.activity');

    // Bottlenecks
    Route::get('/pipeline-aging', [ReportsController::class, 'pipelineAging'])->name('pipeline_aging');
    Route::get('/wip', [ReportsController::class, 'wip'])->name('wip');
    Route::get('/throughput', [ReportsController::class, 'throughput'])->name('throughput');
  });
