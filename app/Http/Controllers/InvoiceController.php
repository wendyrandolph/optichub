<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceLineItem;
use App\Models\InvoicePayment;
use App\Models\Client;
use App\Services\StripeService; // NEW: For Stripe API calls
use App\Services\InvoiceTotalsService;
use App\Models\Tenant;
use App\Jobs\SendTrackedEmail;
use App\Models\OutboundEmail;
use App\Http\Requests\Invoice\StoreInvoiceRequest; // NEW: For validation/CSRF
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\ActivityLog;
use Illuminate\Validation\Rule;


class InvoiceController extends Controller
{


  protected StripeService $stripeService;
  protected InvoiceTotalsService $totalsService;

  // Use Dependency Injection instead of the PDO constructor
  public function __construct(StripeService $stripeService, InvoiceTotalsService $totalsService)
  {
    $this->middleware('auth');
    $this->stripeService = $stripeService;
    $this->totalsService = $totalsService;
  }

  private function resolveTenantId(): int
  {
    $user = auth('admin')->user() ?? auth()->user();
    $tenantId = $user?->tenant_id;

    if (!$tenantId) {
      $routeTenant = request()->route('tenant');
      if ($routeTenant instanceof Tenant) {
        $tenantId = $routeTenant->getKey();
      } elseif (is_numeric($routeTenant)) {
        $tenantId = (int) $routeTenant;
      }
    }

    return (int) ($tenantId ?? 0);
  }

  private function routePrefix(): string
  {
    $routeName = (string) optional(request()->route())->getName();
    return str_starts_with($routeName, 'tenant.trades.invoices')
      ? 'tenant.trades.invoices'
      : 'tenant.invoices';
  }

  /**
   * Display a listing of the invoices.
   * Replaces index()
   *
   * @return \Illuminate\View\View
   */
  public function index(): View
  {
    $tenantId = $this->resolveTenantId();
    abort_if($tenantId === 0, 404);
    $q = request('q', '');
    $statusFilter = request('status', 'all');
    $sort = request('sort', 'recent');

    $base = Invoice::query()
      ->with(['client'])
      ->where('tenant_id', $tenantId);

    // Counts for chips (unfiltered)
    $counts = [
      'all' => (clone $base)->count(),
      'draft' => (clone $base)->where('status', 'draft')->count(),
      'sent' => (clone $base)->where('status', 'sent')->count(),
      'paid' => (clone $base)->where('status', 'paid')->count(),
      'overdue' => (clone $base)
        ->where(function ($q) {
          $q->where('status', 'overdue')
            ->orWhere(function ($qq) {
              $qq->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', now()->toDateString());
            });
        })
        ->count(),
    ];

    // Monetary summaries (unfiltered)
    $openBalance = (clone $base)
      ->where(function ($q) {
        $q->where('status', '!=', 'paid')
          ->orWhereNull('status');
      })
      ->get()
      ->sum(function ($inv) {
        return $inv->balance_due ?? ($inv->total_amount ?? 0);
      });

    $paidTotal = (clone $base)
      ->where('status', 'paid')
      ->get()
      ->sum(fn($inv) => $inv->total_amount ?? 0);

    // Apply search
    if ($q) {
      $base->where(function ($query) use ($q) {
        $query->where('invoice_number', 'like', "%{$q}%")
          ->orWhereHas('client', function ($cq) use ($q) {
            $cq->where('firstName', 'like', "%{$q}%")
              ->orWhere('lastName', 'like', "%{$q}%")
              ->orWhere('email', 'like', "%{$q}%");
          });
      });
    }

    // Apply status filter
    if ($statusFilter && $statusFilter !== 'all') {
      if ($statusFilter === 'overdue') {
        $base->where(function ($q) {
          $q->where('status', 'overdue')
            ->orWhere(function ($qq) {
              $qq->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', now()->toDateString());
            });
        });
      } else {
        $base->where('status', $statusFilter);
      }
    }

    // Sorting
    $invoices = match ($sort) {
      'due' => $base->orderByRaw('due_date IS NULL')->orderBy('due_date')->paginate(20),
      'amount_desc' => $base->orderByDesc('total_amount')->paginate(20),
      'amount_asc' => $base->orderBy('total_amount')->paginate(20),
      default => $base->orderByDesc('updated_at')->paginate(20),
    };

    $stats = [
      'total' => $counts['all'],
      'open_balance' => $openBalance,
      'overdue' => $counts['overdue'],
      'paid_total' => $paidTotal,
    ];

    $routePrefix = $this->routePrefix();
    $view = str_starts_with($routePrefix, 'tenant.trades.') ? 'trades.invoices.index' : 'invoices.index';

    return view($view, [
      'invoices' => $invoices,
      'counts' => $counts,
      'stats' => $stats,
      'routePrefix' => $routePrefix,
      'filters' => [
        'q' => $q,
        'status' => $statusFilter,
        'sort' => $sort,
      ],
    ]);
  }


