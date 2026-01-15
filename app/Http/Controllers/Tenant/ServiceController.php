<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ClientCompany;
use App\Models\Service;
use App\Models\ServiceLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ServiceController extends Controller
{
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'type' => 'required|string|in:domain,hosting,maintenance,retainer,other',
            'name' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,paused,canceled',
            'handling' => 'nullable|string|in:client,agency,agency_reimburse',
            'billing_cycle' => 'nullable|string|in:monthly,annual,one_time',
            'renewal_date' => 'nullable|date',
            'cost_amount' => 'nullable|numeric',
            'cost_currency' => 'nullable|string|max:10',
            'reminder_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
            'start_date' => 'nullable|date',
            // optional type-specific
            'domain_name' => 'nullable|string|max:255',
            'registrar' => 'nullable|string|max:255',
            'auto_renew' => 'sometimes|boolean',
            'host_plan' => 'nullable|string|max:255',
            'maintenance_cadence' => 'nullable|string|max:255',
            'retainer_type' => 'nullable|string|in:hours,currency',
            'retainer_amount' => 'nullable|numeric',
            'rollover_allowed' => 'sometimes|boolean',
            'rollover_cap' => 'nullable|numeric',
            'reset_day' => 'nullable|integer|min:1|max:31',
        ]);
    }

    protected function validateLogData(Request $request): array
    {
        return $request->validate([
            'occurred_at' => 'nullable|date',
            'log_type' => 'nullable|string|in:note,maintenance,retainer_usage,renewal',
            'hours' => 'nullable|numeric',
            'amount' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);
    }

    public function index(Request $request, Tenant $tenant, ClientCompany $company)
    {
        $this->authorize('view', $tenant);
        if ($company->tenant_id !== $tenant->id) {
            abort(404);
        }

        $q = trim($request->get('q', ''));
        $statusFilter = $request->get('status', '');
        $typeFilter = $request->get('type', '');
        $timing = $request->get('timing', '');
        $tz = $tenant->timezone ?? config('app.timezone');
        $now = Carbon::now($tz);

        $query = $company->services()
            ->orderByRaw("CASE WHEN renewal_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('renewal_date')
            ->orderByDesc('created_at')
            ->withSum('logs as used_amount', 'amount')
            ->withSum('logs as used_hours', 'hours');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('provider', 'like', "%{$q}%");
            });
        }

        if ($statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($statusFilter === 'inactive') {
            $query->whereIn('status', ['paused', 'canceled']);
        }

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        if ($timing === 'overdue') {
            $query->whereNotNull('renewal_date')->where('renewal_date', '<', $now);
        } elseif ($timing === 'soon') {
            $query->whereNotNull('renewal_date')
                ->whereBetween('renewal_date', [$now, (clone $now)->addDays(30)]);
        }

        $services = $query->paginate(20)->appends([
            'q' => $q,
            'status' => $statusFilter,
            'type' => $typeFilter,
            'timing' => $timing,
        ]);

        $services->getCollection()->transform(function ($service) use ($tz, $now) {
            $renewal = $service->renewal_date ? Carbon::parse($service->renewal_date, $tz) : null;
            $days = $renewal ? $now->diffInDays($renewal, false) : null;
            $service->days_until_renewal = $days;
            $service->is_overdue = !is_null($days) && $days < 0;
            return $service;
        });

        $allServices = $company->services()->get(['id', 'status', 'renewal_date', 'name', 'type']);
        $activeCount = $allServices->where('status', 'active')->count();
        $dueSoonCount = $allServices->filter(function ($s) use ($tz, $now) {
            if (!$s->renewal_date) {
                return false;
            }
            $days = $now->diffInDays(Carbon::parse($s->renewal_date, $tz), false);
            return $days >= 0 && $days <= 30;
        })->count();
        $overdueCount = $allServices->filter(function ($s) use ($tz, $now) {
            if (!$s->renewal_date) {
                return false;
            }
            return $now->diffInDays(Carbon::parse($s->renewal_date, $tz), false) < 0;
        })->count();
        $nextRenewal = $allServices->filter(function ($s) use ($tz, $now) {
            return $s->renewal_date && $s->status === 'active' && $now->diffInDays(Carbon::parse($s->renewal_date, $tz), false) >= 0;
        })->sortBy('renewal_date')->first();

        return view('tenant.services.index', [
            'tenant' => $tenant,
            'company' => $company,
            'services' => $services,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'timing' => $timing,
            'summary' => [
                'active' => $activeCount,
                'dueSoon' => $dueSoonCount,
                'overdue' => $overdueCount,
                'nextRenewal' => $nextRenewal,
            ],
        ]);
    }

    public function store(Request $request, Tenant $tenant, ClientCompany $company)
    {
        $this->authorize('view', $tenant);
        if ($company->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $this->validateData($request);
        $data['tenant_id'] = $tenant->id;
        $data['company_id'] = $company->id;

        $meta = $data['meta'] ?? [];
        $meta = array_merge($meta, [
            'domain_name' => $data['domain_name'] ?? null,
            'registrar' => $data['registrar'] ?? null,
            'auto_renew' => $request->boolean('auto_renew'),
            'host_plan' => $data['host_plan'] ?? null,
            'maintenance_cadence' => $data['maintenance_cadence'] ?? null,
            'retainer_type' => $data['retainer_type'] ?? null,
            'retainer_amount' => $data['retainer_amount'] ?? null,
            'rollover_allowed' => $request->boolean('rollover_allowed'),
            'rollover_cap' => $data['rollover_cap'] ?? null,
            'reset_day' => $data['reset_day'] ?? null,
        ]);

        $data['meta'] = $meta;

        Service::create($data);

        return back()->with('success', 'Service added.');
    }

    public function show(Tenant $tenant, Service $service)
    {
        $this->authorize('view', $tenant);
        if ($service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $service->load(['company', 'logs' => function ($q) {
            $q->orderByDesc('occurred_at')->orderByDesc('created_at');
        }]);

        $usedAmount = $service->logs->where('log_type', 'retainer_usage')->sum('amount');
        $usedHours = $service->logs->where('log_type', 'retainer_usage')->sum('hours');
        $meta = $service->meta ?? [];
        $balance = null;
        if ($service->type === 'retainer' && isset($meta['retainer_amount'])) {
            $retainerType = $meta['retainer_type'] ?? null;
            if ($retainerType === 'hours') {
                $balance = ($meta['retainer_amount'] ?? 0) - $usedHours;
            } else {
                $balance = ($meta['retainer_amount'] ?? 0) - $usedAmount;
            }
        }

        // warnings
        $warnings = [];
        if ($service->renewal_date) {
            $tz = $tenant->timezone ?? config('app.timezone');
            $today = now($tz)->startOfDay();
            $renewal = Carbon::parse($service->renewal_date, $tz)->startOfDay();
            $daysDiff = $today->diffInDays($renewal, false);

            if ($daysDiff > 0) {
                $warnings[] = 'Renews in ' . $daysDiff . ' days';
            } elseif ($daysDiff === 0) {
                // same day: show hours/minutes left until end of day
                $nowTz = now($tz);
                $endOfDay = $renewal->copy()->endOfDay();
                $diffMinutes = $nowTz->diffInMinutes($endOfDay, false);
                $hours = intdiv(max($diffMinutes, 0), 60);
                $mins = max($diffMinutes, 0) % 60;
                $timeStr = [];
                if ($hours > 0) {
                    $timeStr[] = "{$hours}h";
                }
                if ($mins > 0) {
                    $timeStr[] = "{$mins}m";
                }
                $label = count($timeStr) ? implode(' ', $timeStr) : 'less than 1m';
                $warnings[] = "Renews in {$label}";
            } else {
                $warnings[] = 'Renewal overdue by ' . abs($daysDiff) . ' days';
            }
        }

        return view('tenant.services.show', [
            'tenant' => $tenant,
            'service' => $service,
            'balance' => $balance,
            'warnings' => $warnings,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Service $service)
    {
        $this->authorize('view', $tenant);
        if ($service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $this->validateData($request);
        $meta = $data['meta'] ?? [];
        $meta = array_merge($meta, [
            'domain_name' => $data['domain_name'] ?? null,
            'registrar' => $data['registrar'] ?? null,
            'auto_renew' => $request->boolean('auto_renew'),
            'host_plan' => $data['host_plan'] ?? null,
            'maintenance_cadence' => $data['maintenance_cadence'] ?? null,
            'retainer_type' => $data['retainer_type'] ?? null,
            'retainer_amount' => $data['retainer_amount'] ?? null,
            'rollover_allowed' => $request->boolean('rollover_allowed'),
            'rollover_cap' => $data['rollover_cap'] ?? null,
            'reset_day' => $data['reset_day'] ?? null,
        ]);
        $data['meta'] = $meta;

        $service->update($data);

        return back()->with('success', 'Service updated.');
    }

    public function storeLog(Request $request, Tenant $tenant, Service $service)
    {
        $this->authorize('view', $tenant);
        if ($service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $this->validateLogData($request);
        $data['company_service_id'] = $service->id;
        $data['tenant_id'] = $tenant->id;

        ServiceLog::create($data);

        return back()->with('success', 'Log entry added.');
    }

    public function destroyLog(Tenant $tenant, ServiceLog $log)
    {
        $this->authorize('view', $tenant);
        if (! $log->service || $log->service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $log->delete();

        return back()->with('success', 'Log entry removed.');
    }

    public function updateLog(Request $request, Tenant $tenant, ServiceLog $log)
    {
        $this->authorize('view', $tenant);
        if (! $log->service || $log->service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $this->validateLogData($request);
        $log->update($data);

        return back()->with('success', 'Log entry updated.');
    }

    public function destroy(Tenant $tenant, Service $service)
    {
        $this->authorize('view', $tenant);
        if ($service->tenant_id !== $tenant->id) {
            abort(404);
        }

        $service->delete();

        return back()->with('success', 'Service removed.');
    }
}
