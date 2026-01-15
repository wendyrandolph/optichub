<?php

namespace App\Services\Trades;

use App\Models\AppointmentAssignment;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeBusinessHour;
use App\Models\TradePtoRequest;
use App\Models\TradeWorkSchedule;
use App\Models\TradeWorkScheduleBlock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\User> $users
     * @return array<int, AvailabilityResult>
     */
    public function availabilityForUsers(Tenant $tenant, Collection $users, Carbon $start, Carbon $end): array
    {
        $userIds = $users->pluck('id')->filter()->values()->all();
        if (empty($userIds)) {
            return [];
        }

        $startUtc = $start->copy()->timezone('UTC');
        $endUtc = $end->copy()->timezone('UTC');

        $schedules = TradeWorkSchedule::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with(['blocks', 'tenant'])
            ->get()
            ->keyBy('user_id');

        $assignments = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('user_id', $userIds)
            ->whereHas('appointment', function ($query) use ($startUtc, $endUtc) {
                $query->where('start_at', '<', $endUtc)
                    ->where('end_at', '>', $startUtc);
            })
            ->with('appointment')
            ->get();

        $bookedByUser = [];
        foreach ($assignments as $assignment) {
            $appointment = $assignment->appointment;
            if (!$appointment || !$appointment->start_at || !$appointment->end_at) {
                continue;
            }
            $bookedByUser[$assignment->user_id][] = [
                'start' => Carbon::parse($appointment->start_at),
                'end' => Carbon::parse($appointment->end_at),
            ];
        }

        $dayStart = $start->copy()->startOfDay();
        $dayEnd = $end->copy()->endOfDay();

        $ptoByUser = [];
        $ptoRequests = TradePtoRequest::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->where('start_date', '<=', $dayEnd)
            ->where('end_date', '>=', $dayStart)
            ->get(['user_id', 'start_date', 'end_date']);

        foreach ($ptoRequests as $request) {
            $ptoByUser[$request->user_id][] = [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end' => Carbon::parse($request->end_date)->endOfDay(),
            ];
        }

        $results = [];
        foreach ($users as $user) {
            $schedule = $schedules->get($user->id);
            $working = $schedule ? $this->workingBlocksForSchedule($schedule, $start) : [];
            $booked = $bookedByUser[$user->id] ?? [];
            $pto = $ptoByUser[$user->id] ?? [];

            $result = new AvailabilityResult(true);

            if (!$this->withinWorkingHours($start, $end, $working)) {
                $result->available = false;
                $result->addReason('Outside hours');
            }

            foreach ($booked as $block) {
                if ($block['start']->lt($end) && $block['end']->gt($start)) {
                    $result->available = false;
                    $result->addReason('Booked');
                    break;
                }
            }

            foreach ($pto as $block) {
                if ($block['start']->lt($end) && $block['end']->gt($start)) {
                    $result->available = false;
                    $result->addReason('PTO');
                    break;
                }
            }

            $results[$user->id] = $result;
        }

        return $results;
    }
    /**
     * @return array<int, array{start: Carbon, end: Carbon, is_closed: bool}>
     */
    public function companyHoursForDate(Tenant $tenant, Carbon $date): array
    {
        $day = (int) $date->dayOfWeek;
        $tz = $this->resolveTenantTimezone($tenant);
        $base = $date->copy()->timezone($tz);

        return TradeBusinessHour::query()
            ->where('tenant_id', $tenant->id)
            ->where('day_of_week', $day)
            ->get()
            ->map(function (TradeBusinessHour $hour) use ($tz, $base) {
                $start = $hour->start_time
                    ? Carbon::createFromFormat('H:i:s', $hour->start_time, $tz)
                        ->setDate($base->year, $base->month, $base->day)
                    : null;
                $end = $hour->end_time
                    ? Carbon::createFromFormat('H:i:s', $hour->end_time, $tz)
                        ->setDate($base->year, $base->month, $base->day)
                    : null;

                return [
                    'start' => $start,
                    'end' => $end,
                    'is_closed' => $hour->is_closed,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function userWorkingBlocks(User $user, Carbon $date): array
    {
        $schedule = TradeWorkSchedule::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['blocks', 'tenant'])
            ->first();

        if (!$schedule) {
            return [];
        }

        $tz = $schedule->timezone ?: $this->resolveTenantTimezone($schedule->tenant ?? null);
        $targetDay = (int) $date->copy()->timezone($tz)->dayOfWeek;

        $weekIndex = 0;
        if ($schedule->cadence === 'biweekly') {
            $startsOn = $schedule->starts_on ? Carbon::parse($schedule->starts_on, $tz) : $date->copy()->timezone($tz);
            $weekDiff = $startsOn->copy()->startOfWeek()->diffInWeeks($date->copy()->timezone($tz)->startOfWeek());
            $weekIndex = $weekDiff % 2;
        }

        return $schedule->blocks
            ->filter(fn(TradeWorkScheduleBlock $block) => $block->week_index === $weekIndex && $block->day_of_week === $targetDay)
            ->values()
            ->map(function (TradeWorkScheduleBlock $block) use ($date, $tz) {
                $base = $date->copy()->timezone($tz);
                $start = Carbon::createFromFormat('H:i:s', $block->start_time, $tz)
                    ->setDate($base->year, $base->month, $base->day);
                $end = Carbon::createFromFormat('H:i:s', $block->end_time, $tz)
                    ->setDate($base->year, $base->month, $base->day);

                return [
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function userBookedBlocks(User $user, Carbon $date): array
    {
        $dayStart = $date->copy()->startOfDay()->timezone('UTC');
        $dayEnd = $date->copy()->endOfDay()->timezone('UTC');

        $appointments = TradeAppointment::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereHas('assignments', fn($query) => $query->where('user_id', $user->id))
            ->where('start_at', '<', $dayEnd)
            ->where('end_at', '>', $dayStart)
            ->get(['start_at', 'end_at']);

        return $appointments
            ->map(function (TradeAppointment $appointment) {
                return [
                    'start' => Carbon::parse($appointment->start_at),
                    'end' => Carbon::parse($appointment->end_at),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function userPtoBlocks(User $user, Carbon $date): array
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $requests = TradePtoRequest::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $dayEnd)
            ->where('end_date', '>=', $dayStart)
            ->get(['start_date', 'end_date']);

        return $requests
            ->map(function (TradePtoRequest $request) use ($date) {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();
                return [
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->all();
    }

    public function isUserAvailable(User $user, Carbon $start, Carbon $end): AvailabilityResult
    {
        $date = $start->copy();

        $working = $this->userWorkingBlocks($user, $date);
        $booked = $this->userBookedBlocks($user, $date);
        $pto = $this->userPtoBlocks($user, $date);

        $result = new AvailabilityResult(true);

        if (!$this->withinWorkingHours($start, $end, $working)) {
            $result->available = false;
            $result->addReason('Outside hours');
        }

        foreach ($booked as $block) {
            if ($block['start']->lt($end) && $block['end']->gt($start)) {
                $result->available = false;
                $result->addReason('Booked');
                break;
            }
        }

        foreach ($pto as $block) {
            if ($block['start']->lt($end) && $block['end']->gt($start)) {
                $result->available = false;
                $result->addReason('PTO');
                break;
            }
        }

        return $result;
    }

    /**
     * @param array<int, array{start: Carbon, end: Carbon}> $workingBlocks
     */
    protected function withinWorkingHours(Carbon $start, Carbon $end, array $workingBlocks): bool
    {
        if (empty($workingBlocks)) {
            return false;
        }

        foreach ($workingBlocks as $block) {
            if ($start->greaterThanOrEqualTo($block['start']) && $end->lessThanOrEqualTo($block['end'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    protected function workingBlocksForSchedule(TradeWorkSchedule $schedule, Carbon $date): array
    {
        $tz = $schedule->timezone ?: $this->resolveTenantTimezone($schedule->tenant ?? null);
        $targetDay = (int) $date->copy()->timezone($tz)->dayOfWeek;

        $weekIndex = 0;
        if ($schedule->cadence === 'biweekly') {
            $startsOn = $schedule->starts_on ? Carbon::parse($schedule->starts_on, $tz) : $date->copy()->timezone($tz);
            $weekDiff = $startsOn->copy()->startOfWeek()->diffInWeeks($date->copy()->timezone($tz)->startOfWeek());
            $weekIndex = $weekDiff % 2;
        }

        return $schedule->blocks
            ->filter(fn(TradeWorkScheduleBlock $block) => $block->week_index === $weekIndex && $block->day_of_week === $targetDay)
            ->values()
            ->map(function (TradeWorkScheduleBlock $block) use ($date, $tz) {
                $base = $date->copy()->timezone($tz);
                $start = Carbon::createFromFormat('H:i:s', $block->start_time, $tz)
                    ->setDate($base->year, $base->month, $base->day);
                $end = Carbon::createFromFormat('H:i:s', $block->end_time, $tz)
                    ->setDate($base->year, $base->month, $base->day);

                return [
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->all();
    }

    protected function resolveTenantTimezone(?Tenant $tenant): string
    {
        return $tenant?->timezone ?: config('app.timezone');
    }
}
