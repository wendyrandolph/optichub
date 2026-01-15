<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\PaymentIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ClientInvoiceController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth:client');
  }

  public function index()
  {
    $user = auth('client')->user();

    if (!$user || $user->role !== 'client') {
      return redirect()->route('login');
    }
    $contactId = $user->contact_id;
    $invoices = Invoice::query()
      ->where('tenant_id', $user->tenant_id)
      ->where('contact_id', $contactId)
      ->orderByDesc('issue_date')
      ->with('client')
      ->get();


    return view('portal.invoices.index', [
      'invoices' => $invoices,
      'client'   => $user->client ?? null,
    ]);
  }

  public function show(Invoice $invoice)
  {
    $user = Auth::guard('client')->user();

    if (!$user) {
      abort(403);
    }

    Gate::authorize('portal-view-invoice', $invoice);
    $tenant = $user->tenant;

    $invoice->load(['items', 'client']);
    $manualMethods = PaymentIntegration::query()
      ->where('tenant_id', $tenant->id)
      ->where('provider', 'manual')
      ->where(function ($query) {
        $query->where('is_enabled', true)->orWhere('active', true);
      })
      ->orderBy('label')
      ->get();

    return view('portal.invoices.show', [
      'tenant'  => $tenant,
      'invoice' => $invoice,
      'items'   => $invoice->items,
      'manualMethods' => $manualMethods,
    ]);
  }
  public function invoices()
  {
    $user = auth()->user();

    if (!$user || $user->role !== 'client') {
      return redirect()->route('login');
    }

    $client = Client::findOrFail($user->contact_id);

    $client->load('tenant');

    $invoices = \App\Models\Invoice::where('contact_id', $client->id)
      ->orderBy('issue_date', 'desc')
      ->get();

    return view('portal.invoices.index', [
      'client'   => $client,
      'tenant'   => $client->tenant,
      'invoices' => $invoices,
    ]);
  }

  public function pdf(Invoice $invoice)
  {
    Gate::authorize('portal-view-invoice', $invoice);

    $invoice->load(['contact', 'lineItems', 'payments']);

    // View: resources/views/client/invoices/pdf.blade.php
    $pdf = Pdf::loadView('client.invoices.pdf', [
      'invoice' => $invoice,
    ]);

    $fileName = 'Invoice-' . ($invoice->number ?? $invoice->id) . '.pdf';

    return $pdf->download($fileName);
  }

  public function pay(Request $request, Invoice $invoice)
  {
    $client    = Auth::guard('client')->user();
    Gate::authorize('portal-view-invoice', $invoice);

    if ($invoice->is_paid) {
      return redirect()
        ->route('portal.invoices.show', $invoice)
        ->with('status', 'This invoice is already paid.');
    }

    $amount = (float) $request->input('amount', $invoice->balance_due ?? $invoice->total);
    $tenant = $client->tenant;
    $balance = (float) ($invoice->balance_due ?? $invoice->total ?? 0);
    $minPartial = (float) config('optichub.portal_min_partial_payment', 1);

    if (! $tenant?->allow_partial_payments) {
      if (abs($amount - $balance) > 0.0001) {
        return redirect()
          ->route('portal.invoices.show', $invoice)
          ->with('status', 'Full payment is required for this invoice.');
      }
    } else {
      if ($amount < $minPartial || $amount > $balance + 0.0001) {
        return redirect()
          ->route('portal.invoices.show', $invoice)
          ->with('status', 'Invalid payment amount.');
      }
    }

    \App\Models\InvoicePayment::create([
      'tenant_id' => $tenant->id,
      'invoice_id' => $invoice->id,
      'amount' => $amount,
      'method' => 'portal',
      'paid_at' => now(),
    ]);

    $paid = (float) \App\Models\InvoicePayment::where('tenant_id', $tenant->id)
      ->where('invoice_id', $invoice->id)
      ->sum('amount');

    $invoice->balance_due = max(($invoice->total ?? 0) - $paid, 0);
    if ($invoice->balance_due <= 0.0001) {
      $invoice->status = 'paid';
    } elseif ($paid > 0) {
      $invoice->status = 'partial';
    }
    $invoice->save();

    return redirect()
      ->route('portal.invoices.show', $invoice)
      ->with('status', 'Payment recorded successfully.');
  }

  public function download(Invoice $invoice)
  {
    Gate::authorize('portal-view-invoice', $invoice);

    $invoice->load(['contact', 'lineItems', 'payments']);

    $pdf = Pdf::loadView('client.invoices.pdf', [
      'invoice' => $invoice,
    ]);

    $fileName = 'Invoice-' . ($invoice->number ?? $invoice->id) . '.pdf';

    return $pdf->download($fileName);
  }
}
