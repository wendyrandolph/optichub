<?php

// app/Http/Controllers/ReportsController.php
namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Services\Reports\SummaryService;
use App\Services\Reports\ExportService;

class ReportsController extends Controller
{
  public function index(Request $r, Tenant $tenant)
  {
    $this->authorize('view', $tenant);
    $summary = app(\App\Services\Reports\SummaryService::class);
    // hydrate the small stats you show on tiles
    $data = $summary->forTenant($tenant, $r->all());

    // Optional preview table (first 10 rows of most-recent report user viewed)
    return view('reports.index', ['tenant' => $tenant->id] + $data);
  }
  // inside ReportsController
  private function invoiceColumns(): array
  {
    $has = fn(string $c) => \Illuminate\Support\Facades\Schema::hasColumn('invoices', $c);

    $numberCol = collect(['number', 'invoice_number', 'no', 'code'])
      ->first(fn($c) => $has($c)) ?? 'id';

    // return NULL if we don't have one (we'll handle it at select-time)
    $clientCol = collect(['client_name', 'customer_name', 'bill_to_name', 'organization_name', 'contact_name'])
      ->first(fn($c) => $has($c)) ?? null;

    $dueCol = collect(['due_date', 'due_at', 'due_on'])
      ->first(fn($c) => $has($c)) ?? 'created_at';

    if ($has('balance_due')) {
      $balanceExpr = 'balance_due';
    } elseif ($has('amount_due')) {
      $balanceExpr = 'amount_due';
    } elseif ($has('outstanding')) {
      $balanceExpr = 'outstanding';
    } elseif ($has('total_due')) {
      $balanceExpr = 'total_due';
    } elseif ($has('total') && $has('amount_paid')) {
      $balanceExpr = '(COALESCE(total,0) - COALESCE(amount_paid,0))';
    } elseif ($has('subtotal') && $has('tax_total') && $has('amount_paid')) {
      $balanceExpr = '((COALESCE(subtotal,0) + COALESCE(tax_total,0)) - COALESCE(amount_paid,0))';
    } else {
      $balanceExpr = '0';
    }

    return compact('numberCol', 'clientCol', 'dueCol', 'balanceExpr');
  }

