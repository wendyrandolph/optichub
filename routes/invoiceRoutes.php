<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\Tenant\SubscriptionInvoiceController;
use App\Http\Middleware\CheckRole;

/*
|--------------------------------------------------------------------
| ADMIN (provider/admin roles) — Tenant scoped
| URL: /{tenant}/invoices/...
| Names: tenant.invoices.*
|--------------------------------------------------------------------
*/


// Index / list
Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');

// Create
Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');

// Edit/Update
Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

// Show / PDF
Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
  ->name('invoices.pdf');


// Send, Delete (POST/DELETE)
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

// Recurring invoices
Route::get('invoices/recurring', [RecurringInvoiceController::class, 'index'])->name('invoices.recurring.index');
Route::get('invoices/recurring/create', [RecurringInvoiceController::class, 'create'])->name('invoices.recurring.create');
Route::post('invoices/recurring', [RecurringInvoiceController::class, 'store'])->name('invoices.recurring.store');
Route::post('invoices/recurring/{recurring}/toggle', [RecurringInvoiceController::class, 'toggle'])
  ->name('invoices.recurring.toggle');

// Platform subscription invoices (for the tenant's own Optic Hub billing)
Route::get('subscription/invoices', [SubscriptionInvoiceController::class, 'index'])
  ->name('subscription.invoices.index');


/*
|--------------------------------------------------------------------
| CLIENT (client role) — Tenant scoped
| URL: /{tenant}/my-invoices/...
| Names: tenant.client_invoices.*
|--------------------------------------------------------------------
*/

Route::get('my-invoices', [ClientInvoiceController::class, 'index'])->name('client_invoices.index');
Route::get('my-invoices/{invoice}', [ClientInvoiceController::class, 'show'])->name('client_invoices.show');
Route::get('my-invoices/{invoice}/pdf', [ClientInvoiceController::class, 'pdf'])->name('client_invoices.pdf');


/*
|--------------------------------------------------------------------
| PUBLIC (no auth) — Thank you / payment confirmation
| URL: /invoice/thank-you/{invoice}
| Name: public.invoice.thankyou
| Use signed URLs if you can.
|--------------------------------------------------------------------
*/
Route::middleware(['web']) // consider ->middleware('signed') if you generate signed URLs
  ->get('invoice/thank-you/{invoice}', [PublicInvoiceController::class, 'thankYou'])
  ->name('public.invoice.thankyou');
