<?php

use Illuminate\Support\Facades\Route;
use App\Models\Tenant;
use App\Http\Controllers\Trades\ServiceLocationController;
use App\Http\Controllers\Trades\JobController;
use App\Http\Controllers\Trades\ScheduleController;
use App\Http\Controllers\Trades\FieldController;
use App\Http\Controllers\Trades\QuoteController;
use App\Http\Controllers\Trades\DashboardController;
use App\Http\Controllers\Trades\ReportsController;
use App\Http\Controllers\Trades\PerformanceController;
use App\Http\Controllers\Trades\ClientController;
use App\Http\Controllers\Trades\SettingsController;
use App\Http\Controllers\Trades\Settings\SettingsHomeController;
use App\Http\Controllers\Trades\Settings\ProfileController;
use App\Http\Controllers\Trades\Settings\OperationsController;
use App\Http\Controllers\Trades\Settings\TimeController;
use App\Http\Controllers\Trades\Settings\WorkSchedulesController;
use App\Http\Controllers\Trades\PtoRequestController;
use App\Http\Controllers\Trades\TradeServicePlanController;
use App\Http\Controllers\Trades\JobTemplateController;
use App\Http\Controllers\Trades\TeamMemberController;
use App\Http\Controllers\Trades\LeadController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Trades\Settings\LeadsController;
use App\Http\Controllers\Trades\ChatController as TradesChatController;

