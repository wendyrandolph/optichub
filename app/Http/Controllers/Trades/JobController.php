<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\Project;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use App\Models\TradeJob;
use App\Models\TradeJobTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;


class JobController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorize('viewAny', TradeJob::class);

        $tenantId = $tenant->id;
        $user = $request->user();
        $isTech = $user?->isTech() ?? false;
        $techId = $user?->id;
        $tz = $tenant->timezone ?? config('app.timezone');
        $now = Carbon::now($tz)->timezone('UTC');
        $todayStart = Carbon::now($tz)->startOfDay()->timezone('UTC');
        $todayEnd = Carbon::now($tz)->endOfDay()->timezone('UTC');

        if ($isTech) {
            $nextApptSub = DB::table('trade_appointments as ta')
                ->join('appointment_assignments as aa', function ($join) use ($techId, $tenantId) {
                    $join->on('aa.appointment_id', '=', 'ta.id')
                        ->where('aa.user_id', '=', $techId)
                        ->where('aa.tenant_id', '=', $tenantId);
                })
                ->selectRaw('ta.trade_job_id, MIN(ta.start_at) as next_start_at')
                ->where('ta.tenant_id', $tenantId)
                ->where('ta.start_at', '>=', $todayStart)
                ->groupBy('ta.trade_job_id');

            $activeTimerSub = DB::table('trade_job_timers')
                ->selectRaw('trade_job_id, COUNT(*) as active_timer_count')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $techId)
                ->whereNull('ended_at')
                ->groupBy('trade_job_id');

            $query = TradeJob::query()
                ->where('tenant_id', $tenantId)
                ->leftJoinSub($nextApptSub, 'next_appt', function ($join) {
                    $join->on('next_appt.trade_job_id', '=', 'trade_jobs.id');
                })
                ->leftJoinSub($activeTimerSub, 'active_timer', function ($join) {
                    $join->on('active_timer.trade_job_id', '=', 'trade_jobs.id');
                })
                ->select('trade_jobs.*')
                ->selectRaw('next_appt.next_start_at as next_appointment_start_at')
                ->selectRaw('COALESCE(active_timer.active_timer_count, 0) as active_timer_count')
                ->with([
                    'client',
                    'company',
                    'serviceLocation',
                    'project',
                    'appointments' => function ($q) use ($techId, $todayStart) {
                        $q->whereHas('assignments', function ($assignments) use ($techId) {
                            $assignments->where('user_id', $techId);
                        })
                            ->where('start_at', '>=', $todayStart)
                            ->orderBy('start_at')
                            ->with([
                                'assignments' => function ($assignments) use ($techId) {
                                    $assignments->where('user_id', $techId)->with('user');
                                },
                            ]);
                    },
                ]);
        } else {
            // Subquery: next upcoming appointment start per job
            $nextApptSub = DB::table('trade_appointments')
                ->selectRaw('trade_job_id, MIN(start_at) as next_start_at')
                ->where('tenant_id', $tenantId)
                ->where('start_at', '>=', $now)
                ->groupBy('trade_job_id');

            $query = TradeJob::query()
                ->where('tenant_id', $tenantId)
                ->leftJoinSub($nextApptSub, 'next_appt', function ($join) {
                    $join->on('next_appt.trade_job_id', '=', 'trade_jobs.id');
                })
                ->select('trade_jobs.*')
                ->selectRaw('next_appt.next_start_at as next_appointment_start_at')
                ->with([
                    'client',
                    'company',
                    'serviceLocation',
                    'project',
                    'nextAppointment' => function ($q) {
                        $q->withCount('assignments')
                            ->with('assignments.user');
                    },
                ]);
        }

        $search = trim((string) $request->input('q'));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('summary', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($client) use ($search) {
                        $client->where('firstName', 'like', "%{$search}%")
                            ->orWhere('lastName', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$search}%"]);
                    });
            });
        }

        // ---- Filters (office-only) ----
        if (!$isTech) {
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->string('type')); // service|project
            }

            if ($request->filled('scheduling')) {
                if ($request->string('scheduling') === 'unscheduled') {
                    $query->whereNull('next_appt.next_start_at');
                } elseif ($request->string('scheduling') === 'scheduled') {
                    $query->whereNotNull('next_appt.next_start_at');
                }
            }
        }

        if ($isTech) {
            $query->where(function ($q) {
                $q->whereNotNull('next_appt.next_start_at')
                    ->orWhereNotNull('active_timer.active_timer_count');
            });

            $query
                ->orderByRaw(
                    'CASE WHEN next_appt.next_start_at BETWEEN ? AND ? THEN 0 WHEN next_appt.next_start_at IS NOT NULL THEN 1 WHEN active_timer.active_timer_count IS NOT NULL THEN 2 ELSE 3 END ASC',
                    [$todayStart, $todayEnd]
                )
                ->orderBy('next_appointment_start_at', 'asc')
                ->orderByDesc('updated_at');
        } else {
            // ---- Sorting: Unscheduled first, then next start, then recently updated ----
            // Sort: Unscheduled first, then open-ish statuses first, then by next appt / updated_at.
            $query
                // Unscheduled first (no next appointment)
                ->orderByRaw('CASE WHEN next_appt.next_start_at IS NULL THEN 0 ELSE 1 END ASC')
                // Status priority (customize list as needed)
                ->orderByRaw("
        CASE
            WHEN status = 'open' THEN 0
            WHEN status = 'scheduled' THEN 1
            WHEN status = 'in_progress' THEN 2
            WHEN status = 'completed' THEN 3
            WHEN status = 'closed' THEN 4
            ELSE 9
        END ASC
    ")
                // For scheduled jobs, sort by next appointment start time (soonest first)
                ->orderBy('next_appointment_start_at', 'asc')
                // Fallback
                ->orderByDesc('updated_at');
        }

        $jobs = $query->paginate(20)->appends($request->query());

        return view('trades.jobs.index', [
            'tenant' => $tenant,
            'jobs' => $jobs,
            'isFieldTech' => $isTech,
        ]);
    }

    public function create(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeJob::class);

        $selectedClientId = (int) $request->query('client', 0);
        $selectedLocationId = (int) $request->query('location', 0);
        $templateId = (int) $request->query('template', 0);
        $template = null;
        if ($templateId) {
            $template = TradeJobTemplate::where('tenant_id', $tenant->id)
                ->where('id', $templateId)
                ->where('is_active', true)
                ->with(['items', 'checklistItems'])
                ->first();
        }

        return view('trades.jobs.create', [
            'tenant' => $tenant,
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'locations' => ServiceLocation::where('tenant_id', $tenant->id)->orderBy('label')->get(),
            'templates' => TradeJobTemplate::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get(),
            'selectedClientId' => $selectedClientId ?: null,
            'selectedLocationId' => $selectedLocationId ?: null,
            'selectedTemplate' => $template,
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeJob::class);

        $data = $this->validateData($request, $tenant);
        $data['tenant_id'] = $tenant->id;

        if (Schema::hasColumn('trade_jobs', 'job_template_id')) {
            $templateId = (int) $request->input('template_id', 0);
            if ($templateId) {
                $data['job_template_id'] = TradeJobTemplate::where('tenant_id', $tenant->id)
                    ->where('id', $templateId)
                    ->value('id');
            } else {
                $data['job_template_id'] = null;
            }
        }

        if (empty($data['company_id']) && !empty($data['client_id'])) {
            $data['company_id'] = Client::where('tenant_id', $tenant->id)
                ->where('id', $data['client_id'])
                ->value('client_company_id');
        }

        $job = TradeJob::create($data);

        $items = $this->normalizeItems($request->input('items', []), $tenant->id);
        if (!empty($items)) {
            $job->items()->createMany($items);
        }

        $checklist = $this->normalizeChecklist($request->input('checklist', []));
        if (empty($checklist)) {
            $templateId = (int) $request->input('template_id', 0);
            if ($templateId) {
                $template = TradeJobTemplate::where('tenant_id', $tenant->id)
                    ->where('id', $templateId)
                    ->with('checklistItems')
                    ->first();
                if ($template) {
                    $checklist = $template->checklistItems
                        ->sortBy('sort_order')
                        ->map(fn($item) => [
                            'tenant_id' => $tenant->id,
                            'label' => $item->label,
                            'is_required' => (bool) $item->is_required,
                            'sort_order' => (int) $item->sort_order,
                        ])->values()->toArray();
                }
            }
        }
        if (!empty($checklist)) {
            $job->checklistItems()->createMany(
                collect($checklist)->map(fn($item) => array_merge($item, ['tenant_id' => $tenant->id]))->toArray()
            );
        }

        return redirect()
            ->route('tenant.trades.jobs.show', ['tenant' => $tenant->id, 'job' => $job->id])
            ->with('success_message', 'Job created.');
    }

    public function show(Tenant $tenant, TradeJob $job)
    {
        $user = auth()->user();
        if ($user?->isTech()) {
            $appointment = $job->appointments()
                ->whereHas('assignments', fn($q) => $q->where('user_id', $user->id))
                ->orderBy('start_at')
                ->first();

            if ($appointment) {
                return redirect()->route('tenant.trades.field.show', [
                    'tenant' => $tenant->id,
                    'appointment' => $appointment->id,
                ]);
            }

            return redirect()
                ->route('tenant.trades.jobs.index', ['tenant' => $tenant->id])
                ->with('error_message', 'No appointment assigned to you for this job.');
        }

        $this->authorize('view', $job);
        $this->abortIfWrongTenant($tenant, $job);

        $job->load([
            'client',
            'company',
            'serviceLocation',
            'project',
            'items',
            'checklistItems',
            'invoices',
            'nextAppointment' => function ($q) {
                $q->withCount('assignments')
                    ->with('assignments.user');
            },
        ]);
        $history = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->where('client_id', $job->client_id)
            ->where('id', '!=', $job->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
        $jobChannel = $job->ensureChatChannel();
        return view('trades.jobs.show', [
            'tenant' => $tenant,
            'job' => $job,
            'history' => $history,
            'jobChannel' => $jobChannel,
        ]);
    }

    public function edit(Tenant $tenant, TradeJob $job)
    {
        $this->authorize('update', $job);
        $this->abortIfWrongTenant($tenant, $job);

        return view('trades.jobs.edit', [
            'tenant' => $tenant,
            'job' => $job->load(['client', 'company', 'serviceLocation', 'items', 'checklistItems']),
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'locations' => ServiceLocation::where('tenant_id', $tenant->id)
                ->where('client_id', $job->client_id)
                ->orderBy('label')
                ->get(),
            'templates' => TradeJobTemplate::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, TradeJob $job)
    {
        $this->authorize('update', $job);
        $this->abortIfWrongTenant($tenant, $job);

        $data = $this->validateData($request, $tenant, $job);

        if (Schema::hasColumn('trade_jobs', 'job_template_id')) {
            $templateId = (int) $request->input('template_id', 0);
            if ($templateId) {
                $data['job_template_id'] = TradeJobTemplate::where('tenant_id', $tenant->id)
                    ->where('id', $templateId)
                    ->value('id');
            } else {
                $data['job_template_id'] = null;
            }
        }

        if (empty($data['company_id']) && !empty($data['client_id'])) {
            $data['company_id'] = Client::where('tenant_id', $tenant->id)
                ->where('id', $data['client_id'])
                ->value('client_company_id');
        }

        $job->update($data);

        $job->items()->delete();
        $items = $this->normalizeItems($request->input('items', []), $tenant->id);
        if (!empty($items)) {
            $job->items()->createMany($items);
        }

        $job->checklistItems()->delete();
        $checklist = $this->normalizeChecklist($request->input('checklist', []));
        if (!empty($checklist)) {
            $job->checklistItems()->createMany(
                collect($checklist)->map(fn($item) => array_merge($item, ['tenant_id' => $tenant->id]))->toArray()
            );
        }

        return redirect()
            ->route('tenant.trades.jobs.show', ['tenant' => $tenant->id, 'job' => $job->id])
            ->with('success_message', 'Job updated.');
    }

    public function destroy(Tenant $tenant, TradeJob $job)
    {
        $this->authorize('delete', $job);
        $this->abortIfWrongTenant($tenant, $job);

        $job->delete();

        return redirect()
            ->route('tenant.trades.jobs.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Job deleted.');
    }

    public function convertToProject(Tenant $tenant, TradeJob $job)
    {
        $this->authorize('update', $job);
        $this->abortIfWrongTenant($tenant, $job);

        if ($job->type !== 'project') {
            return back()->with('error_message', 'Only project jobs can be converted.');
        }

        if ($job->project_id) {
            return back()->with('error_message', 'This job already has a linked project.');
        }

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $job->client_id,
            'client_company_id' => $job->company_id,
            'project_name' => 'Job: ' . $job->summary,
            'status' => 'open',
            'description' => $job->description,
            'uses_phases' => false,
        ]);

        $job->update(['project_id' => $project->id]);

        return redirect()
            ->route('tenant.trades.jobs.show', ['tenant' => $tenant->id, 'job' => $job->id])
            ->with('success_message', 'Converted to project.');
    }

    protected function validateData(Request $request, Tenant $tenant, ?TradeJob $job = null): array
    {
        $scope = $tenant->trades_warranty_scope ?? 'job';
        $workType = $tenant->trades_work_type ?? 'both';
        if (!in_array($workType, ['residential', 'commercial', 'both'], true)) {
            $workType = 'both';
        }
        $propertyRule = $workType === 'both'
            ? ['required', Rule::in(['residential', 'commercial'])]
            : ['nullable', Rule::in(['residential', 'commercial'])];

        $data = $request->validate([
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
            'type' => ['required', Rule::in(['service', 'project'])],
            'property_type' => $propertyRule,
            'status' => ['required', 'string', 'max:40'],
            'summary' => 'required|string|max:255',
            'description' => 'nullable|string',
            'warranty_starts_on' => [$scope === 'job' ? 'nullable' : 'nullable', 'date'],
            'warranty_ends_on' => [$scope === 'job' ? 'nullable' : 'nullable', 'date'],
            'warranty_terms' => [$scope === 'job' ? 'nullable' : 'nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.warranty_ends_on' => ['nullable', 'date'],
            'items.*.warranty_terms' => ['nullable', 'string', 'max:255'],
            'checklist' => ['nullable', 'array'],
            'checklist.*.label' => ['nullable', 'string', 'max:255'],
            'checklist.*.is_required' => ['nullable', 'boolean'],
            'template_id' => ['nullable', 'integer'],
        ]);

        if ($workType !== 'both') {
            $data['property_type'] = $workType;
        }

        return $data;
    }

    protected function normalizeItems(array $items, int $tenantId): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $qty = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['unit_price'] ?? 0);

            $normalized[] = [
                'tenant_id' => $tenantId,
                'description' => $description,
                'quantity' => $qty,
                'unit_price' => $rate,
                'line_total' => $qty * $rate,
                'warranty_ends_on' => $item['warranty_ends_on'] ?? null,
                'warranty_terms' => trim((string) ($item['warranty_terms'] ?? '')) ?: null,
                'sort_order' => (int) $index,
            ];
        }

        return $normalized;
    }

    protected function normalizeChecklist(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'is_required' => !empty($item['is_required']),
                'sort_order' => (int) $index,
            ];
        }

        return $normalized;
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeJob $job): void
    {
        if ((int) $job->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
