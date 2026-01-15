<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use App\Models\TradeJob;
use App\Models\TradeServicePlan;
use App\Models\TradeServicePlanOverride;
use App\Models\TradeServicePlanOccurrence;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TradeServicePlanController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorize('viewAny', TradeServicePlan::class);

        $query = TradeServicePlan::query()
            ->where('tenant_id', $tenant->id)
            ->with(['client', 'serviceLocation']);

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($client) use ($q) {
                        $client->where('firstName', 'like', "%{$q}%")
                            ->orWhere('lastName', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $plans = $query
            ->orderByRaw('next_occurrence IS NULL')
            ->orderBy('next_occurrence')
            ->orderBy('title')
            ->paginate(20)
            ->appends($request->query());

        return view('trades.service-plans.index', [
            'tenant' => $tenant,
            'plans' => $plans,
        ]);
    }

    public function create(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeServicePlan::class);

        return view('trades.service-plans.create', [
            'tenant' => $tenant,
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'locations' => ServiceLocation::where('tenant_id', $tenant->id)->orderBy('label')->get(),
            'selectedClientId' => (int) $request->query('client_id', 0) ?: null,
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeServicePlan::class);

        $data = $this->validateData($request, $tenant);
        $data['tenant_id'] = $tenant->id;

        $plan = TradeServicePlan::create($data);

        $items = $this->normalizeItems($request->input('items', []));
        if (!empty($items)) {
            $plan->items()->createMany($items);
        }

        $plan->next_occurrence = $this->computeNextOccurrence($plan);
        $plan->save();

        return redirect()
            ->route('tenant.trades.service-plans.show', ['tenant' => $tenant->id, 'service_plan' => $plan->id])
            ->with('success_message', 'Service plan created.');
    }

    public function show(Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('view', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        $service_plan->load([
            'client',
            'company',
            'serviceLocation',
            'items',
            'overrides' => fn($q) => $q->orderBy('override_date'),
        ]);

        return view('trades.service-plans.show', [
            'tenant' => $tenant,
            'plan' => $service_plan,
        ]);
    }

    public function edit(Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('update', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        return view('trades.service-plans.edit', [
            'tenant' => $tenant,
            'plan' => $service_plan->load([
                'items',
                'overrides' => fn($q) => $q->orderBy('override_date'),
            ]),
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'locations' => ServiceLocation::where('tenant_id', $tenant->id)->orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('update', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        $data = $this->validateData($request, $tenant, $service_plan);
        $service_plan->update($data);

        $service_plan->items()->delete();
        $items = $this->normalizeItems($request->input('items', []));
        if (!empty($items)) {
            $service_plan->items()->createMany($items);
        }

        $service_plan->next_occurrence = $this->computeNextOccurrence($service_plan);
        $service_plan->save();

        return redirect()
            ->route('tenant.trades.service-plans.show', ['tenant' => $tenant->id, 'service_plan' => $service_plan->id])
            ->with('success_message', 'Service plan updated.');
    }

    public function destroy(Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('delete', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        $service_plan->delete();

        return redirect()
            ->route('tenant.trades.service-plans.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Service plan deleted.');
    }

    public function storeOverride(Request $request, Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('update', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        $data = $request->validate([
            'override_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        TradeServicePlanOverride::firstOrCreate([
            'trade_service_plan_id' => $service_plan->id,
            'override_date' => $data['override_date'],
        ], [
            'created_by' => auth()->id(),
            'note' => $data['note'] ?? null,
        ]);

        $service_plan->next_occurrence = $this->computeNextOccurrence($service_plan->fresh('overrides'));
        $service_plan->save();

        return back()->with('success_message', 'Override date added.');
    }

    public function destroyOverride(
        Tenant $tenant,
        TradeServicePlan $service_plan,
        TradeServicePlanOverride $override
    ) {
        $this->authorize('update', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        if ((int) $override->trade_service_plan_id !== (int) $service_plan->id) {
            abort(404);
        }

        $override->delete();

        $service_plan->next_occurrence = $this->computeNextOccurrence($service_plan->fresh('overrides'));
        $service_plan->save();

        return back()->with('success_message', 'Override date removed.');
    }

    public function generateJob(Tenant $tenant, TradeServicePlan $service_plan)
    {
        $this->authorize('update', $service_plan);
        $this->abortIfWrongTenant($tenant, $service_plan);

        if ($service_plan->status !== 'active') {
            return back()->with('error_message', 'Activate the plan before generating jobs.');
        }

        $nextDate = $service_plan->next_occurrence
            ? Carbon::parse($service_plan->next_occurrence)->toDateString()
            : null;

        if (!$nextDate) {
            return back()->with('error_message', 'No upcoming occurrence available.');
        }

        $existing = TradeServicePlanOccurrence::where('tenant_id', $tenant->id)
            ->where('trade_service_plan_id', $service_plan->id)
            ->whereDate('scheduled_for', $nextDate)
            ->first();

        if ($existing && $existing->trade_job_id) {
            return back()->with('error_message', 'A job is already scheduled for this occurrence.');
        }

        $job = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $service_plan->client_id,
            'company_id' => $service_plan->company_id,
            'service_location_id' => $service_plan->service_location_id,
            'type' => 'service',
            'status' => 'open',
            'summary' => $service_plan->title,
            'description' => $service_plan->notes,
        ]);

        $items = $service_plan->items()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) use ($job) {
                return [
                    'tenant_id' => $job->tenant_id,
                    'trade_job_id' => $job->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => $item->unit_price ?? 0,
                    'line_total' => ($item->quantity ?? 0) * ($item->unit_price ?? 0),
                    'sort_order' => $item->sort_order ?? 0,
                ];
            })
            ->toArray();

        if (!empty($items)) {
            $job->items()->createMany($items);
        }

        $occurrence = $existing ?? TradeServicePlanOccurrence::create([
            'tenant_id' => $tenant->id,
            'trade_service_plan_id' => $service_plan->id,
            'scheduled_for' => $nextDate,
        ]);

        $occurrence->update([
            'trade_job_id' => $job->id,
            'generated_at' => now(),
        ]);

        $override = $service_plan->overrides()
            ->whereDate('override_date', $nextDate)
            ->first();
        if ($override) {
            $override->delete();
        }

        $service_plan->next_occurrence = $this->computeNextOccurrence($service_plan->fresh('overrides'));
        $service_plan->save();

        return redirect()
            ->route('tenant.trades.jobs.show', ['tenant' => $tenant->id, 'job' => $job->id])
            ->with('success_message', 'Job created from service plan.');
    }

    protected function validateData(Request $request, Tenant $tenant, ?TradeServicePlan $plan = null): array
    {
        return $request->validate([
            'client_id' => [
                'required',
                Rule::exists('contacts', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'company_id' => [
                'nullable',
                Rule::exists('client_companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'service_location_id' => [
                'nullable',
                Rule::exists('service_locations', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'paused'])],
            'cadence_unit' => ['required', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'cadence_interval' => ['required', 'integer', 'min:1', 'max:12'],
            'cadence_weekday' => ['nullable', 'integer', 'min:0', 'max:6'],
            'cadence_month_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'starts_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $qty = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['unit_price'] ?? 0);

            if ($description === '') {
                continue;
            }

            $lineTotal = $qty * $rate;
            $normalized[] = [
                'description' => $description,
                'quantity' => $qty,
                'unit_price' => $rate,
                'line_total' => $lineTotal,
                'sort_order' => (int) $index,
            ];
        }

        return $normalized;
    }

    protected function computeNextOccurrence(TradeServicePlan $plan): ?string
    {
        if ($plan->status === 'paused') {
            return null;
        }

        $today = Carbon::today();
        $start = Carbon::parse($plan->starts_on)->startOfDay();
        $unit = $plan->cadence_unit;
        $interval = max(1, (int) $plan->cadence_interval);

        $next = $start->copy();

        if ($unit === 'weekly') {
            $weekday = $plan->cadence_weekday ?? $start->dayOfWeek;
            if ($next->dayOfWeek !== $weekday) {
                $next = $next->nextOrSame($weekday);
            }
            while ($next->lt($today)) {
                $next->addWeeks($interval);
            }
        } else {
            $monthDay = $plan->cadence_month_day ?: $start->day;
            $monthDay = max(1, min(28, (int) $monthDay));
            $stepMonths = $unit === 'monthly' ? $interval : ($unit === 'quarterly' ? 3 * $interval : 12 * $interval);

            $next->day(min($monthDay, $next->daysInMonth));
            while ($next->lt($today)) {
                $next->addMonthsNoOverflow($stepMonths);
                $next->day(min($monthDay, $next->daysInMonth));
            }
        }

        $override = $plan->overrides()
            ->whereDate('override_date', '>=', $today)
            ->orderBy('override_date')
            ->value('override_date');

        if ($override) {
            $overrideDate = Carbon::parse($override);
            if ($overrideDate->lt($next)) {
                $next = $overrideDate;
            }
        }

        return $next->toDateString();
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeServicePlan $plan): void
    {
        if ((int) $plan->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
