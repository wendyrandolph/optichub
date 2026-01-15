<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RecurringInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web,admin');
    }

    public function index(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);

        $recurring = RecurringInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('next_run_at')
            ->get();

        return view('invoices.recurring.index', [
            'tenant' => $tenant,
            'recurring' => $recurring,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);

        $clients = Client::where('tenant_id', $tenant->id)->orderBy('firstName')->get();
        $projects = Project::where('tenant_id', $tenant->id)->orderBy('project_name')->get();

        return view('invoices.recurring.create', [
            'tenant' => $tenant,
            'clients' => $clients,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('view', $tenant);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:120'],
            'frequency' => ['required', 'in:weekly,monthly,yearly'],
            'interval' => ['required', 'integer', 'min:1', 'max:12'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'due_days' => ['nullable', 'integer', 'min:0', 'max:60'],
            'auto_send' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $lineItems = collect($data['items'])->map(function ($item, $idx) {
            return [
                'position' => $idx + 1,
                'description' => $item['description'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'is_taxable' => true,
            ];
        })->values()->all();

        $nextRun = $this->nextRunAt(
            $data['frequency'],
            (int) $data['interval'],
            $data['day_of_week'] ?? null,
            $data['day_of_month'] ?? null
        );

        RecurringInvoice::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $data['contact_id'],
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'] ?? null,
            'status' => 'active',
            'frequency' => $data['frequency'],
            'interval' => (int) $data['interval'],
            'day_of_week' => $data['day_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'due_days' => (int) ($data['due_days'] ?? 14),
            'auto_send' => (bool) ($data['auto_send'] ?? false),
            'next_run_at' => $nextRun,
            'line_items' => $lineItems,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('tenant.invoices.recurring.index', ['tenant' => $tenant->id])
            ->with('status', 'Recurring invoice created.');
    }

    public function toggle(Tenant $tenant, RecurringInvoice $recurring): RedirectResponse
    {
        $this->authorize('view', $tenant);
        abort_unless($recurring->tenant_id === $tenant->id, 404);

        $recurring->status = $recurring->status === 'active' ? 'paused' : 'active';
        $recurring->save();

        return back()->with('status', 'Recurring invoice updated.');
    }

    private function nextRunAt(string $frequency, int $interval, ?int $dayOfWeek, ?int $dayOfMonth): Carbon
    {
        $now = now();

        return match ($frequency) {
            'weekly' => $now->copy()->startOfWeek()->addDays($dayOfWeek ?? 1)->addWeeks($interval),
            'yearly' => $now->copy()->startOfYear()->addYears($interval),
            default => $now->copy()->startOfMonth()->addMonths($interval)->day($dayOfMonth ?? 1),
        };
    }
}
