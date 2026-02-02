<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Tenant\TeamMemberController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Tenant\ClientCompanyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalTemplateController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\Client\ProjectConversationController;
use App\Http\Controllers\Client\ProjectMessageController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MockPaymentController;
use App\Http\Controllers\Tenant\ScheduleController;


/*
        |--------------------------------------------------------------------------
        | Core Work Management
        |--------------------------------------------------------------------------
        */

// Projects
Route::resource('projects', ProjectController::class)
  ->names('projects');
Route::post('projects/{project}/milestones', [ProjectController::class, 'storeMilestoneInvoice'])
  ->name('projects.milestones.store');
Route::put('projects/{project}/milestones/{invoice}', [ProjectController::class, 'updateMilestoneInvoice'])
  ->name('projects.milestones.update');
Route::delete('projects/{project}/milestones/{invoice}', [ProjectController::class, 'destroyMilestoneInvoice'])
  ->name('projects.milestones.destroy');
// Mark project completed (status -> closed, end_date -> today)
Route::post('projects/{project}/complete', [ProjectController::class, 'complete'])
  ->name('projects.complete');
// Reopen project (status -> open, clear end_date)
Route::post('projects/{project}/reopen', [ProjectController::class, 'reopen'])
  ->name('projects.reopen');

// Tasks
Route::resource('tasks', TaskController::class)
  ->names('tasks');
Route::post('tasks/{task}/start', [TaskController::class, 'start'])
  ->name('tasks.start');
Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])
  ->name('tasks.complete');
Route::post('tasks/{task}/archive', [TaskController::class, 'archive'])
  ->name('tasks.archive');
Route::post('tasks/{task}/timer/start', [TaskController::class, 'timerStart'])
  ->name('tasks.timer.start');
Route::post('tasks/{task}/timer/stop', [TaskController::class, 'timerStop'])
  ->name('tasks.timer.stop');
Route::post('tasks/{task}/approve', [TaskController::class, 'approveTask'])
  ->name('tasks.approve');
Route::post('tasks/{task}/request-changes', [TaskController::class, 'requestChanges'])
  ->name('tasks.request_changes');

// My Schedule (creative workspace)
Route::get('schedule', [ScheduleController::class, 'index'])
  ->name('schedule.index');

// Time entries
Route::resource('time', TimeEntryController::class)
  ->parameters(['time' => 'entry'])
  ->names('time');
Route::post('time/bulk-bill', [TimeEntryController::class, 'bulkBill'])
  ->name('time.bulk-bill');
Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
  ->name('invoices.payments.store')
  ->middleware('capability:PAYMENTS_FRAMEWORK');
Route::get('pay/mock/{invoice}', [MockPaymentController::class, 'show'])
  ->name('payments.mock.show')
  ->middleware('capability:PAYMENTS_MOCK_PROVIDERS');
Route::post('pay/mock/{invoice}', [MockPaymentController::class, 'simulate'])
  ->name('payments.mock.simulate')
  ->middleware('capability:PAYMENTS_MOCK_PROVIDERS');

// Calendar
Route::get('calendar', [CalendarController::class, 'index'])
  ->name('calendar.index');
Route::get('calendar/events', [\App\Http\Controllers\CalendarEventsController::class, 'index'])
  ->name('calendar.events');
Route::resource('meetings', MeetingController::class)
  ->only(['create', 'store', 'show'])
  ->names('meetings');

// Email logs (Gmail sync)
Route::get('email-logs', [\App\Http\Controllers\EmailLogController::class, 'index'])
  ->name('email-logs.index');
Route::post('email-logs/{log}/ignore', [\App\Http\Controllers\EmailLogController::class, 'ignore'])
  ->name('email-logs.ignore');
Route::post('email-logs/{log}/link-contact', [\App\Http\Controllers\EmailLogController::class, 'linkContact'])
  ->name('email-logs.link-contact');


/*
        |--------------------------------------------------------------------------
        | CRM: Organizations, Companies, Contacts, Leads, Opportunities
        |--------------------------------------------------------------------------
        */

// Higher-level organizations (if you're using these separately from companies)
Route::resource('organizations', OrganizationController::class)
  ->names('organizations');

// Tenant's own client companies (client_companies table)
// Using resource for consistency; we skip show/destroy for now
Route::resource('companies', ClientCompanyController::class)
  ->except(['destroy'])
  ->names('companies');