  // ------------------------------------------------------------------------- // 
  // ---------------------- Invoice Report ----------------------------------- // 
  // ------------------------------------------------------------------------- // 
  public function invoices(Request $r, \App\Models\Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $r->validate([
      'status' => ['nullable', Rule::in(['sent', 'overdue', 'draft', 'paid', 'void'])],
      'sort'   => ['nullable', Rule::in(['due_date', 'number', 'client', 'balance_due', 'created_at'])],
      'dir'    => ['nullable', Rule::in(['asc', 'desc'])],
      'range'  => ['nullable', Rule::in(['wtd', 'mtd', 'qtd', 'ytd', 'last30'])],
      'q'      => ['nullable', 'string', 'max:100'],
      'min'    => ['nullable', 'numeric'],
      'max'    => ['nullable', 'numeric'],
    ]);

    $status = $r->input('status');      // default to sent+overdue if null
    $sort   = $r->input('sort', 'due_date');
    $dir    = $r->input('dir', 'asc');
    $range  = $r->input('range', 'wtd');
    $qText  = trim((string) $r->input('q', ''));
    $min    = $r->input('min');
    $max    = $r->input('max');

    // ----- date window -----
    [$start, $end] = match ($range) {
      'mtd'    => [now()->startOfMonth(),  now()],
      'qtd'    => [now()->firstOfQuarter(), now()],
      'ytd'    => [now()->startOfYear(),   now()],
      'last30' => [now()->subDays(30),     now()],
      default  => [now()->startOfWeek(),   now()],
    };

    // ----- resolve columns/expression safely -----
    $hasInv = fn($c) => Schema::hasColumn('invoices', $c);

    $numberCol = $hasInv('number') ? 'number' : ($hasInv('invoice_number') ? 'invoice_number' : null);
    $clientCol = $hasInv('client_name') ? 'client_name' : ($hasInv('bill_to_name') ? 'bill_to_name' : null);
    $dueCol    = $hasInv('due_date') ? 'due_date' : ($hasInv('due_on') ? 'due_on' : 'created_at');

    if ($hasInv('balance_due')) $balanceExpr = 'balance_due';
    elseif ($hasInv('amount_due'))  $balanceExpr = 'amount_due';
    elseif ($hasInv('outstanding')) $balanceExpr = 'outstanding';
    elseif ($hasInv('due_amount'))  $balanceExpr = 'due_amount';
    elseif ($hasInv('total_due'))   $balanceExpr = 'total_due';
    elseif ($hasInv('total') && $hasInv('amount_paid')) {
      $balanceExpr = '(total - amount_paid)';
    } elseif ($hasInv('subtotal') && $hasInv('tax_total') && $hasInv('amount_paid')) {
      $balanceExpr = '((subtotal + tax_total) - amount_paid)';
    } else {
      $balanceExpr = '0';
    }

    // ----- base query (shared) -----
    $base = DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->whereBetween($dueCol, [$start, $end])
      ->when(
        $status,
        fn($q) => $q->where('status', $status),
        fn($q) => $q->whereIn('status', ['sent', 'overdue']) // default for this view
      )
      ->when($qText !== '', function ($q) use ($qText, $numberCol, $clientCol) {
        $q->where(function ($w) use ($qText, $numberCol, $clientCol) {
          if ($numberCol) $w->orWhere($numberCol, 'like', "%{$qText}%");
          if ($clientCol) $w->orWhere($clientCol, 'like', "%{$qText}%");
          if (Schema::hasColumn('invoices', 'subject')) {
            $w->orWhere('subject', 'like', "%{$qText}%");
          }
        });
      })
      ->when(isset($min), fn($q) => $q->whereRaw("{$balanceExpr} >= ?", [(float) $min]))
      ->when(isset($max), fn($q) => $q->whereRaw("{$balanceExpr} <= ?", [(float) $max]));

    // ----- rows -----
    $sortMap = [
      'due_date'    => $dueCol,
      'number'      => $numberCol ?: null,
      'client'      => $clientCol ?: null,
      'balance_due' => DB::raw($balanceExpr),
      'created_at'  => 'created_at',
    ];
    $sortCol = $sortMap[$sort] ?? $dueCol;
    if ($sort === 'client' && !$clientCol) $sortCol = $dueCol;
    if ($sort === 'number' && !$numberCol) $sortCol = $dueCol;

    $rows = (clone $base)
      ->select([
        'id',
        $numberCol ? DB::raw("`{$numberCol}` as number") : DB::raw("CONCAT('INV-', id) as number"),
        $clientCol ? DB::raw("`{$clientCol}` as client_name") : DB::raw("'—' as client_name"),
        'status',
        $dueCol === 'due_date' ? 'due_date' : DB::raw("`{$dueCol}` as due_date"),
        DB::raw("{$balanceExpr} as balance_due"),
        'created_at',
      ])
      ->orderBy($sortCol, $dir)
      ->paginate(25)
      ->withQueryString();

    // ----- summary -----
    $summaryRow = (clone $base)
      ->selectRaw("COUNT(*) as cnt, COALESCE(SUM({$balanceExpr}),0) as total")
      ->first();

    // ----- chart (amount due per day in window) -----
    $byDay = (clone $base)
      ->selectRaw("DATE({$dueCol}) as d, COALESCE(SUM({$balanceExpr}),0) as total")
      ->groupBy('d')
      ->orderBy('d')
      ->get()
      ->keyBy('d');

    $labels = [];
    $amounts = [];
    $cursor = $start->copy()->startOfDay();
    while ($cursor->lte($end)) {
      $k = $cursor->format('Y-m-d');
      $labels[]  = $cursor->format('M d');
      $amounts[] = (float) ($byDay[$k]->total ?? 0);
      $cursor->addDay();
    }

    $invoicesConfig = [
      'type' => 'bar',
      '_yCurrency' => true,
      'data' => [
        'labels' => $labels,
        'datasets' => [[
          'label'  => 'Amount Due',
          'data'   => $amounts,
          '_brand' => 'primary',
        ]],
      ],
      'options' => [
        'scales' => ['y' => ['beginAtZero' => true]],
      ],
    ];

    return view('reports.invoices', [
      'tenant'         => $tenant->id,
      'rows'           => $rows,
      'summary'        => [
        'cnt'   => (int) ($summaryRow->cnt ?? 0),
        'total' => (float) ($summaryRow->total ?? 0),
      ],
      'invoicesConfig' => $invoicesConfig,
      'filters'        => [
        'status' => $status,
        'sort'   => $sort,
        'dir'    => $dir,
        'range'  => $range,
        'q'      => $qText,
        'min'    => $min,
        'max'    => $max,
      ],
    ]);
  }
  // ------------------------------------------------------------------------- // 
  // ---------------------- Invoice Collected Report ------------------------- // 
  // ------------------------------------------------------------------------- // 
  public function collected(Request $request, Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $request->validate([
      'range'  => ['nullable', Rule::in(['wtd', 'mtd', 'qtd', 'ytd', 'last30'])],
      'method' => ['nullable', 'string', 'max:50'],   // e.g. stripe|authorizenet|manual
      'status' => ['nullable', 'string', 'max:50'],   // e.g. succeeded|failed|refunded
      'sort'   => ['nullable', Rule::in(['paid_at', 'created_at', 'amount', 'method', 'status'])],
      'dir'    => ['nullable', Rule::in(['asc', 'desc'])],
    ]);

    // ----- range -----
    $range = strtolower($request->input('range', 'last30'));
    [$start, $end] = match ($range) {
      'wtd' => [now()->startOfWeek(), now()],
      'mtd' => [now()->startOfMonth(), now()],
      'qtd' => [now()->firstOfQuarter(), now()],
      'ytd' => [now()->startOfYear(), now()],
      default => [now()->subDays(30), now()],
    };

    // ----- column detection -----
    $hasPay = fn($c) => Schema::hasColumn('payments', $c);
    $paidAt   = $hasPay('paid_at') ? 'paid_at' : 'created_at';
    $amtExpr  = $hasPay('amount') ? 'amount' : ($hasPay('amount_cents') ? '(amount_cents/100)' : '0');
    $methodCol = $hasPay('provider') ? 'provider' : ($hasPay('method') ? 'method' : null);
    $statusCol = $hasPay('status') ? 'status' : null;

    // ----- chart series (dense labels) -----
    $byDay = DB::table('payments')
      ->selectRaw("DATE($paidAt) as d, COALESCE(SUM($amtExpr),0) as total")
      ->where('tenant_id', $tenant->id)
      ->whereBetween($paidAt, [$start, $end])
      ->when($methodCol && $request->filled('method'), fn($q) => $q->where($methodCol, $request->method))
      ->when($statusCol && $request->filled('status'), fn($q) => $q->where($statusCol, $request->status))
      ->groupBy('d')->orderBy('d')
      ->get()->keyBy('d');

    $labels = [];
    $values = [];
    $cursor = $start->copy()->startOfDay();
    while ($cursor->lte($end)) {
      $key = $cursor->format('Y-m-d');
      $labels[] = $cursor->format('M d');
      $values[] = (float) ($byDay[$key]->total ?? 0);
      $cursor->addDay();
    }

    $collectedConfig = [
      'type' => 'bar',
      '_yCurrency' => true,
      'data' => [
        'labels' => $labels,
        'datasets' => [[
          'label'  => 'Collected',
          'data'   => $values,
          '_brand' => 'primary',
        ]],
      ],
      'options' => ['scales' => ['y' => ['beginAtZero' => true]]],
    ];

    // ----- table rows -----
    $sort = $request->input('sort', 'paid_at');
    $dir  = $request->input('dir', 'desc');

    $rows = DB::table('payments')
      ->leftJoin('invoices', function ($j) {
        $j->on('invoices.id', '=', 'payments.invoice_id')
          ->on('invoices.tenant_id', '=', 'payments.tenant_id');
      })
      ->where('payments.tenant_id', $tenant->id)
      ->whereBetween("payments.$paidAt", [$start, $end])
      ->when($methodCol && $request->filled('method'), fn($q) => $q->where("payments.$methodCol", $request->method))
      ->when($statusCol && $request->filled('status'), fn($q) => $q->where("payments.$statusCol", $request->status))
      ->select([
        'payments.id',
        DB::raw("payments.$paidAt as paid_at"),
        DB::raw("$amtExpr as amount"),
        DB::raw($methodCol ? "COALESCE(payments.$methodCol,'') as method" : "'' as method"),
        DB::raw($statusCol ? "COALESCE(payments.$statusCol,'') as status" : "'' as status"),
        DB::raw("COALESCE(invoices.client_name,'') as client_name"),
      ])
      // sorting (amount needs raw; others are plain)
      ->when($sort === 'amount', fn($q) => $q->orderBy(DB::raw($amtExpr), $dir))
      ->when($sort !== 'amount' && in_array($sort, ['paid_at', 'created_at']), fn($q) => $q->orderBy("payments.$sort", $dir))
      ->when($sort === 'method' && $methodCol, fn($q) => $q->orderBy("payments.$methodCol", $dir))
      ->when($sort === 'status' && $statusCol, fn($q) => $q->orderBy("payments.$statusCol", $dir))
      ->paginate(25)->withQueryString();

    return view('reports.collected', [
      'tenant'          => $tenant->id,
      'collectedConfig' => $collectedConfig,
      'rows'            => $rows,
      'filters'         => [
        'range'  => $range,
        'method' => $request->input('method'),
        'status' => $request->input('status'),
        'sort'   => $sort,
        'dir'    => $dir,
      ],
    ]);
  }


