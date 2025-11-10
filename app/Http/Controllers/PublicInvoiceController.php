<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PublicInvoiceController extends Controller
{
  /**
   * Display a thank you page after an invoice payment.
   * * Uses Route Model Binding to automatically retrieve the Invoice model 
   * based on the ID provided in the route, handling 404 errors automatically.
   * Replaces the original thankYou($id) method.
   *
   * @param \App\Models\Invoice $invoice The invoice model fetched by ID.
   * @return \Illuminate\View\View
   */
  public function thankYou(Invoice $invoice): View
  {
    // Eager load the related invoice items to avoid an N+1 query issue.
    // This replaces the manual call to $this->itemModel->getByInvoiceId($id).
    $invoice->load('items');

    // Calculate the total from the collection of items. 
    // This is highly efficient and replaces the manual $this->invoiceModel->getTotalById($id) call.
    // Assumes InvoiceItem model has an 'amount' column.
    $total = $invoice->items->sum('amount');

    // Return a Blade view with the necessary data.
    // This replaces the procedural require_once statement.
    return view('public.invoice-thank-you', [
      'invoice' => $invoice,
      'items' => $invoice->items, // The collection of InvoiceItem models
      'total' => $total,
    ]);
  }
}
