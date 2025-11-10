<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvoicePdfController extends Controller
{
  /**
   * Protect the controller with authentication and policies.
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Generates and streams a PDF for the specified invoice.
   * Replaces generate($id)
   *
   * @param \App\Models\Invoice $invoice (Route Model Binding automatically fetches the invoice)
   * @return \Illuminate\Http\Response
   */
  public function generate(Invoice $invoice)
  {
    // 1. Authorization Check (Ensures the user can view this invoice)
    $this->authorize('view', $invoice);

    // 2. Data Retrieval (Eager load relationships, replacing manual model lookups)
    // This is much cleaner than the old method:
    // $invoice = $invoiceModel->getById($id);
    // $items = $itemModel->getByInvoiceId($id);
    // $client = $clientModel->find($invoice['client_id']);
    $invoice->load(['client', 'items']);

    // 3. HTML Rendering (Using Laravel's View facade instead of ob_start/require)
    // We pass the Eloquent model object directly to the Blade view.
    $html = view('invoices.pdf', [
      'invoice' => $invoice,
      // $invoice->items and $invoice->client are available automatically
    ])->render();

    // 4. Dompdf Setup and Generation
    $options = new Options();
    // Allow loading of remote CSS/images if needed for the PDF (e.g., logo URLs)
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $pdf = new Dompdf($options);

    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();

    // 5. Stream Output (Using Laravel's response helpers)
    $filename = "Invoice-{$invoice->invoice_number}.pdf";

    // Returns a streamed response, viewing the PDF in the browser (Attachment => false)
    return response()->stream(
      fn() => $pdf->stream($filename, ["Attachment" => false]),
      200,
      [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"{$filename}\""
      ]
    );
  }
}