// Services tied to companies
Route::get('companies/{company}/services', [ServiceController::class, 'index'])->name('companies.services.index');
Route::post('companies/{company}/services', [ServiceController::class, 'store'])->name('companies.services.store');
Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::post('services/{service}/logs', [ServiceController::class, 'storeLog'])->name('services.logs.store');
Route::patch('services/logs/{log}', [ServiceController::class, 'updateLog'])->name('services.logs.update');
Route::delete('services/logs/{log}', [ServiceController::class, 'destroyLog'])->name('services.logs.destroy');
Route::patch('services/{service}', [ServiceController::class, 'update'])->name('services.update');

/*
|--------------------------------------------------------------------------
| Tenant Support Center
|--------------------------------------------------------------------------
*/
Route::prefix('support')
  ->as('support.')
  ->group(function () {
    Route::get('/', [\App\Http\Controllers\Tenant\SupportCenterController::class, 'index'])
      ->name('index');
    Route::get('/kb', [\App\Http\Controllers\Tenant\KnowledgeBaseController::class, 'index'])
      ->name('kb.index');
    Route::get('/kb/{article:slug}', [\App\Http\Controllers\Tenant\KnowledgeBaseController::class, 'show'])
      ->name('kb.show');

    Route::get('/tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'index'])
      ->name('tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'create'])
      ->name('tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'store'])
      ->name('tickets.store');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'show'])
      ->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'reply'])
      ->name('tickets.reply');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'resolve'])
      ->name('tickets.resolve');
  });
Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

// Leads
Route::resource('leads', LeadController::class)
  ->names('leads');
Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])
  ->name('leads.notes.store');
Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
  ->name('leads.convert');

// Opportunities
Route::resource('opportunities', OpportunityController::class)
  ->names('opportunities');
Route::post('opportunities/{opportunity}/activities', [OpportunityController::class, 'storeActivity'])
  ->name('opportunities.activities.store');
Route::post('opportunities/{opportunity}/convert', [OpportunityController::class, 'convertToProject'])
  ->name('opportunities.convert');
Route::post('opportunities/{opportunity}/followup', [OpportunityController::class, 'updateFollowup'])
  ->name('opportunities.followup');

