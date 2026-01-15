<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\TimeEntry\UpdateTimeEntryRequest;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Services\InvoiceTotalsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
  protected InvoiceTotalsService $totalsService;

  public function __construct()
  {
    $this->middleware('auth');
    $this->totalsService = app(InvoiceTotalsService::class);
  }

  public function index(Tenant $tenant)
  {
    $query = \App\Models\TimeEntry::with(['user', 'project', 'task'])
      ->where('tenant_id', $tenant->id);

    $timestampColumn = TimeEntry::query()->first()?->getDates()[0] ?? 'created_at';

    $baseSummaryQuery = TimeEntry::where('tenant_id', $tenant->id);

    // Filters (optional)
    if ($range = request('range')) {
      match ($range) {
        'today' => $query->whereDate($timestampColumn, today()),
        'wtd'   => $query->whereDate($timestampColumn, '>=', now()->startOfWeek()),
        'mtd'   => $query->whereDate($timestampColumn, '>=', now()->startOfMonth()),
        '30d'   => $query->whereDate($timestampColumn, '>=', now()->subDays(30)),
        default => null,
      };
    }
    if ($mid = request('member_id'))   $query->where('user_id', $mid);
    if ($pid = request('project_id'))  $query->where('project_id', $pid);
    if (request()->filled('billable')) $query->where('billable', (bool) request('billable'));
    if ($billing = request('billing_status')) {
      if ($billing === 'unbilled') {
        $query->where('billable', true)->whereNull('invoice_id')->whereNull('billed_at');
      } elseif ($billing === 'billed') {
        $query->where('billable', true)->where(function ($q) {
          $q->whereNotNull('invoice_id')->orWhereNotNull('billed_at');
        });
      } elseif ($billing === 'non_billable') {
        $query->where('billable', false);
      }
    }
    if ($q = request('q'))             $query->where('notes', 'like', "%{$q}%");

    $entries = $query->orderByDesc($timestampColumn)->paginate(15);

    // Summary stats (tenant-wide, not filter-limited)
    $defaultRate = (float) config('optichub.default_hourly_rate', 100);
    $unbilledEntries = (clone $baseSummaryQuery)
      ->with('project')
      ->where('billable', true)
      ->whereNull('invoice_id')
      ->whereNull('billed_at')
      ->get();

    $unbilledValue = $unbilledEntries->sum(function ($e) use ($defaultRate) {
      $rate = (float) ($e->hourly_rate ?? $e->project->hourly_rate ?? $defaultRate);
      $hours = (float) ($e->hours ?? 0);
      return $hours * $rate;
    });

    $summary = [
      'hours_today' => (clone $baseSummaryQuery)->whereDate($timestampColumn, today())->sum('hours'),
      'hours_wtd'   => (clone $baseSummaryQuery)->whereDate($timestampColumn, '>=', now()->startOfWeek())->sum('hours'),
      'hours_mtd'   => (clone $baseSummaryQuery)->whereDate($timestampColumn, '>=', now()->startOfMonth())->sum('hours'),
      'unbilled'    => (float) $unbilledEntries->sum('hours'),
      'unbilled_value' => $unbilledValue,
    ];

    // Filters data
    $members = User::where('tenant_id', $tenant->id)
      ->orderBy('last_name')
      ->select('id', 'first_name', 'last_name', 'username')
      ->selectRaw("TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) as name")
      ->get();

    $projects = Project::where('tenant_id', $tenant->id)
      ->orderBy('project_name')
      ->get(['id', 'project_name as name']);

    $draftInvoices = Invoice::where('tenant_id', $tenant->id)
      ->where('status', 'draft')
      ->orderByDesc('created_at')
      ->get(['id', 'invoice_number', 'contact_id']);

    // Tasks (for modal task filtering)
    $tasks = Task::where('tenant_id', $tenant->id)
      ->whereNotNull('project_id')
      ->select('id', 'title', 'project_id')
      ->orderBy('title')
      ->get();

    $filters = array_merge([
      'range' => null,
      'member_id' => null,
      'project_id' => null,
      'q' => null,
      'billable' => null,
      'billing_status' => null,
    ], request()->only(['range', 'member_id', 'project_id', 'q', 'billable', 'billing_status']));

    return view('time.index', compact('tenant', 'entries', 'summary', 'members', 'projects', 'filters', 'draftInvoices', 'tasks'));
  }

  // GET {tenant}/time/create 
  public function create(): View
  {
    $tenantParam = request()->route('tenant');
    $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int)$tenantParam;

    $users    = User::where('tenant_id', $tenantId)->select('id', 'first_name', 'last_name',  'username')->orderBy('last_name')->get();
    $projects = Project::where('tenant_id', $tenantId)
      ->select('id', 'project_name as name')
      ->orderBy('project_name')
      ->get();
    $tasks    = Task::where('tenant_id', $tenantId)->select('id', 'title')->orderBy('title')->get();

    return view('time.create', compact('users', 'projects', 'tasks'));
  }

  // POST {tenant}/time
  public function store(StoreTimeEntryRequest $request): RedirectResponse
  {
    $data = $request->validated();

    $tenantParam = $request->route('tenant');
    $data['tenant_id'] = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int)$tenantParam;
    $data['user_id'] = $data['user_id'] ?? auth()->id();
    $data['date'] = $data['date'] ?? now()->toDateString();
    $data['billable'] = $data['billable'] ?? true;
    if (empty($data['hourly_rate']) && !empty($data['project_id'])) {
      $projectRate = Project::where('tenant_id', $data['tenant_id'])
        ->where('id', $data['project_id'])
        ->value('hourly_rate');
      $data['hourly_rate'] = $projectRate ?? null;
    }

    // If a project has tasks, encourage linking the time to a task
    if (!empty($data['project_id']) && empty($data['task_id'])) {
      $projectHasTasks = Project::where('tenant_id', $data['tenant_id'])
        ->where('id', $data['project_id'])
        ->whereHas('tasks')
        ->exists();
      if ($projectHasTasks) {
        return back()
          ->withInput()
          ->with('error', 'Please select a task for this project so time links correctly.');
      }
    }

    TimeEntry::create($data);

    return redirect()
      ->route('tenant.time.index', ['tenant' => $data['tenant_id']])
      ->with('success_message', 'Time entry added successfully.');
  }

  // GET {tenant}/time/{entry}/edit  (rename your editForm to edit)
  public function edit(Tenant $tenant, TimeEntry $entry): View
  {
    abort_unless($entry->tenant_id === $tenant->id, 404);
    if ($this->isBilled($entry) && ! $this->canManageBilled()) {
      abort(403, 'Billed entries can only be edited by an admin.');
    }

    $tenantId = $entry->tenant_id;
    $users    = User::where('tenant_id', $tenantId)
      ->select('id', 'first_name', 'last_name', 'username')
      ->selectRaw("TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) as name")
      ->orderBy('last_name')
      ->get();
    $projects = Project::where('tenant_id', $tenantId)
      ->select('id', 'project_name as name')
      ->orderBy('project_name')
      ->get();
    $tasks    = Task::where('tenant_id', $tenantId)
      ->select('id', 'title', 'project_id')
      ->orderBy('title')
      ->get();

    return view('time.edit', compact('tenant', 'entry', 'users', 'projects', 'tasks'));
  }

  // PUT/PATCH {tenant}/time/{entry}
  public function update(UpdateTimeEntryRequest $request, Tenant $tenant, TimeEntry $entry): RedirectResponse
  {
    abort_unless($entry->tenant_id === $tenant->id, 404);
    if ($this->isBilled($entry) && ! $this->canManageBilled()) {
      return back()->with('error', 'Billed entries can only be edited by an admin.');
    }

    $entry->update($request->validated());

    return redirect()
      ->route('tenant.time.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Time entry updated.');
  }

  // DELETE {tenant}/time/{entry}
  public function destroy(Tenant $tenant, TimeEntry $entry): RedirectResponse
  {
    // Allow delete if entry belongs to this tenant (simple guard; adjust if you add policies)
    abort_unless($entry->tenant_id === $tenant->id, 404);
    if ($this->isBilled($entry) && ! $this->canManageBilled()) {
      return back()->with('error', 'Billed entries can only be deleted by an admin.');
    }

    $entry->delete();

    return redirect()
      ->route('tenant.time.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Time entry deleted.');
  }

  public function bulkBill(Tenant $tenant)
  {
    $entryIds = request('entry_ids', []);
    $grouping = request('grouping', 'entry');
    $invoiceId = request('invoice_id');
    $createNew = request('create_new') === '1';

    if (empty($entryIds) || !is_array($entryIds)) {
      return back()->with('error', 'Select at least one time entry.');
    }

    $entries = TimeEntry::with('project')
      ->whereIn('id', $entryIds)
      ->where('tenant_id', $tenant->id)
      ->get();

    if ($entries->isEmpty()) {
      return back()->with('error', 'No matching time entries found.');
    }

    // Eligibility: billable and unbilled
    foreach ($entries as $e) {
      if ($e->billable === false || $e->billing_status !== 'unbilled') {
        return back()->with('error', 'Only billable, unbilled entries can be invoiced.');
      }
    }

    // Ensure same client/contact (via project->contact_id if available)
    $contactIds = $entries->map(function ($e) {
      return optional($e->project)->contact_id;
    })->unique()->filter();
    if ($contactIds->count() !== 1) {
      return back()->with('error', 'Selected time entries must belong to the same client.');
    }
    $contactId = $contactIds->first();

    DB::beginTransaction();
    try {
      $invoice = null;
      if ($createNew || !$invoiceId) {
        $invoice = Invoice::create([
          'tenant_id' => $tenant->id,
          'contact_id' => $contactId,
          'status' => 'draft',
          'issue_date' => now(),
          'due_date' => now()->addDays(14),
          'invoice_number' => null,
        ]);
      } else {
        $invoice = Invoice::where('tenant_id', $tenant->id)->where('status', 'draft')->find($invoiceId);
        if (!$invoice) {
          throw new \RuntimeException('Invoice not found or not draft.');
        }
        if ((int) $invoice->contact_id !== (int) $contactId) {
          throw new \RuntimeException('Invoice belongs to a different client.');
        }
      }

      // Grouping
      $items = [];
      if ($grouping === 'project') {
        $grouped = $entries->groupBy(fn($e) => $e->project_id);
        foreach ($grouped as $projectId => $group) {
          $hours = $group->sum('hours');
          $projectName = optional($group->first()->project)->project_name ?? 'Project';
          $items[] = [
            'name' => $projectName,
            'description' => 'Time entries for project',
            'quantity' => $hours,
            'unit_price' => $group->first()->hourly_rate ?? $group->first()->project->hourly_rate ?? config('optichub.default_hourly_rate', 100),
            'source' => ['type' => 'time_entry_group', 'id' => null],
          ];
        }
      } elseif ($grouping === 'day') {
        $grouped = $entries->groupBy(fn($e) => optional($e->date)->toDateString());
        foreach ($grouped as $date => $group) {
          $hours = $group->sum('hours');
          $items[] = [
            'name' => 'Time ' . $date,
            'description' => 'Logged hours for ' . $date,
            'quantity' => $hours,
            'unit_price' => $group->first()->hourly_rate ?? $group->first()->project->hourly_rate ?? config('optichub.default_hourly_rate', 100),
            'source' => ['type' => 'time_entry_group', 'id' => null],
          ];
        }
      } else {
        foreach ($entries as $e) {
          $items[] = [
            'name' => 'Time ' . ($e->date?->toDateString() ?? ''),
            'description' => $e->notes,
            'quantity' => $e->hours ?? 0,
            'unit_price' => $e->hourly_rate ?? $e->project->hourly_rate ?? config('optichub.default_hourly_rate', 100),
            'source' => ['type' => 'time_entry', 'id' => $e->id],
          ];
        }
      }

      foreach ($items as $i => $item) {
        $invoice->lineItems()->create([
          'tenant_id' => $tenant->id,
          'position' => $i,
          'name' => $item['name'] ?? 'Time',
          'description' => $item['description'] ?? null,
          'quantity' => $item['quantity'] ?? 0,
          'unit_price' => $item['unit_price'] ?? 0,
          'line_total' => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
          'is_taxable' => true,
          'source_type' => $item['source']['type'] ?? null,
          'source_id' => $item['source']['id'] ?? null,
        ]);
      }

      $invoice->load('lineItems');
      $this->totalsService->compute($invoice);
      $invoice->total_amount = $invoice->total;
      $invoice->balance_due = $invoice->total;
      $invoice->save();

      TimeEntry::whereIn('id', $entries->pluck('id'))
        ->update(['invoice_id' => $invoice->id, 'billed_at' => now()]);

      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error('[time.bulkBill] ' . $e->getMessage());
      return back()->with('error', $e->getMessage());
    }

    return back()->with('success_message', 'Added ' . $entries->count() . ' entries to Invoice #' . ($invoice->invoice_number ?? $invoice->id));
  }

  private function isBilled(TimeEntry $entry): bool
  {
    return $entry->billing_status === 'billed';
  }

  private function canManageBilled(): bool
  {
    $role = strtolower(auth()->user()->role ?? '');
    return in_array($role, ['owner', 'admin', 'manager', 'provider', 'super_admin', 'superadmin'], true);
  }
}
