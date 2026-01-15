<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAssignment;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeJobTimer;
use App\Models\TradeJobTemplate;
use App\Models\TradePtoRequest;
use App\Models\TradeQuote;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Trades\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Tenant $tenant)
    {
        $this->authorize('viewAny', TradeAppointment::class);

        $currentView = (string) request()->query('view', 'calendar');
        $tz = $tenant->timezone ?? config('app.timezone');

        $appointments = null;
        $filters = [];
        $statusOptions = $this->statusOptions();
        if ($currentView === 'list') {
            $query = $this->listAppointmentsQuery($tenant);
            [$query, $filters] = $this->applyListFilters($query, $tenant, $tz);

            if (($filters['sort'] ?? 'soonest') === 'status') {
                $query->orderByRaw(
                    "CASE
                        WHEN status = 'scheduled' THEN 0
                        WHEN status = 'in_progress' THEN 1
                        WHEN status = 'done' THEN 2
                        WHEN status = 'completed' THEN 3
                        WHEN status = 'canceled' THEN 4
                        ELSE 5
                    END ASC"
                );
            }

            $query->orderBy('start_at')->orderBy('end_at');

            if (!empty($filters['issues'])) {
                $allAppointments = $query->get();
                $this->decorateListAppointments($allAppointments, $tenant, $tz);

                $filtered = $allAppointments->filter(fn(TradeAppointment $appointment) => $appointment->has_issues ?? false)
                    ->values();
                $appointments = $this->paginateCollection($filtered, 20);
            } else {
                $appointments = $query->paginate(20)->withQueryString();
                $collection = $appointments->getCollection();
                $this->decorateListAppointments($collection, $tenant, $tz);
                $appointments->setCollection($collection);
            }
        }

        $unscheduledJobs = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments')
            ->with(['client', 'serviceLocation'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('trades.schedule.index', [
            'tenant' => $tenant,
            'appointments' => $appointments,
            'techs' => $this->techUsers($tenant),
            'unscheduledJobs' => $unscheduledJobs,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function create(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeAppointment::class);
        $selectedJobId = null;
        $selectedJob = null;
        $template = null;
        $suggestedTechCount = null;
        $defaultDurationMinutes = 60;
        $bannerMessage = null;
        $prefillNotes = null;
        $quote = null;
        $jobId = (int) $request->query('job', 0);
        if ($jobId) {
            $selectedJob = TradeJob::where('tenant_id', $tenant->id)
                ->where('id', $jobId)
                ->with('jobTemplate')
                ->first();
            $selectedJobId = $selectedJob?->id;
            $template = $selectedJob?->jobTemplate;
        }

        $quoteId = (int) $request->query('quote', 0);
        if ($quoteId) {
            $quote = TradeQuote::where('tenant_id', $tenant->id)
                ->where('id', $quoteId)
                ->first();
        }

        if ($request->query('purpose') === 'site_visit' && $quote) {
            $bannerMessage = 'Scheduling site visit for Quote #' . $quote->id;
            $prefillNotes = 'Site visit / Estimate';
        }

        if (!$template) {
            $templateId = (int) $request->query('template', 0);
            if ($templateId) {
                $template = TradeJobTemplate::where('tenant_id', $tenant->id)
                    ->where('id', $templateId)
                    ->where('is_active', true)
                    ->first();
            }
        }

        if ($template) {
            $defaultDurationMinutes = $template->default_duration_minutes ?: $defaultDurationMinutes;
            $suggestedTechCount = $template->suggested_tech_count;
        }

        $tz = $tenant->timezone ?? config('app.timezone');
        $defaultStartAt = Carbon::now($tz)->addHour()->startOfHour();
        $defaultEndAt = $defaultStartAt->copy()->addMinutes($defaultDurationMinutes);
        [$availabilityStart, $availabilityEnd] = $this->resolveAvailabilityWindow(
            $tenant,
            $request,
            $defaultStartAt,
            $defaultEndAt
        );
        $techs = $this->techUsers($tenant);
        [$availability, $suggestedTechs] = $this->buildAvailabilityData(
            $tenant,
            $techs,
            $availabilityStart,
            $availabilityEnd
        );

        return view('trades.schedule.create', [
            'tenant' => $tenant,
            'jobs' => TradeJob::where('tenant_id', $tenant->id)
                ->with('serviceLocation')
                ->orderBy('summary')
                ->get(),
            'techs' => $techs,
            'selectedJobId' => $selectedJobId,
            'selectedJob' => $selectedJob,
            'defaultStartAt' => $defaultStartAt,
            'defaultEndAt' => $defaultEndAt,
            'suggestedTechCount' => $suggestedTechCount,
            'defaultDurationMinutes' => $defaultDurationMinutes,
            'bannerMessage' => $bannerMessage,
            'prefillNotes' => $prefillNotes,
            'availability' => $availability,
            'suggestedTechs' => $suggestedTechs,
        ]);
    }

    public function events(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('viewAny', TradeAppointment::class);

        $tz = $tenant->timezone ?? config('app.timezone');
        $start = $request->query('start');
        $end = $request->query('end');

        if (!$start || !$end) {
            return response()->json([]);
        }

        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);

        $query = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->with(['job.client'])
            ->withCount('assignments');

        $techId = (int) $request->query('tech', 0);
        if ($techId) {
            $query->whereHas('assignments', function ($assignments) use ($techId, $tenant) {
                $assignments->where('tenant_id', $tenant->id)
                    ->where('user_id', $techId);
            });
        }

        $events = $query->get()->map(function (TradeAppointment $appointment) use ($tenant, $tz) {
            $jobSummary = $appointment->job?->summary ?? 'Job';
            $clientName =
                trim(
                    ($appointment->job?->client?->firstName ?? '') .
                        ' ' .
                        ($appointment->job?->client?->lastName ?? ''),
                ) ?: null;
            $title = $clientName ? $jobSummary . ' · ' . $clientName : $jobSummary;
            $techCount = (int) ($appointment->assignments_count ?? 0);
            $unassigned = $techCount === 0;

            return [
                'id' => $appointment->id,
                'title' => $title,
                'start' => $appointment->start_at?->timezone($tz)->toIso8601String(),
                'end' => $appointment->end_at?->timezone($tz)->toIso8601String(),
                'url' => route('tenant.trades.schedule.show', [
                    'tenant' => $tenant->id,
                    'appointment' => $appointment->id,
                ]),
                'extendedProps' => [
                    'status' => $appointment->status,
                    'techCount' => $techCount,
                    'unassigned' => $unassigned,
                ],
            ];
        });

        return response()->json($events);
    }

    public function ptoEvents(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('viewAny', TradeAppointment::class);

        $tz = $tenant->timezone ?? config('app.timezone');
        $start = $request->query('start');
        $end = $request->query('end');

        if (!$start || !$end) {
            return response()->json([]);
        }

        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);

        $query = TradePtoRequest::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endAt->toDateString())
            ->where('end_date', '>=', $startAt->toDateString())
            ->with(['user', 'type']);

        $techId = (int) $request->query('tech', 0);
        if ($techId) {
            $query->where('user_id', $techId);
        }

        $events = $query->get()->map(function (TradePtoRequest $requestItem) use ($tenant, $tz) {
            $userName = $requestItem->user?->name
                ?? trim(($requestItem->user?->first_name ?? '') . ' ' . ($requestItem->user?->last_name ?? ''))
                ?? $requestItem->user?->username
                ?? 'Team member';
            $typeLabel = $requestItem->type?->name ?? 'PTO';
            $start = $requestItem->start_date?->startOfDay()->timezone($tz);
            $end = $requestItem->end_date?->addDay()->startOfDay()->timezone($tz);

            return [
                'id' => $requestItem->id,
                'title' => 'PTO — ' . $userName . ' · ' . $typeLabel,
                'start' => $start?->toIso8601String(),
                'end' => $end?->toIso8601String(),
                'allDay' => true,
                'url' => route('tenant.trades.settings.index', ['tenant' => $tenant->id]),
                'extendedProps' => [
                    'status' => $requestItem->status,
                    'techName' => $userName,
                    'type' => $typeLabel,
                ],
            ];
        });

        return response()->json($events);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeAppointment::class);

        $data = $this->validateData($request, $tenant);

        $appointment = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $data['trade_job_id'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncAssignments($appointment, $tenant->id, $data['tech_ids'] ?? []);

        $warnings = $this->overlapWarnings($tenant->id, $appointment->start_at, $appointment->end_at, $data['tech_ids'] ?? [], $appointment->id);
        $suggestedWarning = $this->suggestedTechWarning($tenant->id, $data['trade_job_id'], $data['tech_ids'] ?? []);
        $availabilityWarnings = $this->availabilityWarnings(
            $tenant,
            $data['tech_ids'] ?? [],
            $appointment->start_at,
            $appointment->end_at
        );

        $redirect = redirect()
            ->route('tenant.trades.schedule.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Appointment created.')
            ->with('overlap_warnings', $warnings);
        if ($suggestedWarning) {
            $redirect->with('suggested_tech_warning', $suggestedWarning);
        }
        if (!empty($availabilityWarnings)) {
            $redirect->with('availability_warnings', $availabilityWarnings);
        }

        return $redirect;
    }

    public function show(Tenant $tenant, TradeAppointment $appointment)
    {
        $this->authorize('view', $appointment);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->load([
            'job.client',
            'job.company',
            'job.serviceLocation',
            'assignments.user',
            'fieldNotes' => fn($q) => $q->latest(),
            'fieldNotes.user',
            'fieldPhotos' => fn($q) => $q->latest(),
            'fieldPhotos.user',
        ]);

        return view('trades.schedule.show', [
            'tenant' => $tenant,
            'appointment' => $appointment,
        ]);
    }

    public function edit(Tenant $tenant, TradeAppointment $appointment)
    {
        $this->authorize('update', $appointment);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->load(['assignments', 'job.serviceLocation']);
        $tz = $tenant->timezone ?? config('app.timezone');
        $defaultStartAt = $appointment->start_at?->timezone($tz);
        $defaultEndAt = $appointment->end_at?->timezone($tz);
        [$availabilityStart, $availabilityEnd] = $this->resolveAvailabilityWindow(
            $tenant,
            request(),
            $defaultStartAt,
            $defaultEndAt
        );
        $techs = $this->techUsers($tenant);
        [$availability, $suggestedTechs] = $this->buildAvailabilityData(
            $tenant,
            $techs,
            $availabilityStart,
            $availabilityEnd
        );

        return view('trades.schedule.edit', [
            'tenant' => $tenant,
            'appointment' => $appointment,
            'jobs' => TradeJob::where('tenant_id', $tenant->id)
                ->with('serviceLocation')
                ->orderBy('summary')
                ->get(),
            'techs' => $techs,
            'assignedTechIds' => $appointment->assignments->pluck('user_id')->all(),
            'availability' => $availability,
            'suggestedTechs' => $suggestedTechs,
        ]);
    }

    public function update(Request $request, Tenant $tenant, TradeAppointment $appointment)
    {
        $this->authorize('update', $appointment);
        $this->abortIfWrongTenant($tenant, $appointment);

        $originalStart = $appointment->start_at?->copy();

        $data = $this->validateData($request, $tenant, $appointment);

        $appointment->update([
            'trade_job_id' => $data['trade_job_id'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($originalStart && $appointment->start_at && $originalStart->ne($appointment->start_at)) {
            $appointment->reminders()->delete();
        }

        $this->syncAssignments($appointment, $tenant->id, $data['tech_ids'] ?? []);

        $warnings = $this->overlapWarnings($tenant->id, $appointment->start_at, $appointment->end_at, $data['tech_ids'] ?? [], $appointment->id);
        $availabilityWarnings = $this->availabilityWarnings(
            $tenant,
            $data['tech_ids'] ?? [],
            $appointment->start_at,
            $appointment->end_at
        );

        return redirect()
            ->route('tenant.trades.schedule.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Appointment updated.')
            ->with('overlap_warnings', $warnings)
            ->with('availability_warnings', $availabilityWarnings);
    }

    public function destroy(Tenant $tenant, TradeAppointment $appointment)
    {
        $this->authorize('delete', $appointment);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->delete();

        return redirect()
            ->route('tenant.trades.schedule.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Appointment deleted.');
    }

    public function updateAssignment(Request $request, Tenant $tenant, TradeAppointment $appointment, AppointmentAssignment $assignment)
    {
        $this->authorize('update', $appointment);
        $this->abortIfWrongTenant($tenant, $appointment);

        if ((int) $assignment->appointment_id !== (int) $appointment->id || (int) $assignment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'presence_status' => ['required', Rule::in(['assigned', 'on_site', 'done'])],
        ]);

        $assignment->presence_status = $data['presence_status'];
        if ($data['presence_status'] === 'on_site' && !$assignment->on_site_at) {
            $assignment->on_site_at = now();
        }
        if ($data['presence_status'] === 'done' && !$assignment->done_at) {
            $assignment->done_at = now();
        }
        $assignment->save();

        $hasOpenTimers = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->where('appointment_id', $appointment->id)
            ->whereNull('ended_at')
            ->exists();

        $allDone = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->where('appointment_id', $appointment->id)
            ->where('presence_status', '!=', 'done')
            ->doesntExist();

        if (!$hasOpenTimers && $allDone && $appointment->status !== 'completed') {
            $appointment->status = 'completed';
            $appointment->updated_at = now();
            $appointment->save();
        }

        return redirect()
            ->route('tenant.trades.schedule.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Presence updated.');
    }

    protected function validateData(Request $request, Tenant $tenant, ?TradeAppointment $appointment = null): array
    {
        $data = $request->validate([
            'trade_job_id' => [
                'required',
                Rule::exists('trade_jobs', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'status' => 'required|string|max:40',
            'notes' => 'nullable|string',
            'tech_ids' => 'nullable|array',
            'tech_ids.*' => [
                Rule::exists('users', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
        ]);

        $tz = $tenant->timezone ?? config('app.timezone');
        $data['start_at'] = Carbon::parse($data['start_at'], $tz)->timezone('UTC');
        $data['end_at'] = Carbon::parse($data['end_at'], $tz)->timezone('UTC');

        $job = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->with('serviceLocation')
            ->find($data['trade_job_id']);
        $addressLine1 = trim((string) ($job?->serviceLocation?->address_line1 ?? ''));
        if ($addressLine1 === '') {
            throw ValidationException::withMessages([
                'trade_job_id' => 'Add a service address to this job before scheduling.',
            ]);
        }

        return $data;
    }

    protected function syncAssignments(TradeAppointment $appointment, int $tenantId, array $techIds): void
    {
        $techIds = array_values(array_unique(array_filter($techIds)));
        $existing = $appointment->assignments()->get()->keyBy('user_id');

        $appointment->assignments()->whereNotIn('user_id', $techIds)->delete();

        foreach ($techIds as $userId) {
            $assignment = $existing->get($userId);
            if (!$assignment) {
                AppointmentAssignment::create([
                    'tenant_id' => $tenantId,
                    'appointment_id' => $appointment->id,
                    'user_id' => $userId,
                    'presence_status' => 'assigned',
                ]);
            }
        }
    }

    protected function overlapWarnings(int $tenantId, $startAt, $endAt, array $techIds, ?int $excludeAppointmentId = null): array
    {
        $techIds = array_values(array_unique(array_filter($techIds)));
        if (empty($techIds)) {
            return [];
        }

        $query = AppointmentAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('user_id', $techIds)
            ->whereHas('appointment', function ($q) use ($startAt, $endAt, $excludeAppointmentId) {
                $q->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
                if ($excludeAppointmentId) {
                    $q->where('id', '!=', $excludeAppointmentId);
                }
            })
            ->with(['appointment.job', 'user']);

        $warnings = [];
        foreach ($query->get() as $assignment) {
            $userName = $assignment->user?->name
                ?? trim(($assignment->user?->first_name ?? '') . ' ' . ($assignment->user?->last_name ?? ''))
                ?? 'Team member';
            $warnings[] = [
                'user' => $userName,
                'job' => $assignment->appointment?->job?->summary ?? 'Another appointment',
                'start' => optional($assignment->appointment?->start_at)->toDateTimeString(),
                'end' => optional($assignment->appointment?->end_at)->toDateTimeString(),
            ];
        }

        return $warnings;
    }

    protected function listAppointmentsQuery(Tenant $tenant): Builder
    {
        return TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'job.client',
                'job.serviceLocation',
                'job.jobTemplate',
                'assignments.user',
            ])
            ->withCount('assignments');
    }

    protected function applyListFilters(Builder $query, Tenant $tenant, string $tz): array
    {
        $filters = [
            'q' => trim((string) request()->query('q', '')),
            'from' => request()->query('from', ''),
            'to' => request()->query('to', ''),
            'status' => request()->query('status', ''),
            'tech' => request()->query('tech', ''),
            'issues' => request()->query('issues', ''),
            'sort' => request()->query('sort', 'soonest'),
        ];

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['tech'] !== '') {
            $techId = (int) $filters['tech'];
            if ($techId) {
                $query->whereHas('assignments', fn(Builder $assignments) => $assignments->where('user_id', $techId));
            }
        }

        $from = $filters['from'] ? Carbon::parse($filters['from'], $tz)->startOfDay()->timezone('UTC') : null;
        $to = $filters['to'] ? Carbon::parse($filters['to'], $tz)->endOfDay()->timezone('UTC') : null;

        if ($from && $to) {
            $query->whereBetween('start_at', [$from, $to]);
        } elseif ($from) {
            $query->where('start_at', '>=', $from);
        } elseif ($to) {
            $query->where('start_at', '<=', $to);
        }

        if ($filters['q'] !== '') {
            $search = '%' . $filters['q'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('job', function (Builder $job) use ($search) {
                    $job->where('summary', 'like', $search)
                        ->orWhereHas('client', function (Builder $client) use ($search) {
                            $client->where('firstName', 'like', $search)
                                ->orWhere('lastName', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        })
                        ->orWhereHas('serviceLocation', function (Builder $location) use ($search) {
                            $location->where('address_line1', 'like', $search)
                                ->orWhere('address_line2', 'like', $search)
                                ->orWhere('city', 'like', $search)
                                ->orWhere('state', 'like', $search)
                                ->orWhere('postal_code', 'like', $search);
                        });
                })->orWhereHas('assignments.user', function (Builder $user) use ($search) {
                    $user->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('username', 'like', $search);
                });
            });
        }

        return [$query, $filters];
    }

    protected function decorateListAppointments(Collection $appointments, Tenant $tenant, string $tz): void
    {
        if ($appointments->isEmpty()) {
            return;
        }

        $conflicts = $this->detectConflicts($appointments, $tenant, $tz);
        $now = Carbon::now($tz);

        foreach ($appointments as $appointment) {
            $techNames = $appointment->assignments
                ->pluck('user')
                ->map(fn($user) => $this->formatTechDisplayName($user))
                ->filter()
                ->values();
            $techCount = $techNames->count();

            $suggestedTechs = $appointment->job?->jobTemplate?->suggested_tech_count;
            $isUnassigned = $techCount === 0;
            $isUnderstaffed = $suggestedTechs !== null && $techCount < $suggestedTechs;

            $startAt = $appointment->start_at?->timezone($tz);
            $isLate = $appointment->status === 'scheduled' && $startAt && $startAt->lt($now);

            $conflictTechs = $conflicts[$appointment->id]['techs'] ?? [];
            $hasConflict = !empty($conflictTechs);

            $issues = [];
            if ($isUnassigned) {
                $issues[] = ['label' => 'Unassigned', 'tone' => 'warning'];
            }
            if ($isUnderstaffed) {
                $issues[] = ['label' => 'Understaffed', 'tone' => 'warning'];
            }
            if ($hasConflict) {
                $issues[] = [
                    'label' => 'Conflict',
                    'tone' => 'danger',
                    'detail' => implode(', ', $conflictTechs),
                ];
            }
            if ($isLate) {
                $issues[] = ['label' => 'Late', 'tone' => 'danger'];
            }

            $rowClass = '';
            if ($hasConflict) {
                $rowClass = 'bg-rose-50/70';
            } elseif ($isLate) {
                $rowClass = 'bg-amber-50/70';
            } elseif ($isUnassigned || $isUnderstaffed) {
                $rowClass = 'bg-yellow-50/60';
            }

            $addressText = null;
            $location = $appointment->job?->serviceLocation;
            if ($location) {
                $addressParts = array_filter([
                    $location->address_line1,
                    $location->address_line2,
                    trim(
                        ($location->city ?? '') .
                            ($location->state ? ', ' . $location->state : '') .
                            ($location->postal_code ? ' ' . $location->postal_code : ''),
                    ),
                ]);
                $addressText = $addressParts ? implode(', ', $addressParts) : null;
            }

            $appointment->tech_names = $techNames->all();
            $appointment->tech_count = $techCount;
            $appointment->tech_label = $techCount ? $techNames->implode(', ') : 'Unassigned';
            $appointment->issues = $issues;
            $appointment->has_issues = !empty($issues);
            $appointment->issue_row_class = $rowClass;
            $appointment->conflict_tech_names = $conflictTechs;
            $appointment->address_text = $addressText;
            $appointment->is_unassigned = $isUnassigned;
            $appointment->is_understaffed = $isUnderstaffed;
            $appointment->is_late = $isLate;
            $appointment->has_conflict = $hasConflict;
        }
    }

    protected function detectConflicts(Collection $appointments, Tenant $tenant, string $tz): array
    {
        $techIds = $appointments->flatMap(fn(TradeAppointment $appointment) => $appointment->assignments->pluck('user_id'))
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (empty($techIds)) {
            return [];
        }

        $minStart = $appointments->pluck('start_at')->filter()->min();
        $maxEnd = $appointments->pluck('end_at')->filter()->max();
        if (!$minStart || !$maxEnd) {
            return [];
        }

        $assignments = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('user_id', $techIds)
            ->whereHas('appointment', function (Builder $builder) use ($minStart, $maxEnd) {
                $builder->where('start_at', '<', $maxEnd)
                    ->where('end_at', '>', $minStart);
            })
            ->with(['appointment', 'user'])
            ->get();

        $techAppointments = [];
        $techNames = [];
        foreach ($assignments as $assignment) {
            $appointment = $assignment->appointment;
            if (!$appointment || !$appointment->start_at || !$appointment->end_at) {
                continue;
            }

            $day = $appointment->start_at->copy()->timezone($tz)->toDateString();
            $techAppointments[$assignment->user_id][] = [
                'appointment_id' => $appointment->id,
                'start_at' => $appointment->start_at,
                'end_at' => $appointment->end_at,
                'day' => $day,
            ];
            if (!isset($techNames[$assignment->user_id])) {
                $techNames[$assignment->user_id] = $this->formatTechDisplayName($assignment->user);
            }
        }

        $conflicts = [];
        foreach ($appointments as $appointment) {
            $conflictTechs = [];
            if (!$appointment->start_at || !$appointment->end_at) {
                $conflicts[$appointment->id] = ['techs' => []];
                continue;
            }
            $day = $appointment->start_at->copy()->timezone($tz)->toDateString();

            foreach ($appointment->assignments as $assignment) {
                $userId = $assignment->user_id;
                if (empty($techAppointments[$userId])) {
                    continue;
                }
                foreach ($techAppointments[$userId] as $other) {
                    if ($other['appointment_id'] === $appointment->id) {
                        continue;
                    }
                    if ($other['day'] !== $day) {
                        continue;
                    }
                    if ($other['start_at'] < $appointment->end_at && $other['end_at'] > $appointment->start_at) {
                        $conflictTechs[] = $techNames[$userId] ?? 'Tech';
                        break;
                    }
                }
            }

            $conflicts[$appointment->id] = [
                'techs' => array_values(array_unique($conflictTechs)),
            ];
        }

        return $conflicts;
    }

    protected function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $results = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($results, $items->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    protected function statusOptions(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'in_progress' => 'In progress',
            'done' => 'Done',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
        ];
    }

    protected function formatTechDisplayName(?User $user): string
    {
        if (!$user) {
            return 'Tech';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return 'Tech #' . ($user->id ?? 'Unknown');
    }

    protected function suggestedTechWarning(int $tenantId, int $jobId, array $techIds): ?string
    {
        $techIds = array_values(array_unique(array_filter($techIds)));
        $job = TradeJob::where('tenant_id', $tenantId)
            ->with('jobTemplate')
            ->find($jobId);

        $suggested = (int) ($job?->jobTemplate?->suggested_tech_count ?? 0);
        if ($suggested > 0 && count($techIds) < $suggested) {
            return "Suggested {$suggested} tech(s) for this job. You assigned " . count($techIds) . ".";
        }

        return null;
    }

    /**
     * @return array<int, array{user_id:int, name:string, reasons:array<int,string>}>
     */
    protected function availabilityWarnings(Tenant $tenant, array $techIds, $startAt, $endAt): array
    {
        $techIds = array_values(array_unique(array_filter($techIds)));
        if (empty($techIds) || !$startAt || !$endAt) {
            return [];
        }

        $tz = $tenant->timezone ?? config('app.timezone');
        $start = Carbon::parse($startAt)->timezone($tz);
        $end = Carbon::parse($endAt)->timezone($tz);

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $techIds)
            ->get();

        $service = app(AvailabilityService::class);
        $availability = $service->availabilityForUsers($tenant, $users, $start, $end);

        $warnings = [];
        foreach ($users as $user) {
            $result = $availability[$user->id] ?? null;
            if ($result && !$result->available) {
                $warnings[] = [
                    'user_id' => $user->id,
                    'name' => $this->formatTechDisplayName($user),
                    'reasons' => $result->reasons,
                ];
            }
        }

        return $warnings;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveAvailabilityWindow(Tenant $tenant, Request $request, ?Carbon $defaultStart, ?Carbon $defaultEnd): array
    {
        $tz = $tenant->timezone ?? config('app.timezone');
        $startInput = $request->old('start_at');
        $endInput = $request->old('end_at');

        $start = $startInput
            ? Carbon::createFromFormat('Y-m-d\\TH:i', $startInput, $tz)
            : $defaultStart;
        $end = $endInput
            ? Carbon::createFromFormat('Y-m-d\\TH:i', $endInput, $tz)
            : $defaultEnd;

        return [$start, $end];
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\User> $techs
     * @return array{0: array<int, array{available: bool, reasons: array<int, string>}>, 1: array<int, string>}
     */
    protected function buildAvailabilityData(Tenant $tenant, Collection $techs, ?Carbon $start, ?Carbon $end): array
    {
        if (!$start || !$end || $techs->isEmpty()) {
            return [[], []];
        }

        $service = app(AvailabilityService::class);
        $availabilityResults = $service->availabilityForUsers($tenant, $techs, $start, $end);
        $availability = [];
        $suggestedTechs = [];

        foreach ($techs as $tech) {
            $result = $availabilityResults[$tech->id] ?? new \App\Services\Trades\AvailabilityResult(false, ['Outside hours']);
            $availability[$tech->id] = [
                'available' => $result->available,
                'reasons' => $result->reasons,
            ];
            if ($result->available) {
                $suggestedTechs[] = $this->formatTechDisplayName($tech);
            }
        }

        return [$availability, $suggestedTechs];
    }

    protected function techUsers(Tenant $tenant)
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', '!=', 'client')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'username', 'email', 'role']);
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeAppointment $appointment): void
    {
        if ((int) $appointment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