  // ------------------------------------------------------------------------- // 
  // ---------------------- Forecast Report ----------------------------------- // 
  // ------------------------------------------------------------------------- // 

  public function forecast(Request $r, \App\Models\Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $r->validate([
      'window' => ['nullable', Rule::in(['next7', 'next14', 'next30', 'custom'])],
      'from'   => ['nullable', 'date'],
      'to'     => ['nullable', 'date', 'after_or_equal:from'],
      'status' => ['nullable', Rule::in(['draft', 'sent', 'overdue', 'paid', 'void'])],
      'sort'   => ['nullable', Rule::in(['due_date', 'number', 'client_name', 'balance_due', 'created_at'])],
      'dir'    => ['nullable', Rule::in(['asc', 'desc'])],
      'q'      => ['nullable', 'string', 'max:100'],
      'min'    => ['nullable', 'numeric'],
      'max'    => ['nullable', 'numeric'],
    ]);

    // -------- window ----------
    $window = $r->input('window', 'next14');
    [$start, $end] = match ($window) {
      'next7'  => [now()->startOfDay(), now()->addDays(7)->endOfDay()],
      'next14' => [now()->startOfDay(), now()->addDays(14)->endOfDay()],
      'next30' => [now()->startOfDay(), now()->addDays(30)->endOfDay()],
      'custom' => [
        $r->filled('from') ? Carbon::parse($r->input('from'))->startOfDay() : now()->startOfDay(),
        $r->filled('to')   ? Carbon::parse($r->input('to'))->endOfDay()     : now()->addDays(14)->endOfDay(),
      ],
      default  => [now()->startOfDay(), now()->addDays(14)->endOfDay()],
    };
    if ($start->gt($end)) [$start, $end] = [$end, $start];

    $status = $r->input('status');
    $sort   = $r->input('sort', 'due_date');
    $dir    = $r->input('dir', 'asc');
    $qText  = trim((string) $r->input('q', ''));
    $min    = $r->input('min');
    $max    = $r->input('max');

    // -------- resolve invoice columns/expressions safely ----------
    $hasInv = fn($c) => Schema::hasColumn('invoices', $c);

    // number column (nullable)
    $numberCol = $hasInv('number') ? 'number' : ($hasInv('invoice_number') ? 'invoice_number' : null);

    // client name column (nullable)
    $clientCol = $hasInv('client_name') ? 'client_name'
      : ($hasInv('bill_to_name') ? 'bill_to_name' : null);

    // due date column (required-ish; fallback to created_at)
    $dueCol = $hasInv('due_date') ? 'due_date'
      : ($hasInv('due_on') ? 'due_on' : 'created_at');

    // balance due expression (try several, else derive or zero)
    if ($hasInv('balance_due')) {
      $balanceExpr = 'balance_due';
    } elseif ($hasInv('amount_due')) {
      $balanceExpr = 'amount_due';
    } elseif ($hasInv('outstanding')) {
      $balanceExpr = 'outstanding';
    } elseif ($hasInv('due_amount')) {
      $balanceExpr = 'due_amount';
    } elseif ($hasInv('total_due')) {
      $balanceExpr = 'total_due';
    } elseif ($hasInv('total') && $hasInv('amount_paid')) {
      $balanceExpr = '(total - amount_paid)';
    } elseif ($hasInv('subtotal') && $hasInv('tax_total') && $hasInv('amount_paid')) {
      $balanceExpr = '((subtotal + tax_total) - amount_paid)';
    } else {
      $balanceExpr = '0';
    }

    // -------- base query ----------
    $base = DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->whereBetween($dueCol, [$start, $end])
      ->when(
        $status,
        fn($qq) => $qq->where('status', $status),
        fn($qq) => $qq->whereIn('status', ['draft', 'sent']) // default for forecast
      )
      ->when($qText !== '', function ($qq) use ($qText, $numberCol, $clientCol) {
        $qq->where(function ($w) use ($qText, $numberCol, $clientCol) {
          if ($numberCol)   $w->orWhere($numberCol, 'like', "%{$qText}%");
          if ($clientCol)   $w->orWhere($clientCol, 'like', "%{$qText}%");
          // also allow subject-ish search if present
          if (Schema::hasColumn('invoices', 'subject')) {
            $w->orWhere('subject', 'like', "%{$qText}%");
          }
        });
      })
      ->when(isset($min), fn($qq) => $qq->whereRaw("{$balanceExpr} >= ?", [(float) $min]))
      ->when(isset($max), fn($qq) => $qq->whereRaw("{$balanceExpr} <= ?", [(float) $max]));

    // -------- sorting ----------
    $sortMap = [
      'due_date'    => $dueCol,
      'number'      => $numberCol,
      'client_name' => $clientCol,
      'balance_due' => DB::raw($balanceExpr),
      'created_at'  => 'created_at',
    ];
    $sortCol = $sortMap[$sort] ?? $dueCol;
    if ($sort === 'client_name' && !$clientCol) $sortCol = $dueCol;
    if ($sort === 'number' && !$numberCol)      $sortCol = $dueCol;

    // -------- table rows ----------
    $rows = (clone $base)
      ->select([
        'id',
        $numberCol ? DB::raw("`{$numberCol}` as number") : DB::raw("CONCAT('INV-', id) as number"),
        $clientCol ? DB::raw("`{$clientCol}` as client_name") : DB::raw("'—' as client_name"),
        'status',
        $dueCol === 'due_date' ? 'due_date' : DB::raw("`{$dueCol}` as due_date"),
        DB::raw("{$balanceExpr} as balance_due"),
        'created_at',
      ])
      ->when($sortCol, fn($qq) => $qq->orderBy($sortCol, $dir))
      ->paginate(25)
      ->withQueryString();

    // -------- summary & chart series ----------
    $summaryRow = (clone $base)
      ->selectRaw("COUNT(*) as cnt, COALESCE(SUM({$balanceExpr}),0) as total")
      ->first();

    // Build dense day-by-day amounts for the chart
    $byDay = (clone $base)
      ->selectRaw("DATE({$dueCol}) as d, COALESCE(SUM({$balanceExpr}),0) as total")
      ->groupBy('d')->orderBy('d')
      ->get()->keyBy('d');

    $labels = [];
    $amounts = [];
    $cursor = $start->copy()->startOfDay();
    while ($cursor->lte($end)) {
      $key = $cursor->format('Y-m-d');
      $labels[]  = $cursor->format('M d');
      $amounts[] = (float) ($byDay[$key]->total ?? 0);
      $cursor->addDay();
    }

    $forecastConfig = [
      'type' => 'bar',
      '_yCurrency' => true,
      'data' => [
        'labels' => $labels,
        'datasets' => [[
          'label'  => "Due ({$start->format('M j')}–{$end->format('M j')})",
          'data'   => $amounts,
          '_brand' => 'primary',
        ]],
      ],
      'options' => [
        'scales' => ['y' => ['beginAtZero' => true]],
      ],
    ];

    return view('reports.forecast', [
      'tenant'         => $tenant->id,
      'rows'           => $rows,
      'summary'        => [
        'cnt'   => (int) ($summaryRow->cnt ?? 0),
        'total' => (float) ($summaryRow->total ?? 0),
      ],
      'forecastConfig' => $forecastConfig,
      'filters'        => [
        'window' => $window,
        'from'   => $start->toDateString(),
        'to'     => $end->toDateString(),
        'status' => $status,
        'sort'   => $sort,
        'dir'    => $dir,
        'qText'  => $qText,
        'min'    => $min,
        'max'    => $max,
      ],
    ]);
  }