  /**
   * Display the specified invoice.
   * Replaces show($id)
   *
   * @param \App\Models\Invoice $invoice (Route Model Binding)
   * @return \Illuminate\View\View
   */
  public function show(Tenant $tenant, Invoice $invoice)
  {
    // 1. Authorization: Ensures the user can view this invoice
    $this->authorize('view', $invoice);

    // 2. Data Retrieval (Items and Client are eager loaded)
    $invoice->load(['lineItems', 'client', 'payments']);

    $routePrefix = $this->routePrefix();
    $view = str_starts_with($routePrefix, 'tenant.trades.') ? 'trades.invoices.show' : 'invoices.view';

    return view($view, compact('invoice', 'routePrefix'));
    // Note: The original code passed 'invoices' and 'items'. We only pass 'invoice'
    // and access $invoice->items in the Blade view.
  }

  public function storePayment(Request $request, Tenant $tenant, Invoice $invoice)
  {
    abort_unless($invoice->tenant_id === $tenant->id, 404);
    $tenant->refresh();
    $data = $request->validate([
      'amount' => ['required', 'numeric', 'min:0.01'],
      'method' => ['nullable', 'string', 'max:50'],
      'reference' => ['nullable', 'string', 'max:100'],
      'paid_at' => ['nullable', 'date'],
    ]);

    if (!$tenant->allow_partial_payments) {
      $balance = (float) ($invoice->balance_due ?? $invoice->total_amount ?? $invoice->total ?? 0);
      if ((float) $data['amount'] + 0.0001 < $balance) {
        return back()->withErrors(['amount' => 'Partial payments are disabled for this tenant.']);
      }
    }

    DB::transaction(function () use ($tenant, $invoice, $data) {
      InvoicePayment::create([
        'tenant_id' => $tenant->id,
        'invoice_id' => $invoice->id,
        'amount' => $data['amount'],
        'method' => $data['method'] ?? 'manual',
        'reference' => $data['reference'] ?? null,
        'paid_at' => $data['paid_at'] ?? now(),
      ]);

      $paid = (float) InvoicePayment::where('tenant_id', $tenant->id)
        ->where('invoice_id', $invoice->id)
        ->sum('amount');
      $total = (float) ($invoice->total_amount ?? $invoice->total ?? 0);

      $invoice->balance_due = max($total - $paid, 0);
      if ($invoice->balance_due <= 0.0001) {
        $invoice->status = 'paid';
      } elseif ($paid > 0) {
        $invoice->status = 'partial';
      }
      $invoice->save();
    });

    return back()->with('success_message', 'Payment recorded.');
  }
  // app/Models/Invoice.php


