<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TradeHoliday;
use App\Models\TradePtoRequest;
use App\Models\TradePtoType;
use App\Models\User;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TimeController extends Controller
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
        $hasHolidays = Schema::hasTable('trade_holidays');
        if ($hasHolidays) {
            $holidays = TradeHoliday::where('tenant_id', $tenant->id)
                ->orderBy('holiday_date')
                ->get();
        }

        return view('trades.settings.time', [
            'tenant' => $tenant,
            'users' => $users,
            'ptoTypes' => $ptoTypes,
            'pendingRequests' => $pendingRequests,
            'holidays' => $holidays,
            'hasHolidays' => $hasHolidays,
            'timezoneOptions' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $timezones = DateTimeZone::listIdentifiers();
        $data = $request->validate([
            'timezone' => ['sometimes', 'nullable', 'string', Rule::in($timezones)],
            'overtime_enabled' => ['sometimes', 'nullable', 'boolean'],
            'overtime_daily_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'overtime_weekly_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pto_approver_id' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'pto_backup_approver_id' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
        ]);

        $updates = [];

        if ($request->has('timezone')) {
            $updates['timezone'] = $data['timezone'] ?: null;
        }

        if ($request->hasAny(['overtime_enabled', 'overtime_daily_hours', 'overtime_weekly_hours'])) {
            $updates['overtime_enabled'] = (bool) ($data['overtime_enabled'] ?? false);
            $updates['overtime_daily_hours'] = $data['overtime_daily_hours'] ?? null;
            $updates['overtime_weekly_hours'] = $data['overtime_weekly_hours'] ?? null;
        }

        if ($request->hasAny(['pto_approver_id', 'pto_backup_approver_id'])) {
            $updates['pto_approver_id'] = $data['pto_approver_id'] ?? null;
            $updates['pto_backup_approver_id'] = $data['pto_backup_approver_id'] ?? null;
        }

        if (!empty($updates)) {
            $tenant->update($updates);
        }

        return back()->with('success', 'Time settings updated.');
    }
}