  // ------------------------------------------------------------------------- // 
  // ---------------------- Task Due Report ----------------------------------- // 
  // ------------------------------------------------------------------------- // 

  public function tasksDue(Request $request, \App\Models\Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $request->validate([
      'window'   => ['nullable', Rule::in(['overdue', 'today', 'next7', 'next14', 'next30', 'all', 'custom'])],
      'from'     => ['nullable', 'date'],
      'to'       => ['nullable', 'date', 'after_or_equal:from'],
      'status'   => ['nullable', 'string', 'max:100'], // allow comma list
      'assignee' => ['nullable', 'integer'],
      'sort'     => ['nullable', Rule::in(['due_date', 'priority', 'project', 'title', 'assignee', 'status', 'created_at'])],
      'dir'      => ['nullable', Rule::in(['asc', 'desc'])],
      'q'        => ['nullable', 'string', 'max:100'],
      'days'     => ['nullable', 'integer', 'min:1', 'max:90'],
    ]);

    // ------- resolve date window -------
    $window = $request->input('window', 'next14');
    $now    = now();
    [$start, $end] = match ($window) {
      'today'   => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
      'overdue' => [Carbon::create(2000, 1, 1)->startOfDay(), $now->copy()->subDay()->endOfDay()],
      'next7'   => [$now->copy()->startOfDay(), $now->copy()->addDays(7)->endOfDay()],
      'next14'  => [$now->copy()->startOfDay(), $now->copy()->addDays(14)->endOfDay()],
      'next30'  => [$now->copy()->startOfDay(), $now->copy()->addDays(30)->endOfDay()],
      'all'     => [Carbon::create(2000, 1, 1)->startOfDay(), Carbon::create(2100, 1, 1)->endOfDay()],
      'custom'  => [
        Carbon::parse($request->input('from', $now->copy()->startOfDay()))->startOfDay(),
        Carbon::parse($request->input('to',   $now->copy()->endOfDay()))->endOfDay(),
      ],
      default   => [$now->copy()->startOfDay(), $now->copy()->addDays((int)$request->input('days', 14))->endOfDay()],
    };

    // guard: if someone sets from > to, flip
    if ($start->gt($end)) [$start, $end] = [$end, $start];

    // ------- columns & filters -------
    $dueCol  = Schema::hasColumn('tasks', 'due_date') ? 'due_date' : 'created_at';
    $statusList = array_filter(array_map('trim', explode(',', (string)$request->input('status', 'open,in_progress'))));
    $sort = $request->input('sort', 'due_date');
    $dir  = $request->input('dir', 'asc');
    $q    = $request->input('q');

    // ------- series for chart (dense labels across range) -------
    // We only chart future windows & today; for "overdue" we chart up to yesterday.
    $chartStart = $window === 'overdue' ? $start : $start->copy();
    $chartEnd   = $window === 'overdue' ? $end->copy() : $end->copy();

    $byDay = DB::table('tasks')
      ->selectRaw("DATE($dueCol) as d, COUNT(*) as c")
      ->where('tenant_id', $tenant->id)
      ->when(!empty($statusList), fn($q) => $q->whereIn('status', $statusList))
      ->when($q, fn($query) => $query->where('title', 'like', "%{$q}%"))
      ->whereBetween($dueCol, [$chartStart, $chartEnd])
      ->groupBy('d')
      ->orderBy('d')
      ->get()
      ->keyBy('d');

    $labels = [];
    $counts = [];
    $cursor = $chartStart->copy()->startOfDay();
    while ($cursor->lte($chartEnd)) {
      $key = $cursor->format('Y-m-d');
      $labels[] = $cursor->format('M d');
      $counts[] = (int)($byDay[$key]->c ?? 0);
      $cursor->addDay();
    }

    $dueConfig = [
      'type' => 'bar',
      'data' => [
        'labels' => $labels,
        'datasets' => [[
          'label'  => 'Tasks Due',
          'data'   => $counts,
          '_brand' => 'secondary',
        ]],
      ],
      'options' => [
        'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
      ],
    ];

    // ------- table rows -------
    $rows = DB::table('tasks')
      ->leftJoin('projects', function ($j) {
        $j->on('projects.id', '=', 'tasks.project_id')
          ->on('projects.tenant_id', '=', 'tasks.tenant_id');
      })
      ->where('tasks.tenant_id', $tenant->id)
      ->when(!empty($statusList), fn($q) => $q->whereIn('tasks.status', $statusList))
      ->when($request->filled('assignee'), fn($q) => $q->where('tasks.assignee_id', (int)$request->assignee))
      ->when($q, fn($query) => $query->where('tasks.title', 'like', "%{$q}%"))
      ->whereBetween("tasks.$dueCol", [$start, $end])
      ->select([
        'tasks.id',
        'tasks.title',
        'tasks.status',
        DB::raw("tasks.$dueCol as due_date"),
        DB::raw("COALESCE(projects.project_name, '') as project_name"),
      ])
      ->when($sort === 'project', fn($q) => $q->orderBy('projects.project_name', $dir))
      ->when($sort !== 'project', fn($q) => $q->orderBy("tasks.$sort", $dir))
      ->paginate(25)
      ->withQueryString();

    return view('reports.tasks-due', [
      'tenant'     => $tenant->id,
      'dueConfig'  => $dueConfig,
      'rows'       => $rows,
      'filters'    => [
        'window'   => $window,
        'from'     => $request->input('from'),
        'to'       => $request->input('to'),
        'status'   => implode(',', $statusList),
        'assignee' => $request->input('assignee'),
        'sort'     => $sort,
        'dir'      => $dir,
        'q'        => $q,
      ],
    ]);
  }