Route::prefix('trades')->name('trades.')->group(function () {

    Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');


    // Office-only business management routes
    Route::middleware(['not-tech'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('performance', [PerformanceController::class, 'index'])->name('performance.index');
        Route::get('performance/{kpi}', [PerformanceController::class, 'show'])->name('performance.show');
        Route::get('settings', [SettingsHomeController::class, 'index'])->name('settings.index');
        Route::get('settings/profile', [ProfileController::class, 'index'])->name('settings.profile');
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
        Route::get('settings/operations', [OperationsController::class, 'index'])->name('settings.operations');
        Route::patch('settings/operations', [OperationsController::class, 'update'])->name('settings.operations.update');
        Route::get('settings/time', [TimeController::class, 'index'])->name('settings.time');
        Route::patch('settings/time', [TimeController::class, 'update'])->name('settings.time.update');
        Route::get('settings/time/work-schedules', [WorkSchedulesController::class, 'index'])
            ->name('settings.time.work-schedules');
        Route::get('settings/time/work-schedules/{user}', [WorkSchedulesController::class, 'edit'])
            ->name('settings.time.work-schedules.edit');
        Route::patch('settings/time/work-schedules/company', [WorkSchedulesController::class, 'updateCompany'])
            ->name('settings.time.work-schedules.company');
        Route::patch('settings/time/work-schedules/{user}', [WorkSchedulesController::class, 'updateUser'])
            ->name('settings.time.work-schedules.user');
        Route::get('settings/leads', [LeadsController::class, 'index'])->name('settings.leads');
        Route::patch('settings/leads/recipients', [LeadsController::class, 'updateRecipients'])->name('settings.leads.recipients');
        Route::patch('settings/leads/mapping', [LeadsController::class, 'updateMapping'])->name('settings.leads.mapping');
        Route::post('settings/leads/test', [LeadsController::class, 'testLead'])->name('settings.leads.test');
        Route::put('settings/pto-approvers', [SettingsController::class, 'updateApprovers'])->name('settings.pto-approvers');
        Route::put('settings/recurring', [SettingsController::class, 'updateRecurring'])->name('settings.recurring');
        Route::put('settings/warranty', [SettingsController::class, 'updateWarranty'])->name('settings.warranty');
        Route::put('settings/work-type', [SettingsController::class, 'updateWorkType'])->name('settings.work-type');
        Route::put('settings/overtime', [SettingsController::class, 'updateOvertime'])->name('settings.overtime');
        Route::put('settings/timezone', [SettingsController::class, 'updateTimezone'])->name('settings.timezone');
        Route::post('settings/holidays', [SettingsController::class, 'storeHoliday'])->name('settings.holidays.store');
        Route::delete('settings/holidays/{holiday}', [SettingsController::class, 'deleteHoliday'])->name('settings.holidays.destroy');
        Route::post('settings/pto-types', [SettingsController::class, 'storeType'])->name('settings.pto-types.store');
        Route::put('settings/pto-types/{type}', [SettingsController::class, 'updateType'])->name('settings.pto-types.update');
        Route::patch('settings/pto-types/{type}/toggle', [SettingsController::class, 'toggleType'])->name('settings.pto-types.toggle');
        Route::post('settings/pto-requests/{ptoRequest}/approve', [SettingsController::class, 'approve'])->name('settings.pto-requests.approve');
        Route::post('settings/pto-requests/{ptoRequest}/deny', [SettingsController::class, 'deny'])->name('settings.pto-requests.deny');

        Route::resource('locations', ServiceLocationController::class);
        Route::get('jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [JobController::class, 'store'])->name('jobs.store');

        Route::get('jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{job}/delete', [JobController::class, 'destroy'])->name('jobs.destroy');

        Route::post('jobs/{job}/convert', [JobController::class, 'convertToProject'])->name('jobs.convert');

        Route::resource('team', TeamMemberController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->names('team')
            ->parameters(['team' => 'team_member']);
        Route::post('team/{team_member}/resend-invite', [TeamMemberController::class, 'resendInvite'])
            ->name('team.resend-invite');
        Route::post('team/{team_member}/send-reset', [TeamMemberController::class, 'sendPasswordReset'])
            ->name('team.send-reset');
        Route::post('team/{team_member}/deactivate', [TeamMemberController::class, 'deactivate'])
            ->name('team.deactivate');
        Route::post('team/{team_member}/reactivate', [TeamMemberController::class, 'reactivate'])
            ->name('team.reactivate');
        Route::post('team/{team_member}/remove', [TeamMemberController::class, 'removeAccess'])
            ->name('team.remove');





        Route::post('service-plans/{service_plan}/overrides', [TradeServicePlanController::class, 'storeOverride'])
            ->name('service-plans.overrides.store');
        Route::delete('service-plans/{service_plan}/overrides/{override}', [TradeServicePlanController::class, 'destroyOverride'])
            ->name('service-plans.overrides.destroy');
        Route::post('service-plans/{service_plan}/generate', [TradeServicePlanController::class, 'generateJob'])
            ->name('service-plans.generate');
        Route::resource('service-plans', TradeServicePlanController::class)
            ->names('service-plans');

        Route::resource('job-templates', JobTemplateController::class)
            ->names('job-templates');

        Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::patch('quotes/{quote}/archive', [QuoteController::class, 'archive'])->name('quotes.archive');
        Route::resource('quotes', QuoteController::class);

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
            Route::get('{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::get('{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('{invoice}', [InvoiceController::class, 'update'])->name('update');
            Route::delete('{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('{invoice}/send', [InvoiceController::class, 'send'])->name('send');
            Route::get('{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
        });

        Route::patch('schedule/{appointment}/assignments/{assignment}', [ScheduleController::class, 'updateAssignment'])
            ->name('schedule.assignments.update');
        Route::get('schedule/events', [ScheduleController::class, 'events'])
            ->name('schedule.events');
        Route::get('schedule/pto-events', [ScheduleController::class, 'ptoEvents'])
            ->name('schedule.pto-events');
        Route::resource('schedule', ScheduleController::class)->parameters(['schedule' => 'appointment']);

        Route::resource('clients', ClientController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->names('clients');

        Route::resource('leads', LeadController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->names('leads');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
            ->name('leads.convert');
        Route::post('leads/{lead}/contact', [LeadController::class, 'contact'])
            ->name('leads.contact');
        Route::post('leads/{lead}/status', [LeadController::class, 'status'])
            ->name('leads.status');
        Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])
            ->name('leads.assign');
        Route::post('leads/{lead}/note', [LeadController::class, 'note'])
            ->name('leads.note');

        Route::post('reports/tech/{user}/force-clockout', [ReportsController::class, 'forceClockOut'])
            ->name('reports.tech.force-clockout');
    });
    Route::middleware(['trades.reports'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');

        Route::get('jobs', [ReportsController::class, 'jobs'])->name('jobs');
        Route::get('schedule', [ReportsController::class, 'schedule'])->name('schedule');
        Route::get('tech', [ReportsController::class, 'tech'])->name('tech');
    });

    Route::get('jobs/{job}', [JobController::class, 'show'])->name('jobs.show');

    // Team Chat Routes
    Route::get('chat', [TradesChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{channel}', [TradesChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{channel}/messages', [TradesChatController::class, 'store'])->name('chat.messages.store');
    Route::get('jobs/{job}/chat', [TradesChatController::class, 'job'])
        ->name('jobs.chat');

    Route::prefix('field')->name('field.')->group(function () {

        // ✅ View routes (GET): tech + office can access (overview)
        Route::middleware(['field-view'])->group(function () {
            Route::get('today', [FieldController::class, 'today'])->name('today');
            Route::get('time', [FieldController::class, 'time'])->name('time');
            Route::get('pto', [FieldController::class, 'pto'])->name('pto');
            Route::get('appointments/{appointment}', [FieldController::class, 'show'])->name('show');
            Route::post('pto-requests', [PtoRequestController::class, 'store'])->name('pto-requests.store');
        });

        // 🔒 Action routes (POST): tech only
        Route::middleware(['tech'])->group(function () {
            Route::post('clock-in', [FieldController::class, 'clockIn'])->name('clock-in');
            Route::post('clock-out', [FieldController::class, 'clockOut'])->name('clock-out');
            Route::post('appointments/{appointment}/start', [FieldController::class, 'startJob'])->name('start');
            Route::post('appointments/{appointment}/stop', [FieldController::class, 'stopJob'])->name('stop');
            Route::post('appointments/{appointment}/notes', [FieldController::class, 'storeNote'])->name('notes.store');
            Route::post('appointments/{appointment}/photos', [FieldController::class, 'storePhoto'])->name('photos.store');
        });
    });
});