  /**
   * Show the form for creating a new invoice.
   * Replaces create() (GET part)
   *
   * @return \Illuminate\View\View
   */
  public function create(Request $request): View
  {
    //$this->authorize('create', Invoice::class);

    $tenantId = $this->resolveTenantId();
    abort_if($tenantId === 0, 404);
    $clients = Client::where('tenant_id', $tenantId)
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName']);

    $invoice = new Invoice(['status' => 'draft']);
    $lineItems = collect();

    $prefillContactId = $request->query('contact_id');
    $prefillServiceLocationId = $request->query('service_location_id');
    $prefillNotes = null;
    $prefillTradeJobId = $request->query('trade_job_id');

    if ($prefillServiceLocationId) {
      $location = \App\Models\ServiceLocation::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $prefillServiceLocationId)
        ->first();
      if ($location) {
        $prefillNotes = trim(
          'Service location: ' .
          ($location->label ?? 'Location') .
          ' — ' .
          trim(implode(', ', array_filter([
            $location->address_line1,
            $location->address_line2,
            $location->city,
            $location->state,
            $location->postal_code,
          ])))
        );
      }
    }

    return view('invoices.create', compact('clients', 'invoice', 'lineItems', 'prefillContactId', 'prefillNotes', 'prefillTradeJobId'));
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
    $tenantId = $this->resolveTenantId();
    abort_if($tenantId === 0, 404);
    $validated = $request->validate([
      'contact_id' => ['required', 'integer', 'exists:contacts,id'],
      'invoice_number' => ['nullable', 'string'],
      'issue_date' => ['nullable', 'date'],
      'due_date' => ['nullable', 'date'],
      'status' => ['required', 'string', 'max:32'],
      'notes' => ['nullable', 'string'],
      'tax_rate' => ['nullable', 'numeric', 'min:0'],
      'discount_type' => ['nullable', 'in:none,percent,fixed'],
      'discount_value' => ['nullable', 'numeric', 'min:0'],
      'items' => ['array'],
      'items.*.id' => ['nullable', 'integer', 'exists:invoice_line_items,id'],
      'items.*.name' => ['required', 'string', 'max:255'],
      'items.*.description' => ['nullable', 'string'],
      'items.*.quantity' => ['required', 'numeric', 'min:0'],
      'items.*.unit_price' => ['required', 'numeric', 'min:0'],
      'items.*.position' => ['nullable', 'integer', 'min:0'],
      'items.*.is_taxable' => ['nullable', 'boolean'],
      'items.*.service_date' => ['nullable', 'date'],
      'items.*.source_type' => ['nullable', 'string', 'max:50'],
      'items.*.source_id' => ['nullable', 'integer'],
      'trade_job_id' => ['nullable', 'integer', Rule::exists('trade_jobs', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],
    ]);

    $invoice = null;

    DB::beginTransaction();
    try {
      $invoice = new Invoice();
      $invoice->fill([
        'tenant_id' => $tenantId,
        'contact_id' => $validated['contact_id'],
        'trade_job_id' => $validated['trade_job_id'] ?? null,
        'invoice_number' => $validated['invoice_number'] ?? null,
        'issue_date' => $validated['issue_date'] ?? null,
        'due_date' => $validated['due_date'] ?? null,
        'status' => strtolower($validated['status'] ?? 'draft'),
        'notes' => $validated['notes'] ?? null,
        'tax_rate' => $validated['tax_rate'] ?? null,
        'discount_type' => $validated['discount_type'] ?? 'none',
        'discount_value' => $validated['discount_value'] ?? 0,
        'updated_after_sent' => false,
      ]);
      $invoice->save();

      $this->syncLineItems($invoice, $validated['items'] ?? [], $tenantId);
      $invoice->load('lineItems');
      $this->totalsService->compute($invoice);

      $invoice->total_amount = $invoice->total;
      $invoice->balance_due = $invoice->status === 'paid' ? 0 : $invoice->total;
      $invoice->save();

      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error('[invoices.store] ' . $e->getMessage());
      return Redirect::route($this->routePrefix() . '.create', ['tenant' => $tenantId])
        ->withInput()
        ->with('error', 'Invoice creation failed.');
    }

    return Redirect::route($this->routePrefix() . '.show', ['tenant' => $tenantId, 'invoice' => $invoice->id])
      ->with('success', 'Invoice created.');
  }

  /** GET /{tenant}/invoices/{invoice}/edit */
  public function edit(Tenant $tenant, Invoice $invoice): View
  {
    $this->authorize('update', $invoice);
    abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

    $clients = Client::where('tenant_id', $tenant->id)
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName']);

    $invoice->load('lineItems');
    $lineItems = $invoice->lineItems;

    return view('invoices.edit', compact('tenant', 'invoice', 'clients', 'lineItems'));
  }