  // ------------------------------------------------------------------------- // 
  // ---------------------- Stale Projects Report ---------------------------- // 
  // ------------------------------------------------------------------------- // 

  public function projectsStale(Request $r, \App\Models\Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $r->validate([
      'days'     => ['nullable', 'integer', 'min:1'],
      'min_days' => ['nullable', 'integer', 'min:0'],
      'max_days' => ['nullable', 'integer', 'min:0'],
      'status'   => ['nullable', 'string', 'max:40'],
      'owner'    => ['nullable', 'integer'],
      'q'        => ['nullable', 'string', 'max:100'],
      'sort'     => ['nullable', Rule::in(['stale_days', 'name', 'client', 'owner', 'status', 'updated_at', 'created_at'])],
      'dir'      => ['nullable', Rule::in(['asc', 'desc'])],
    ]);

    $baseline = (int) $r->input('days', 14);
    $minDays  = $r->input('min_days');
    $maxDays  = $r->input('max_days');
    $status   = $r->input('status');
    $ownerId  = $r->input('owner');
    $qText    = trim((string) $r->input('q', ''));
    $sort     = $r->input('sort', 'stale_days');
    $dir      = $r->input('dir', 'desc');

    // Schema detection
    $hasProj = fn($c) => Schema::hasColumn('projects', $c);

    $nameCol    = $hasProj('name') ? 'name' : ($hasProj('project_name') ? 'project_name' : 'id');
    $clientCol  = $hasProj('client_name') ? 'client_name' : null;
    $ownerIdCol = $hasProj('owner_id') ? 'owner_id' : null;
    $lastCol    = $hasProj('last_activity_at') ? 'last_activity_at' : ($hasProj('updated_at') ? 'updated_at' : 'created_at');

    $today = now()->toDateString();

    // Base query
    $base = DB::table('projects')->where('projects.tenant_id', $tenant->id);

    if ($ownerIdCol && Schema::hasTable('users')) {
      $base->leftJoin('users', "projects.$ownerIdCol", '=', 'users.id');
    }

    if ($status) {
      $base->where('projects.status', $status);
    }
    if ($ownerId && $ownerIdCol) {
      $base->where("projects.$ownerIdCol", (int) $ownerId);
    }
    if ($qText !== '') {
      $base->where(function ($w) use ($qText, $nameCol, $clientCol) {
        $w->where("projects.$nameCol", 'like', "%{$qText}%");
        if ($clientCol) {
          $w->orWhere("projects.$clientCol", 'like', "%{$qText}%");
        }
      });
    }

    // Stale days expression (MySQL): DATEDIFF(today, lastCol)
    $staleExpr = "GREATEST(DATEDIFF(?, `projects`.`$lastCol`), 0)";
    $bind = [$today];

    // Apply stale windows
    $q = (clone $base);
    if ($minDays !== null) {
      $q->whereRaw("$staleExpr >= ?", array_merge($bind, [(int)$minDays]));
    } else {
      $q->whereRaw("$staleExpr >= ?", array_merge($bind, [$baseline]));
    }
    if ($maxDays !== null) {
      $q->whereRaw("$staleExpr <= ?", array_merge($bind, [(int)$maxDays]));
    }

    // Select fields
    $select = [
      'projects.id',
      $nameCol === 'name' ? DB::raw('projects.name') : DB::raw("projects.`$nameCol` as name"),
      $clientCol ? DB::raw("projects.`$clientCol` as client_name") : DB::raw("'—' as client_name"),
      'projects.status',
      DB::raw("projects.`$lastCol` as last_activity_at"),
      DB::raw("$staleExpr as stale_days"),
      'projects.created_at',
    ];
    if ($ownerIdCol) {
      $select[] = Schema::hasTable('users')
        ? DB::raw("COALESCE(users.name, '—') as owner_name")
        : DB::raw("'—' as owner_name");
    } else {
      $select[] = DB::raw("'—' as owner_name");
    }

    // Sorting
    $sortMap = [
      'stale_days' => DB::raw('stale_days'),
      'name'       => DB::raw('name'),
      'client'     => DB::raw('client_name'),
      'owner'      => DB::raw('owner_name'),
      'status'     => DB::raw('projects.status'),
      'updated_at' => DB::raw('last_activity_at'),
      'created_at' => DB::raw('projects.created_at'),
    ];
    $sortCol = $sortMap[$sort] ?? DB::raw('stale_days');

    // Rows (paginated)
    $rows = (clone $q)
      ->select($select)
      ->orderBy($sortCol, $dir)
      ->paginate(25)
      ->withQueryString();
    // one placeholder per $staleExpr; AVG + MAX => 2 total placeholders
    $totalsRow = (clone $q)
      ->selectRaw(
        "COUNT(*) as cnt, AVG($staleExpr) as avg_stale, MAX($staleExpr) as max_stale",
        array_merge($bind, $bind) // ⬅️ two dates, not three
      )
      ->first();

    $totals = [
      'count' => (int) ($totalsRow->cnt ?? 0),
      'avg'   => round((float) ($totalsRow->avg_stale ?? 0), 1),
      'max'   => (int) ($totalsRow->max_stale ?? 0),
    ];

    // ---- Buckets for chart (15–30, 31–60, 61–90, 90+) ----
    $buckets = ['15–30' => 0, '31–60' => 0, '61–90' => 0, '90+' => 0];

    $forChart = (clone $q)
      ->selectRaw("$staleExpr as stale_days", $bind)
      ->get();

    foreach ($forChart as $row) {
      $d = (int) $row->stale_days;
      if ($d >= 15 && $d <= 30)      $buckets['15–30']++;
      elseif ($d >= 31 && $d <= 60)  $buckets['31–60']++;
      elseif ($d >= 61 && $d <= 90)  $buckets['61–90']++;
      elseif ($d >= 91)              $buckets['90+']++;
    }

    $staleConfig = [
      'type' => 'bar',
      'data' => [
        'labels' => array_keys($buckets),
        'datasets' => [[
          'label'  => 'Stale Projects',
          'data'   => array_values($buckets),
          '_brand' => 'danger',
        ]],
      ],
      'options' => [
        'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
      ],
    ];

    return view('reports.projects-stale', [
      'tenant'      => $tenant->id,
      'rows'        => $rows,
      'totals'      => $totals,
      'staleConfig' => $staleConfig,
      'filters'     => [
        'days'     => $baseline,
        'min_days' => $minDays,
        'max_days' => $maxDays,
        'status'   => $status,
        'owner'    => $ownerId,
        'qText'    => $qText,
        'sort'     => $sort,
        'dir'      => $dir,
      ],
    ]);
  }




