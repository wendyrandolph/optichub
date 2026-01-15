<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TradeBusinessHour;
use App\Models\TradeWorkSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkSchedulesController extends Controller
{
    public function index(Tenant $tenant)
    {
        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', '!=', 'client')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $hours = TradeBusinessHour::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('day_of_week');

        $weekHours = collect(range(0, 6))
            ->mapWithKeys(function ($day) use ($hours) {
                $row = $hours->get($day);
                return [$day => [
                    'day_of_week' => $day,
                    'start_time' => $row?->start_time,
                    'end_time' => $row?->end_time,
                    'is_closed' => (bool) ($row?->is_closed ?? true),
                ]];
            })
            ->all();

        $schedules = TradeWorkSchedule::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('user_id');

        return view('trades.settings.work-schedules.index', [
            'tenant' => $tenant,
            'users' => $users,
            'weekHours' => $weekHours,
            'schedules' => $schedules,
        ]);
    }

    public function updateCompany(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'hours.*.end_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        foreach (range(0, 6) as $day) {
            $row = $data['hours'][$day] ?? [];
            $isClosed = !empty($row['is_closed']);
            $start = $row['start_time'] ?? null;
            $end = $row['end_time'] ?? null;

            if ($isClosed) {
                $start = null;
                $end = null;
            }

            TradeBusinessHour::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day_of_week' => $day],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_closed' => $isClosed,
                ]
            );
        }

        return redirect()
            ->route('tenant.trades.settings.time.work-schedules', ['tenant' => $tenant->id])
            ->with('success_message', 'Company hours updated.');
    }

    public function edit(Tenant $tenant, User $user)
    {
        $this->abortIfWrongTenant($tenant, $user);

        $schedule = TradeWorkSchedule::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('blocks')
            ->first();

        $blocks = [];
        foreach ([0, 1] as $weekIndex) {
            $blocks[$weekIndex] = collect(range(0, 6))
                ->mapWithKeys(function ($day) use ($schedule, $weekIndex) {
                    $block = $schedule?->blocks
                        ->first(fn($item) => (int) $item->week_index === $weekIndex && (int) $item->day_of_week === $day);
                    return [$day => [
                        'start_time' => $block?->start_time,
                        'end_time' => $block?->end_time,
                    ]];
                })
                ->all();
        }

        return view('trades.settings.work-schedules.edit', [
            'tenant' => $tenant,
            'user' => $user,
            'schedule' => $schedule,
            'blocks' => $blocks,
        ]);
    }

    public function updateUser(Request $request, Tenant $tenant, User $user)
    {
        $this->abortIfWrongTenant($tenant, $user);

        $validator = Validator::make($request->all(), [
            'cadence' => ['required', 'in:weekly,biweekly'],
            'timezone' => ['nullable', 'string'],
            'starts_on' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'blocks' => ['nullable', 'array'],
            'blocks.*.*.start_time' => ['nullable', 'date_format:H:i'],
            'blocks.*.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $blocks = $request->input('blocks', []);
            foreach ($blocks as $weekIndex => $days) {
                foreach ($days as $day => $block) {
                    $start = $block['start_time'] ?? null;
                    $end = $block['end_time'] ?? null;
                    if (($start && !$end) || ($end && !$start)) {
                        $validator->errors()->add("blocks.{$weekIndex}.{$day}", 'Start and end time are required together.');
                        continue;
                    }
                    if ($start && $end && strtotime($start) >= strtotime($end)) {
                        $validator->errors()->add("blocks.{$weekIndex}.{$day}", 'End time must be after start time.');
                    }
                }
            }
        });

        $data = $validator->validate();

        DB::transaction(function () use ($tenant, $user, $data) {
            $schedule = TradeWorkSchedule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                [
                    'cadence' => $data['cadence'],
                    'timezone' => $data['timezone'] ?? null,
                    'starts_on' => $data['starts_on'] ?? null,
                    'is_active' => !empty($data['is_active']),
                ]
            );

            $schedule->blocks()->delete();

            $blocks = $data['blocks'] ?? [];
            foreach ($blocks as $weekIndex => $days) {
                foreach ($days as $day => $block) {
                    $start = $block['start_time'] ?? null;
                    $end = $block['end_time'] ?? null;
                    if (!$start || !$end) {
                        continue;
                    }
                    $schedule->blocks()->create([
                        'tenant_id' => $tenant->id,
                        'week_index' => (int) $weekIndex,
                        'day_of_week' => (int) $day,
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
                }
            }
        });

        return redirect()
            ->route('tenant.trades.settings.time.work-schedules.edit', ['tenant' => $tenant->id, 'user' => $user->id])
            ->with('success_message', 'Schedule updated.');
    }

    protected function abortIfWrongTenant(Tenant $tenant, User $user): void
    {
        if ((int) $tenant->id !== (int) $user->tenant_id) {
            abort(404);
        }
    }
}