  /** PUT /{tenant}/invoices/{invoice} */
  public function update(Request $request, Tenant $tenant, Invoice $invoice)
  {
    $this->authorize('update', $invoice);
    abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

    $validated = $request->validate([
      'contact_id' => ['required', 'integer', 'exists:contacts,id'],
      'invoice_number' => ['nullable', 'string'],
      'issue_date' => ['nullable', 'date'],
      'due_date' => ['nullable', 'date'],
      'status' => ['required', 'string', 'max:32'],
      'notes' => ['nullable', 'string'],
      'tax_rate' => ['nullable', 'numeric', 'min:0'],
      'discount_type' => ['nullable', 'in:none,percent,fixed'],
      'discount_value' => ['nullable', 'numeric', 'min:0'],
      'items' => ['array'],
      'items.*.id' => ['nullable', 'integer', 'exists:invoice_line_items,id'],
      'items.*.name' => ['required', 'string', 'max:255'],
      'items.*.description' => ['nullable', 'string'],
      'items.*.quantity' => ['required', 'numeric', 'min:0'],
      'items.*.unit_price' => ['required', 'numeric', 'min:0'],
      'items.*.position' => ['nullable', 'integer', 'min:0'],
      'items.*.is_taxable' => ['nullable', 'boolean'],
      'items.*.service_date' => ['nullable', 'date'],
      'items.*.source_type' => ['nullable', 'string', 'max:50'],
      'items.*.source_id' => ['nullable', 'integer'],
    ]);

    DB::beginTransaction();
    try {
      $invoice->fill([
        'contact_id' => $validated['contact_id'],
        'invoice_number' => $validated['invoice_number'] ?? null,
        'issue_date' => $validated['issue_date'] ?? null,
        'due_date' => $validated['due_date'] ?? null,
        'status' => strtolower($validated['status'] ?? 'draft'),
        'notes' => $validated['notes'] ?? null,
        'tax_rate' => $validated['tax_rate'] ?? null,
        'discount_type' => $validated['discount_type'] ?? 'none',
        'discount_value' => $validated['discount_value'] ?? 0,
      ]);
      $invoice->save();

      $this->syncLineItems($invoice, $validated['items'] ?? [], $tenant->id);
      $invoice->load('lineItems');
      $this->totalsService->compute($invoice);
      $invoice->total_amount = $invoice->total;
      $invoice->balance_due = $invoice->status === 'paid' ? 0 : $invoice->total;
      $invoice->save();

      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error('[invoices.update] ' . $e->getMessage());
      return back()->withInput()->with('error', 'Invoice update failed.');
    }

    ActivityLog::record(
      $tenant->id,
      Auth::id(),
      $invoice,
      'invoice_updated',
      'Invoice #' . ($invoice->invoice_number ?? $invoice->id ?? '')
    );

    return Redirect::route($this->routePrefix() . '.show', ['tenant' => $tenant, 'invoice' => $invoice])
      ->with('success', 'Invoice updated.');
  }