  // ------------------------------------------------------------------------- // 
  // ---------------------- Tasks On Time Report ----------------------------- // 
  // ------------------------------------------------------------------------- // 

  public function tasksOnTime(Request $r, Tenant $tenant)
  {
    $range = strtolower($r->input('range', 'last30'));
    [$start, $end] = match ($range) {
      'wtd' => [now()->startOfWeek(), now()],
      'mtd' => [now()->startOfMonth(), now()],
      'qtd' => [now()->firstOfQuarter(), now()],
      'ytd' => [now()->startOfYear(), now()],
      default => [now()->subDays(30), now()],
    };

    $has = fn($c) => Schema::hasColumn('tasks', $c);
    $dueCol = $has('due_date') ? 'due_date' : 'created_at';
    $doneCol = $has('completed_at') ? 'completed_at' : ($has('updated_at') ? 'updated_at' : 'created_at');

    $total = (int) DB::table('tasks')
      ->where('tenant_id', $tenant->id)
      ->where('status', 'completed')
      ->whereBetween($doneCol, [$start, $end])
      ->count();

    $on = (int) DB::table('tasks')
      ->where('tenant_id', $tenant->id)
      ->where('status', 'completed')
      ->whereBetween($doneCol, [$start, $end])
      ->whereColumn($doneCol, '<=', $dueCol) // safe even if due_date has nulls; null compares false
      ->count();

    $late = max(0, $total - $on);

    $donutConfig = [
      'type' => 'doughnut',
      'data' => [
        'labels' => ['On-Time', 'Late'],
        'datasets' => [[
          'data' => [$on, $late],
          'backgroundColor' => ['rgb(98,172,57)', 'rgba(220,53,69,.8)'],
          'borderColor' => ['rgb(98,172,57)', 'rgb(220,53,69)'],
          'borderWidth' => 1,
        ]],
      ],
      'options' => ['cutout' => '65%'],
    ];
    // detect columns
    $has     = fn($c) => Schema::hasColumn('tasks', $c);
    $dueCol  = $has('due_date') ? 'due_date' : 'created_at';
    $doneCol = $has('completed_at') ? 'completed_at' : ($has('updated_at') ? 'updated_at' : 'created_at');

    // optional: join projects for names without tenant_id ambiguity
    $rows = DB::table('tasks')
      ->leftJoin('projects', function ($j) {
        $j->on('projects.id', '=', 'tasks.project_id')
          ->on('projects.tenant_id', '=', 'tasks.tenant_id');
      })
      ->where('tasks.tenant_id', $tenant->id)
      ->where('tasks.status', 'completed')
      ->whereBetween("tasks.$doneCol", [$start, $end])
      ->select([
        'tasks.id',
        DB::raw("tasks.$doneCol as completed_at"),
        DB::raw("tasks.$dueCol as due_date"),
        'tasks.title',
        'tasks.status',
        DB::raw("COALESCE(projects.project_name, projects.name, '') as project_name"),
        // on_time: only true when due_date is not null and completed_at <= due_date
        DB::raw("CASE WHEN tasks.$dueCol IS NOT NULL AND tasks.$doneCol <= tasks.$dueCol THEN 1 ELSE 0 END as on_time"),
      ])
      ->orderBy("tasks.$doneCol", 'desc')
      ->paginate(25)
      ->withQueryString();

    return view('reports.tasks-on-time', [
      'tenant'      => $tenant->id,
      'donutConfig' => $donutConfig,
      'summary'     => ['on_time' => $on, 'total' => $total, 'pct' => $total ? (int)round($on / $total * 100) : 0],
      'filters'     => ['range' => $range],
      'rows'        => $rows,
    ]);
  }
  // ------------------------------------------------------------------------- // 
  // ---------------------- New Leads Report --------------------------------- // 
  // ------------------------------------------------------------------------- // 
  public function leadsNew(Request $r, Tenant $tenant)
  {
    $range = strtolower($r->input('range', 'last30'));
    [$start, $end] = match ($range) {
      'wtd' => [now()->startOfWeek(), now()],
      'mtd' => [now()->startOfMonth(), now()],
      'qtd' => [now()->firstOfQuarter(), now()],
      'ytd' => [now()->startOfYear(), now()],
      default => [now()->subDays(30), now()],
    };

    $createdCol = Schema::hasColumn('leads', 'created_at') ? 'created_at' : 'id'; // last resort
    $byDay = DB::table('leads')
      ->selectRaw("DATE($createdCol) as d, COUNT(*) as c")
      ->where('tenant_id', $tenant->id)
      ->whereBetween($createdCol, [$start, $end])
      ->groupBy('d')
      ->orderBy('d')
      ->get()
      ->keyBy(fn($r) => Carbon::parse($r->d)->format('Y-m-d'));

    $labels = [];
    $data   = [];
    $cursor = $start->copy()->startOfDay();
    while ($cursor->lte($end)) {
      $labels[] = $cursor->format('M d');
      $key = $cursor->format('Y-m-d');
      $data[] = (int) ($byDay[$key]->c ?? 0);
      $cursor->addDay();
    }

    $leadConfig = [
      'type' => 'line',
      'data' => [
        'labels' => $labels,
        'datasets' => [[
          'label' => 'New Leads',
          'data' => $data,
          '_brand' => 'secondary',
          'tension' => 0.35,
          'fill' => true,
        ]],
      ],
      'options' => ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
    ];
    $createdCol = Schema::hasColumn('leads', 'created_at') ? 'created_at' : 'id'; // last resort

    $rows = DB::table('leads')
      ->where('tenant_id', $tenant->id)
      ->whereBetween('created_at', [$start, $end])
      ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'status', 'source', 'created_at'])
      ->orderBy('created_at', 'desc')
      ->paginate(25)
      ->withQueryString();


    return view('reports.leads-new', [
      'tenant'     => $tenant->id,
      'leadConfig' => $leadConfig,
      'summary'    => ['count' => array_sum($data)],
      'filters'    => ['range' => $range],
      'rows'      =>  $rows,
    ]);
  }

  // ------------------------------------------------------------------------- // 
  // ---------------------- Email Activity Report ---------------------------- // 
  // ------------------------------------------------------------------------- // 
  public function emailsActivity(Request $r, Tenant $tenant)
  {
    $range = strtolower($r->input('range', 'last30'));
    [$start, $end] = match ($range) {
      'wtd'    => [now()->startOfWeek(), now()],
      'mtd'    => [now()->startOfMonth(), now()],
      'qtd'    => [now()->firstOfQuarter(), now()],
      'ytd'    => [now()->startOfYear(), now()],
      default  => [now()->subDays(30), now()],
    };

    $has = fn($c) => Schema::hasColumn('emails', $c);
    $tsCols = array_filter([
      $has('sent_at') ? 'sent_at' : null,
      $has('received_at') ? 'received_at' : null,
      $has('date_sent') ? 'date_sent' : null,
      $has('created_at') ? 'created_at' : null,
    ]);
    $tsExpr = $tsCols ? 'COALESCE(' . implode(',', $tsCols) . ')' : 'created_at';

    $series = DB::table('emails')
      ->where('tenant_id', $tenant->id)
      ->whereBetween(DB::raw($tsExpr), [$start, $end])
      ->selectRaw("DATE($tsExpr) as d,
                    SUM(CASE WHEN direction='outbound' THEN 1 ELSE 0 END) as out_cnt,
                    SUM(CASE WHEN direction='inbound'  THEN 1 ELSE 0 END) as in_cnt")
      ->groupBy('d')
      ->orderBy('d')
      ->get();

    // dense labels across the window
    $labels = [];
    $byDay = $series->keyBy(fn($r) => Carbon::parse($r->d)->format('Y-m-d'));
    $out = $in = [];
    $cursor = $start->copy()->startOfDay();
    while ($cursor->lte($end)) {
      $labels[] = $cursor->format('M d');
      $row = $byDay->get($cursor->format('Y-m-d'));
      $out[] = $row ? (int)$row->out_cnt : 0;
      $in[]  = $row ? (int)$row->in_cnt  : 0;
      $cursor->addDay();
    }

    $chartConfig = [
      'type' => 'bar',
      'data' => [
        'labels' => $labels,
        'datasets' => [
          ['label' => 'Outbound', 'data' => $out, '_brand' => 'primary', 'stack' => 'mail'],
          ['label' => 'Inbound',  'data' => $in,  '_brand' => 'green',   'stack' => 'mail'],
        ],
      ],
      'options' => [
        'scales' => [
          'x' => ['stacked' => true],
          'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
        ],
      ],
    ];

    // recent rows list (optional)
    $rows = DB::table('emails')
      ->where('tenant_id', $tenant->id)
      ->whereBetween(DB::raw($tsExpr), [$start, $end])
      ->select([
        'id',
        'subject',
        'direction',
        DB::raw("$tsExpr as ts"),
        DB::raw($has('from_email') ? 'from_email' : "'' as from_email"),
        DB::raw($has('recipient_email') ? 'recipient_email' : "'' as recipient_email"),
        DB::raw($has('status') ? 'status' : "'sent' as status"),
      ])
      ->orderBy('ts', 'desc')
      ->paginate(25)
      ->withQueryString();

    return view('reports.emails-activity', [
      'tenant'      => $tenant->id,
      'chartConfig' => $chartConfig,
      'rows'        => $rows,
      'filters'     => ['range' => $range],
      'series'      => $series,
    ]);
  }

  // ------------------------------------------------------------------------- // 
  // ---------------------- AR AGING Report ---------------------------------- // 
  // ------------------------------------------------------------------------- // 
  public function arAging(Request $r, \App\Models\Tenant $tenant)
  {
    $this->authorize('view', $tenant);

    $r->validate([
      'as_of'  => ['nullable', 'date'],
      'status' => ['nullable', Rule::in(['sent', 'overdue', 'draft', 'paid', 'void'])],
      'bucket' => ['nullable', Rule::in(['current', '0-30', '31-60', '61-90', '90+', 'all'])],
      'sort'   => ['nullable', Rule::in(['due_date', 'number', 'client_name', 'balance_due', 'created_at'])],
      'dir'    => ['nullable', Rule::in(['asc', 'desc'])],
      'q'      => ['nullable', 'string', 'max:100'],
      'min'    => ['nullable', 'numeric'],
      'max'    => ['nullable', 'numeric'],
    ]);

    $asOf  = $r->date('as_of') ?? now();
    $status = $r->input('status'); // default: sent+overdue
    $bucket = $r->input('bucket', 'all');
    $sort   = $r->input('sort', 'due_date');
    $dir    = $r->input('dir', 'asc');
    $qText  = trim((string) $r->input('q', ''));
    $min    = $r->input('min');
    $max    = $r->input('max');

    // Resolve invoice columns/expressions
    ['numberCol' => $numberCol, 'clientCol' => $clientCol, 'dueCol' => $dueCol, 'balanceExpr' => $balanceExpr] = $this->invoiceColumns();

    // CASE expression for bucket (days past due as of $asOf)
    $asOfSql = $asOf->toDateString(); // safe for binding
    $bucketCase = "
        CASE
            WHEN DATEDIFF(?, `$dueCol`) < 0 THEN 'current'
            WHEN DATEDIFF(?, `$dueCol`) BETWEEN 0 AND 30 THEN '0-30'
            WHEN DATEDIFF(?, `$dueCol`) BETWEEN 31 AND 60 THEN '31-60'
            WHEN DATEDIFF(?, `$dueCol`) BETWEEN 61 AND 90 THEN '61-90'
            ELSE '90+'
        END
    ";

    // Base (open A/R by default)
    $base = \DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->when(
        $status,
        fn($q) => $q->where('status', $status),
        fn($q) => $q->whereIn('status', ['sent', 'overdue'])
      )
      // ignore zero or negative balances
      ->whereRaw("({$balanceExpr}) > 0")
      ->when($qText !== '', function ($qq) use ($qText, $numberCol, $clientCol) {
        $qq->where(function ($w) use ($qText, $numberCol, $clientCol) {
          $w->where($numberCol, 'like', "%{$qText}%");
          if ($clientCol) $w->orWhere($clientCol, 'like', "%{$qText}%");
        });
      })
      ->when(isset($min), fn($qq) => $qq->whereRaw("{$balanceExpr} >= ?", [(float)$min]))
      ->when(isset($max), fn($qq) => $qq->whereRaw("{$balanceExpr} <= ?", [(float)$max]));

    // Summary by bucket
    $summaryRows = $base->clone()
      ->selectRaw("$bucketCase as bucket, COUNT(*) as cnt, COALESCE(SUM({$balanceExpr}),0) as total", [
        $asOfSql,
        $asOfSql,
        $asOfSql,
        $asOfSql
      ])
      ->groupBy('bucket')
      ->get()
      ->keyBy('bucket');

    $buckets = ['current', '0-30', '31-60', '61-90', '90+'];

    $summary = [];
    $grandTotal = 0.0;
    foreach ($buckets as $b) {
      $row = $summaryRows->get($b);
      $summary[$b] = [
        'count' => $row->cnt ?? 0,
        'total' => (float) ($row->total ?? 0),
      ];
      $grandTotal += $summary[$b]['total'];
    }

    // Detail rows (optional filter by specific bucket)
    $detail = $base->clone()
      ->when(
        $bucket !== 'all',
        fn($qq) =>
        $qq->whereRaw("$bucketCase = ?", array_merge([$asOfSql, $asOfSql, $asOfSql, $asOfSql], [$bucket]))
      )
      ->select([
        'id',
        $numberCol === 'number' ? 'number' : \DB::raw("`{$numberCol}` as number"),
        $clientCol ? \DB::raw("`{$clientCol}` as client_name") : \DB::raw("'—' as client_name"),
        'status',
        $dueCol === 'due_date' ? 'due_date' : \DB::raw("`{$dueCol}` as due_date"),
        \DB::raw("{$balanceExpr} as balance_due"),
        'created_at',
      ]);

    $sortMap = [
      'due_date'    => $dueCol,
      'number'      => $numberCol,
      'client_name' => $clientCol ?: null,
      'balance_due' => \DB::raw($balanceExpr),
      'created_at'  => 'created_at',
    ];
    $sortCol = $sortMap[$sort] ?? $dueCol;
    if ($sort === 'client_name' && !$clientCol) $sortCol = $numberCol;
    $agingLabels = array_keys($buckets);
    $agingValues = array_values($buckets);

    $agingConfig = [
      'type' => 'bar',
      '_yCurrency' => true, // our charts.js will format Y ticks as currency
      'data' => [
        'labels' => $agingLabels,
        'datasets' => [[
          'label' => 'Outstanding',
          'data'  => $agingValues,
          '_brand' => 'primary',
        ]],
      ],
      'options' => [
        'indexAxis' => 'y',
        'scales' => ['x' => ['beginAtZero' => true]],
      ],
    ];

    $rows = $detail
      ->when($sortCol, fn($qq) => $qq->orderBy($sortCol, $dir))
      ->paginate(25)
      ->withQueryString();

    return view('reports.ar-aging', [
      'tenant'   => $tenant->id,
      'rows'     => $rows,
      'summary'  => $summary,
      'total'    => $grandTotal,
      'agingConfig' => $agingConfig,
      'filters'  => [
        'as_of'  => $asOf->toDateString(),
        'status' => $status,
        'bucket' => $buckets,
        'sort'   => $sort,
        'dir'    => $dir,
        'qText'  => $qText,
        'min'    => $min,
        'max'    => $max,
      ],
    ]);
  }
  // ------------------------------------------------------------------------- // 
  // ---------------------- Export Function ----------------------------------- // 
  // ------------------------------------------------------------------------- // 

  public function export(Request $r, Tenant $tenant): StreamedResponse
  {
    $this->authorize('view', $tenant);

    $type = $r->get('type', 'invoices'); // which dataset
    $rows = app(\App\Services\Reports\ExportService::class)->get($tenant, $type, $r->all());

    $filename = "optic-hub-{$type}-" . now()->format('Ymd-His') . ".csv";
    return response()->streamDownload(function () use ($rows) {
      $out = fopen('php://output', 'w');
      if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
          fputcsv($out, array_map(fn($v) => is_scalar($v) ? $v : json_encode($v), $row));
        }
      }
      fclose($out);
    }, $filename, ['Content-Type' => 'text/csv']);
  }
}
