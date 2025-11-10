<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Middleware\CheckRole;

/*
|--------------------------------------------------------------------
| ADMIN (provider/admin roles) — Tenant scoped
| URL: /{tenant}/invoices/...
| Names: tenant.invoices.*
|--------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'tenant', CheckRole::class . ':provider,admin,super_admin,superadmin'])
  ->prefix('{tenant}')
  ->whereNumber('tenant')       // you’re using IDs, not slugs
  ->as('tenant.')
  ->scopeBindings()
  ->group(function () {

    // Index / list
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    // Create
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');

    // Show / PDF
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Send, Delete (POST/DELETE)
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
  });

/*
|--------------------------------------------------------------------
| CLIENT (client role) — Tenant scoped
| URL: /{tenant}/my-invoices/...
| Names: tenant.client_invoices.*
|--------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'tenant', CheckRole::class . ':client'])
  ->prefix('{tenant}')
  ->whereNumber('tenant')
  ->as('tenant.')
  ->scopeBindings()
  ->group(function () {
    Route::get('my-invoices', [ClientInvoiceController::class, 'index'])->name('client_invoices.index');
    Route::get('my-invoices/{invoice}', [ClientInvoiceController::class, 'show'])->name('client_invoices.show');
    Route::get('my-invoices/{invoice}/pdf', [ClientInvoiceController::class, 'pdf'])->name('client_invoices.pdf');
  });

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
