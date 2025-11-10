<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Services\StripeService; // NEW: For Stripe API calls
use App\Mail\InvoiceMailable;   // NEW: For clean email sending
use App\Http\Requests\Invoice\StoreInvoiceRequest; // NEW: For validation/CSRF
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class InvoiceController extends Controller
{
  protected StripeService $stripeService;

  // Use Dependency Injection instead of the PDO constructor
  public function __construct(StripeService $stripeService)
  {
    $this->middleware('auth');
    $this->stripeService = $stripeService;
  }

  /**
   * Display a listing of the invoices.
   * Replaces index()
   *
   * @return \Illuminate\View\View
   */
  public function index(): View
  {
    //$this->authorize('viewAny', Invoice::class);

    // Fetch invoices for the current tenant
    $invoices = Invoice::with('client') // Eager load client name
      ->latest()       // Order by created_at DESC
      ->get();

    return view('invoices.index', compact('invoices'));
  }

  /**
   * Display the specified invoice.
   * Replaces show($id)
   *
   * @param \App\Models\Invoice $invoice (Route Model Binding)
   * @return \Illuminate\View\View
   */
  public function show(Invoice $invoice): View
  {
    // 1. Authorization: Ensures the user can view this invoice
    $this->authorize('view', $invoice);

    // 2. Data Retrieval (Items and Client are eager loaded)
    $invoice->load(['items', 'client']);

    return view('invoices.view', compact('invoice'));
    // Note: The original code passed 'invoices' and 'items'. We only pass 'invoice'
    // and access $invoice->items in the Blade view.
  }
  // app/Models/Invoice.php
  public function client() // keep the name "client" so you don't touch a lot of views
  {
    // client_id now references contacts.id
    return $this->belongsTo(Contact::class, 'client_id');
  }

  /**
   * Show the form for creating a new invoice.
   * Replaces create() (GET part)
   *
   * @return \Illuminate\View\View
   */
  public function create(): View
  {
    //$this->authorize('create', Invoice::class);

    $clients = Client::where('tenant_id', auth()->user()->tenant_id)
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName']);

    return view('invoices.create', compact('clients'));
  }

  /**
   * Store a newly created invoice and its line items.
   * Replaces create() (POST part)
   *
   * @param \App\Http\Requests\Invoice\StoreInvoiceRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(StoreInvoiceRequest $request)
  {
    $validatedData = $request->validated();
    $tenantId = Auth::user()->tenant_id;
    $invoice = null;

    try {
      DB::beginTransaction();

      // 1. Create the Invoice (initial save)
      $invoice = Invoice::create(array_merge($validatedData, [
        'tenant_id' => $tenantId,
        'status' => $validatedData['status'] ?? 'Draft',
        // Temporarily remove stripe_link as it's added later
      ]));

      $totalCents = 0;
      $itemsToCreate = [];

      // 2. Save Line Items and Calculate Total
      foreach ($validatedData['items'] as $itemData) {
        // Ensure data types are correct before calculation
        $quantity = (float)($itemData['quantity'] ?? 0);
        $unitPrice = (float)($itemData['unit_price'] ?? 0);
        $lineTotal = $quantity * $unitPrice;
        $totalCents += $lineTotal * 100; // Stripe uses cents

        $itemsToCreate[] = array_merge($itemData, [
          'invoice_id' => $invoice->id,
          'tenant_id' => $tenantId,
        ]);
      }
      InvoiceItem::insert($itemsToCreate); // Efficient batch insert

      // 3. Generate Stripe link (Using injected Service)
      $stripeLink = $this->stripeService->createCheckoutSession(
        $totalCents,
        'Invoice #' . $invoice->invoice_number,
        $invoice->id
      );

      // 4. Update the invoice with the Stripe link and final total
      $invoice->update([
        'stripe_link' => $stripeLink,
        'total_cents' => $totalCents, // New column to store the calculated total
      ]);

      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error("[invoices.store] Transaction failed: " . $e->getMessage());

      return Redirect::route('invoices.create')
        ->withInput()
        ->with('error', 'Invoice creation failed due to a system error.');
    }

    // 5. Redirect
    return Redirect::route('invoices.show', $invoice->id)
      ->with('success', 'Invoice created and Stripe link generated successfully.');
  }

  /**
   * Delete an invoice and its associated line items.
   * Replaces delete($id)
   *
   * @param \App\Models\Invoice $invoice (Route Model Binding)
   * @return \Illuminate\Http\RedirectResponse
   */
  public function delete(Invoice $invoice)
  {
    $this->authorize('delete', $invoice);

    // Eloquent's cascading delete (on the model or database) is recommended.
    // If not using cascading deletes:
    $invoice->items()->delete();

    $invoice->delete();

    return Redirect::route('invoices.index')
      ->with('success', 'Invoice deleted.');
  }

  /**
   * Send the invoice email to the client.
   * Replaces send($id)
   *
   * @param \App\Models\Invoice $invoice
   * @return \Illuminate\Http\RedirectResponse
   */
  public function send(Invoice $invoice)
  {
    $this->authorize('update', $invoice);

    // Eager load client relationship
    $invoice->load(['client', 'items']);
    $client = $invoice->client;

    if (!$client || !$client->email) {
      return Redirect::route('invoices.show', $invoice)
        ->with('error', 'Client email address is missing or invalid.');
    }

    try {
      // Replaces procedural mail() function with Laravel Mailable
      Mail::to($client->email)->send(new InvoiceMailable($invoice, $client));

      // Update status (optional, but good practice)
      $invoice->update(['status' => 'Sent']);

      return Redirect::route('invoices.show', $invoice)
        ->with('success', 'Invoice email sent successfully.');
    } catch (\Throwable $e) {
      Log::error("[invoices.send] Email failed: " . $e->getMessage());

      return Redirect::route('invoices.show', $invoice)
        ->with('error', 'Email sending failed.');
    }
  }
}