// Proposals
Route::get('proposals', [ProposalController::class, 'index'])->name('proposals.index');
Route::get('proposals/create', [ProposalController::class, 'createForm'])->name('proposals.create');
Route::post('proposals', [ProposalController::class, 'store'])->name('proposals.store');
Route::get('proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
Route::put('proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
Route::get('proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
Route::get('proposals/{proposal}/pdf', [ProposalController::class, 'pdf'])->name('proposals.pdf');
Route::post('proposals/{proposal}/send', [ProposalController::class, 'send'])->name('proposals.send');
Route::post('proposals/{proposal}/duplicate', [ProposalController::class, 'duplicate'])->name('proposals.duplicate');
Route::delete('proposals/{proposal}', [ProposalController::class, 'archive'])->name('proposals.archive');
Route::post('proposals/{proposal}/templates', [ProposalTemplateController::class, 'fromProposal'])->name('proposals.templates.store');

// Contract templates
Route::get('contracts/templates', [ContractTemplateController::class, 'index'])->name('contracts.templates.index');
Route::get('contracts/templates/create', [ContractTemplateController::class, 'create'])->name('contracts.templates.create');
Route::post('contracts/templates', [ContractTemplateController::class, 'store'])->name('contracts.templates.store');
Route::get('contracts/templates/{template}/edit', [ContractTemplateController::class, 'edit'])->name('contracts.templates.edit');
Route::put('contracts/templates/{template}', [ContractTemplateController::class, 'update'])->name('contracts.templates.update');
Route::delete('contracts/templates/{template}', [ContractTemplateController::class, 'destroy'])->name('contracts.templates.destroy');

// Proposal templates
Route::get('proposal-templates', [ProposalTemplateController::class, 'index'])->name('proposal-templates.index');
Route::get('proposal-templates/create', [ProposalTemplateController::class, 'create'])->name('proposal-templates.create');
Route::post('proposal-templates', [ProposalTemplateController::class, 'store'])->name('proposal-templates.store');
Route::get('proposal-templates/{proposal_template}/edit', [ProposalTemplateController::class, 'edit'])->name('proposal-templates.edit');
Route::put('proposal-templates/{proposal_template}', [ProposalTemplateController::class, 'update'])->name('proposal-templates.update');
Route::delete('proposal-templates/{proposal_template}', [ProposalTemplateController::class, 'destroy'])->name('proposal-templates.destroy');

// Quick company create (AJAX from lead form)
Route::post('companies/quick-store', [\App\Http\Controllers\Tenant\ClientCompanyController::class, 'quickStore'])
  ->name('companies.quick-store');

// Contacts (individual people)
Route::resource('contacts', ClientController::class)
  ->parameters(['contacts' => 'contact'])
  ->names('contacts');
Route::post('contacts/{contact}/create-login', [ClientController::class, 'createLogin'])
  ->name('contacts.create-login');
Route::post('contacts/{contact}/resend-login', [ClientController::class, 'resendLoginEmail'])
  ->name('contacts.resend-login');
Route::post('contacts/{contact}/magic-link', [ClientController::class, 'sendMagicLink'])
  ->name('contacts.magic-link');
Route::post('contacts/{contact}/magic-link-task', [ClientController::class, 'createMagicLinkTask'])
  ->name('contacts.magic-link-task');
Route::post('contacts/{contact}/notes', [\App\Http\Controllers\ContactNotesController::class, 'store'])
  ->name('contacts.notes.store');
Route::post('contacts/{contact}/followup', [ClientController::class, 'updateFollowup'])
  ->name('contacts.followup');
Route::post('contacts/{contact}/files', [\App\Http\Controllers\ContactFilesController::class, 'store'])
  ->name('contacts.files.store');
Route::delete('contacts/{contact}/files/{file}', [\App\Http\Controllers\ContactFilesController::class, 'destroy'])
  ->name('contacts.files.destroy');
Route::get('contacts/files/{file}/download', [\App\Http\Controllers\ContactFilesController::class, 'download'])
  ->middleware('signed')
  ->name('contacts.files.download');
Route::post('contacts/{contact}/notes/{note}/pin', [\App\Http\Controllers\ContactNotesController::class, 'pin'])
  ->name('contacts.notes.pin');
Route::delete('contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNotesController::class, 'destroy'])
  ->name('contacts.notes.destroy');

// Quick search API for modal
Route::get('search/quick', [SearchController::class, 'quick'])
  ->name('search.quick');

// Team members
Route::resource('team-members', TeamMemberController::class)
  ->names('team-members');
Route::post('team-members/{team_member}/resend-invite', [TeamMemberController::class, 'resendInvite'])
  ->name('team-members.resend-invite');
Route::post('team-members/{team_member}/send-reset', [TeamMemberController::class, 'sendPasswordReset'])
  ->name('team-members.send-reset');
Route::post('team-members/{team_member}/deactivate', [TeamMemberController::class, 'deactivate'])
  ->name('team-members.deactivate');
Route::post('team-members/{team_member}/reactivate', [TeamMemberController::class, 'reactivate'])
  ->name('team-members.reactivate');
Route::post('team-members/{team_member}/remove', [TeamMemberController::class, 'removeAccess'])
  ->name('team-members.remove');

Route::prefix('projects/{project}')->group(function () {
  Route::get('conversation', [ProjectConversationController::class, 'show'])->name('projects.conversation.show');
  Route::post('conversation/approve', [ProjectConversationController::class, 'approve'])->name('projects.conversation.approve');
  Route::post('conversation/request-changes', [ProjectConversationController::class, 'requestChanges'])->name('projects.conversation.request_changes');
  Route::post('conversation/decision', [ProjectConversationController::class, 'decision'])->name('projects.conversation.decision');

  Route::get('messages', [ProjectMessageController::class, 'index'])->name('projects.messages.index');
  Route::post('messages', [ProjectMessageController::class, 'store'])->name('projects.messages.store');
  Route::put('messages/{message}', [ProjectMessageController::class, 'update'])->name('projects.messages.update');
  Route::delete('messages/{message}', [ProjectMessageController::class, 'destroy'])->name('projects.messages.destroy');
});

// Internal chat
Route::get('chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
Route::get('chat/dm', [\App\Http\Controllers\ChatController::class, 'dmIndex'])->name('chat.dm.index');
Route::get('chat/dm/{user}', [\App\Http\Controllers\ChatController::class, 'dmStart'])->name('chat.dm.start');
Route::get('chat/{channel}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
Route::post('chat/{channel}/messages', [\App\Http\Controllers\ChatController::class, 'store'])->name('chat.messages.store');
Route::patch('chat/{channel}/messages/{message}', [\App\Http\Controllers\ChatController::class, 'updateMessage'])
    ->name('chat.messages.update');
Route::delete('chat/{channel}/messages/{message}', [\App\Http\Controllers\ChatController::class, 'destroyMessage'])
    ->name('chat.messages.destroy');
Route::post('chat/{channel}/archive', [\App\Http\Controllers\ChatController::class, 'archive'])->name('chat.archive');
Route::get('projects/{project}/chat', [\App\Http\Controllers\ChatController::class, 'project'])->name('chat.project');