  private function syncLineItems(Invoice $invoice, array $items, int $tenantId): void
  {
    $existingIds = $invoice->lineItems()->pluck('id')->all();
    $seen = [];

    foreach ($items as $index => $itemData) {
      $id = $itemData['id'] ?? null;
      $line = null;
      if ($id) {
        $line = InvoiceLineItem::where('tenant_id', $tenantId)
          ->where('invoice_id', $invoice->id)
          ->where('id', $id)
          ->first();
      }
      if (!$line) {
        $line = new InvoiceLineItem([
          'tenant_id' => $tenantId,
          'invoice_id' => $invoice->id,
        ]);
      }

      $line->position = $itemData['position'] ?? $index;
      $line->name = $itemData['name'] ?? '';
      $line->description = $itemData['description'] ?? null;
      $line->quantity = $itemData['quantity'] ?? 0;
      $line->unit_price = $itemData['unit_price'] ?? 0;
      $line->is_taxable = array_key_exists('is_taxable', $itemData) ? (bool) $itemData['is_taxable'] : ($line->is_taxable ?? true);
      $line->service_date = $itemData['service_date'] ?? null;
      $line->source_type = $itemData['source_type'] ?? $line->source_type;
      $line->source_id = $itemData['source_id'] ?? $line->source_id;
      $line->line_total = round((float) $line->quantity * (float) $line->unit_price, 2);
      $line->save();
      $seen[] = $line->id;
    }

    $toDelete = array_diff($existingIds, $seen);
    if (!empty($toDelete)) {
      $invoice->lineItems()->where('tenant_id', $tenantId)->whereIn('id', $toDelete)->delete();
    }
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

    $tenantId = (int) ($invoice->tenant_id ?: $this->resolveTenantId());
    if ($tenantId > 0) {
      return Redirect::route($this->routePrefix() . '.index', ['tenant' => $tenantId])
        ->with('success', 'Invoice deleted.');
    }

    return Redirect::route($this->routePrefix() . '.index', ['tenant' => request()->route('tenant')])
      ->with('success', 'Invoice deleted.');
  }

  /**
   * Route handler for DELETE /invoices/{invoice}.
   *
   * @param \App\Models\Tenant $tenant
   * @param \App\Models\Invoice $invoice
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Tenant $tenant, Invoice $invoice)
  {
    if (!empty($invoice->tenant_id) && (int) $invoice->tenant_id !== (int) $tenant->id) {
      abort(404);
    }

    return $this->delete($invoice);
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

      $tenantParam = request()->route('tenant') ?? $invoice->tenant_id;
      if (!$client || !$client->email) {
        return Redirect::route($this->routePrefix() . '.show', ['tenant' => $tenantParam, 'invoice' => $invoice])
          ->with('error', 'Client email address is missing or invalid.');
      }

      try {
        $outbound = OutboundEmail::create([
          'tenant_id' => $invoice->tenant_id,
          'user_id' => auth()->id(),
          'to_email' => $client->email,
          'to_name' => trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: ($client->name ?? null),
          'subject' => 'Invoice #' . ($invoice->invoice_number ?? $invoice->id),
          'type' => 'invoice_sent',
          'related_type' => Invoice::class,
          'related_id' => $invoice->id,
          'status' => 'queued',
          'queued_at' => now(),
          'meta' => ['invoice_id' => $invoice->id],
        ]);

        SendTrackedEmail::dispatch($outbound->id);

      // Update status (optional, but good practice)
        $invoice->update([
          'status' => 'sent',
          'sent_at' => now(),
          'updated_after_sent' => false,
        ]);

        ActivityLog::record(
          $invoice->tenant_id ?? $this->resolveTenantId(),
          Auth::id(),
          $invoice,
          'invoice_sent',
          'Invoice #' . ($invoice->invoice_number ?? $invoice->id ?? '') . ' sent to ' . ($client->email ?? '')
        );

      return Redirect::route($this->routePrefix() . '.show', ['tenant' => $tenantParam, 'invoice' => $invoice])
        ->with('success', 'Invoice email queued.');
    } catch (\Throwable $e) {
      Log::error("[invoices.send] Email failed: " . $e->getMessage());

      return Redirect::route($this->routePrefix() . '.show', ['tenant' => $tenantParam, 'invoice' => $invoice])
        ->with('error', 'Email sending failed.');
    }
  }
  public function pdf(Tenant $tenant, Invoice $invoice, Request $request)
  {
    // Optional, if/when you use policies:
    // $this->authorize('view', $invoice);

    // Load relationships used in the template
    $invoice->load(['items', 'client']);
    $pdf = Pdf::loadView('invoices.pdf', [
      'invoice' => $invoice,
      'tenant'  => $tenant,
    ]);

    $filename = 'Invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';
    // ?download=1  --> force download
    if ($request->boolean('download')) {
      return $pdf->download($filename);
    }
    // Force *download*:
    return $pdf->stream($filename);
  }
}
