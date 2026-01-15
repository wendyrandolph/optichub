<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TradeHoliday;
use App\Models\TradePtoRequest;
use App\Models\TradePtoType;
use App\Models\User;
use App\Services\Trades\TradePtoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use DateTimeZone;

class SettingsController extends Controller
{
    public function index(Tenant $tenant)
    {
        $users = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'dispatcher', 'lead_tech', 'lead tech', 'lead-tech'])
            ->orderBy('first_name')
            ->get();

        $ptoTypes = TradePtoType::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $pendingRequests = TradePtoRequest::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->with(['user', 'type'])
            ->orderBy('requested_at')
            ->get();

        $holidays = collect();
        if (Schema::hasTable('trade_holidays')) {
            $holidays = TradeHoliday::where('tenant_id', $tenant->id)
                ->orderBy('holiday_date')
                ->get();
        }

        return view('trades.settings.index', [
            'tenant' => $tenant,
            'users' => $users,
            'ptoTypes' => $ptoTypes,
            'pendingRequests' => $pendingRequests,
            'holidays' => $holidays,
        ]);
    }

    public function updateApprovers(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'pto_approver_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'pto_backup_approver_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
        ]);

        $tenant->update($data);

        return back()->with('success', 'PTO approvers updated.');
    }

    public function updateRecurring(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'trades_recurring_enabled' => ['nullable', 'boolean'],
        ]);

        $tenant->update([
            'trades_recurring_enabled' => (bool) ($data['trades_recurring_enabled'] ?? false),
        ]);

        return back()->with('success', 'Service plan settings updated.');
    }

    public function updateWarranty(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'trades_warranty_scope' => ['required', Rule::in(['job', 'line_item'])],
        ]);

        $tenant->update([
            'trades_warranty_scope' => $data['trades_warranty_scope'],
        ]);

        return back()->with('success', 'Warranty settings updated.');
    }

    public function updateWorkType(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'trades_work_type' => ['required', Rule::in(['residential', 'commercial', 'both'])],
        ]);

        $tenant->update([
            'trades_work_type' => $data['trades_work_type'],
        ]);

        return back()->with('success', 'Work type updated.');
    }

    public function updateOvertime(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'overtime_enabled' => ['nullable', 'boolean'],
            'overtime_daily_hours' => ['nullable', 'numeric', 'min:0'],
            'overtime_weekly_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenant->update([
            'overtime_enabled' => (bool) ($data['overtime_enabled'] ?? false),
            'overtime_daily_hours' => $data['overtime_daily_hours'] ?? null,
            'overtime_weekly_hours' => $data['overtime_weekly_hours'] ?? null,
        ]);

        return back()->with('success', 'Overtime settings updated.');
    }

    public function updateTimezone(Request $request, Tenant $tenant)
    {
        $timezones = DateTimeZone::listIdentifiers();
        $data = $request->validate([
            'timezone' => ['nullable', 'string', Rule::in($timezones)],
        ]);

        $tenant->update([
            'timezone' => $data['timezone'] ?: null,
        ]);

        return back()->with('success', 'Timezone updated.');
    }

    public function storeType(Request $request, Tenant $tenant)
    {
        $data = $this->validateType($request, $tenant);
        $data['tenant_id'] = $tenant->id;

        TradePtoType::create($data);

        return back()->with('success', 'PTO type added.');
    }

    public function updateType(Request $request, Tenant $tenant, TradePtoType $type)
    {
        $this->abortIfWrongTenant($tenant, $type);
        $data = $this->validateType($request, $tenant, $type->id);
        $type->update($data);

        return back()->with('success', 'PTO type updated.');
    }

    public function toggleType(Request $request, Tenant $tenant, TradePtoType $type)
    {
        $this->abortIfWrongTenant($tenant, $type);
        $type->is_active = !$type->is_active;
        $type->save();

        return back()->with('success', 'PTO type updated.');
    }

    public function approve(Request $request, Tenant $tenant, TradePtoRequest $ptoRequest, TradePtoService $service)
    {
        $this->abortIfWrongTenant($tenant, $ptoRequest);
        $this->ensureApprover($tenant);

        $service->approveRequest($ptoRequest, auth()->id());

        return back()->with('success', 'PTO request approved.');
    }

    public function deny(Request $request, Tenant $tenant, TradePtoRequest $ptoRequest, TradePtoService $service)
    {
        $this->abortIfWrongTenant($tenant, $ptoRequest);
        $this->ensureApprover($tenant);

        $service->denyRequest($ptoRequest, auth()->id());

        return back()->with('success', 'PTO request denied.');
    }

    protected function validateType(Request $request, Tenant $tenant, ?int $typeId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('trade_pto_types', 'code')
                    ->where(fn($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($typeId),
            ],
            'accrual_rate_hours' => ['required', 'numeric', 'min:0'],
            'accrual_period' => ['required', Rule::in(['month', 'pay_period'])],
            'tenure_months' => ['nullable', 'integer', 'min:1'],
            'tenure_accrual_rate_hours' => ['nullable', 'numeric', 'min:0'],
            'carryover_enabled' => ['nullable', 'boolean'],
            'carryover_cap_hours' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    public function storeHoliday(Request $request, Tenant $tenant)
    {
        if (!Schema::hasTable('trade_holidays')) {
            return back()->with('error', 'Holiday settings are not available yet. Run the latest migrations.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'holiday_date' => ['required', 'date'],
            'is_paid' => ['nullable', 'boolean'],
        ]);

        TradeHoliday::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'holiday_date' => $data['holiday_date'],
            'is_paid' => (bool) ($data['is_paid'] ?? true),
        ]);

        return back()->with('success', 'Holiday added.');
    }

    public function deleteHoliday(Request $request, Tenant $tenant, TradeHoliday $holiday)
    {
        if (!Schema::hasTable('trade_holidays')) {
            return back()->with('error', 'Holiday settings are not available yet. Run the latest migrations.');
        }

        $this->abortIfWrongTenant($tenant, $holiday);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    protected function abortIfWrongTenant(Tenant $tenant, $model): void
    {
        if ((int) $model->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    protected function ensureApprover(Tenant $tenant): void
    {
        $userId = auth()->id();
        $allowed = [
            (int) $tenant->pto_approver_id,
            (int) $tenant->pto_backup_approver_id,
        ];

        if (!in_array((int) $userId, $allowed, true)) {
            abort(403);
        }
    }
}
