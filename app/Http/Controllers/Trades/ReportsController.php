<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAssignment;
use App\Models\Tenant;
use App\Models\TechShift;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeJobItem;
use App\Models\TradeJobTimer;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Tenant $tenant)
    {
        $tenantId = $tenant->id;

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $appointmentsThisWeek = \App\Models\TradeAppointment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->count();

        $unscheduledJobs = \App\Models\TradeJob::query()
            ->where('tenant_id', $tenantId)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', now()))
            ->count();

        $activeTechsToday = \App\Models\AppointmentAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('on_site_at')
            ->whereBetween('on_site_at', [$todayStart, $todayEnd])
            ->distinct('user_id')
            ->count('user_id');

        return view('trades.reports.index', [
            'tenant' => $tenant,
            'appointmentsThisWeek' => $appointmentsThisWeek,
            'unscheduledJobs' => $unscheduledJobs,
            'activeTechsToday' => $activeTechsToday,
        ]);
    }
    /**
     * Schedule ops report: appointments + completion
     */
    public function schedule(Request $request, Tenant $tenant)
    {
        [$from, $to] = $this->parseDateRange($request);

        $base = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$from, $to]);

        $totals = [
            'appointments_total' => (clone $base)->count(),
            'appointments_completed' => (clone $base)->where('status', 'completed')->count(),
            'appointments_canceled' => (clone $base)->where('status', 'canceled')->count(),
        ];

        // Appointments per day (simple)
        $perDay = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$from, $to])
            ->selectRaw('DATE(start_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Upcoming (next 10)
        $upcoming = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('start_at', '>=', now())
            ->with(['job'])
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        return view('trades.reports.schedule', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'totals' => $totals,
            'perDay' => $perDay,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Jobs ops report: open/closed, unscheduled backlog
     */
    public function jobs(Request $request, Tenant $tenant)
    {
        [$from, $to] = $this->parseDateRange($request);

        $jobsCreated = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Status breakdown
        $statusBreakdown = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        // Type breakdown (service vs project)
        $typeBreakdown = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        // Unscheduled jobs = no future trade_appointments
        $now = Carbon::now();
        $unscheduledCount = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', $now))
            ->count();

        // Show a small list
        $unscheduled = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', $now))
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        $completedStatuses = ['completed', 'closed'];
        $completedJobs = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', $completedStatuses)
            ->whereBetween('updated_at', [$from, $to]);

        $completedCount = (clone $completedJobs)->count();
        $completedWithCounts = (clone $completedJobs)->withCount('appointments')->get();
        $firstTimeFixCount = $completedWithCounts->filter(fn($job) => (int) $job->appointments_count <= 1)->count();
        $firstTimeFixRate = $completedCount > 0 ? round(($firstTimeFixCount / $completedCount) * 100, 1) : 0;

        $completedJobIds = $completedWithCounts->pluck('id');
        $durationTotals = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('trade_job_id', $completedJobIds)
            ->whereNotNull('ended_at')
            ->selectRaw('trade_job_id, SUM(TIMESTAMPDIFF(SECOND, started_at, ended_at)) as total_seconds')
            ->groupBy('trade_job_id')
            ->get();

        $avgDurationSeconds = (int) ($durationTotals->avg('total_seconds') ?? 0);

        $jobItemTotal = TradeJobItem::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('trade_job_id', $completedJobIds)
            ->sum('line_total');

        $invoiceTotal = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('trade_job_id', $completedJobIds)
            ->get()
            ->sum(fn($invoice) => (float) ($invoice->total_amount ?? $invoice->total ?? $invoice->subtotal ?? 0));

        $unbilledTotal = max(0, (float) $jobItemTotal - (float) $invoiceTotal);

        return view('trades.reports.jobs', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'jobsCreated' => $jobsCreated,
            'statusBreakdown' => $statusBreakdown,
            'typeBreakdown' => $typeBreakdown,
            'unscheduledCount' => $unscheduledCount,
            'unscheduled' => $unscheduled,
            'firstTimeFixRate' => $firstTimeFixRate,
            'avgDurationSeconds' => $avgDurationSeconds,
            'jobItemTotal' => $jobItemTotal,
            'invoiceTotal' => $invoiceTotal,
            'unbilledTotal' => $unbilledTotal,
        ]);
    }

    /**
     * Tech report: on-site counts + timer totals (MVP)
     */
    public function tech(Request $request, Tenant $tenant)
    {
        [$from, $to] = $this->parseDateRange($request);

        if ($request->string('export')->lower()->toString() === 'payroll') {
            return $this->exportPayroll($tenant, $from, $to);
        }

        $shiftTotals = $this->collectShiftTotals($tenant, $from, $to);

        // On-site entries by user (presence_status)
        $presence = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('user_id, COUNT(*) as assignments')
            ->groupBy('user_id')
            ->orderByDesc('assignments')
            ->with('user')
            ->get();

        // Timer totals by user (if you have duration; otherwise compute from started/ended)
        $timerTotals = TradeJobTimer::query()
            ->where('trade_job_timers.tenant_id', $tenant->id)
            ->whereBetween('trade_job_timers.started_at', [$from, $to])
            ->leftJoin('appointment_assignments as aa', function ($join) use ($tenant) {
                $join->on('aa.appointment_id', '=', 'trade_job_timers.appointment_id')
                    ->on('aa.user_id', '=', 'trade_job_timers.user_id')
                    ->where('aa.tenant_id', '=', $tenant->id);
            })
            ->selectRaw("
        trade_job_timers.user_id,
        SUM(
            CASE
                WHEN trade_job_timers.ended_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, trade_job_timers.ended_at)
                WHEN aa.done_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, aa.done_at)
                ELSE 0
            END
        ) as completed_seconds,
        SUM(
            CASE
                WHEN trade_job_timers.ended_at IS NULL
                    AND aa.done_at IS NULL
                    AND (aa.presence_status = 'on_site' OR aa.presence_status IS NULL)
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, NOW())
                ELSE 0
            END
        ) as running_seconds,
        SUM(
            CASE
                WHEN trade_job_timers.ended_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, trade_job_timers.ended_at)
                WHEN aa.done_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, aa.done_at)
                WHEN (aa.presence_status = 'on_site' OR aa.presence_status IS NULL)
                    THEN TIMESTAMPDIFF(SECOND, trade_job_timers.started_at, NOW())
                ELSE 0
            END
        ) as total_seconds
    ")
            ->groupBy('trade_job_timers.user_id')
            ->orderByDesc('total_seconds')
            ->with('user')
            ->get();

        $appointments = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$from, $to])
            ->with('assignments')
            ->get();

        $scheduledSeconds = [];
        foreach ($appointments as $appointment) {
            $start = Carbon::parse($appointment->start_at);
            $end = Carbon::parse($appointment->end_at);
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }
            $seconds = $end->diffInSeconds($start);
            foreach ($appointment->assignments as $assignment) {
                $uid = (int) $assignment->user_id;
                $scheduledSeconds[$uid] = ($scheduledSeconds[$uid] ?? 0) + $seconds;
            }
        }

        $timerTotalsByUser = $timerTotals->keyBy('user_id');
        $utilization = [];
        $userIds = collect([
            ...array_keys($shiftTotals['work_seconds']),
            ...array_keys($shiftTotals['pto_seconds']),
            ...array_keys($scheduledSeconds),
            ...$timerTotalsByUser->keys()->all(),
        ])->unique()->values();

        foreach ($userIds as $userId) {
            $workSeconds = (int) ($timerTotalsByUser[$userId]->total_seconds ?? 0);
            $ptoSeconds = (int) ($shiftTotals['pto_seconds'][$userId] ?? 0);
            $scheduled = (int) ($scheduledSeconds[$userId] ?? 0);
            $overtime = $this->computeOvertimeSeconds(
                $shiftTotals['daily_work_seconds'][$userId] ?? [],
                $shiftTotals['weekly_work_seconds'][$userId] ?? [],
                $tenant
            );

            $utilization[$userId] = [
                'user' => $shiftTotals['users'][$userId] ?? $timerTotalsByUser[$userId]->user ?? null,
                'scheduled_seconds' => $scheduled,
                'worked_seconds' => $workSeconds,
                'pto_seconds' => $ptoSeconds,
                'overtime_seconds' => $overtime,
            ];
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $techUsers = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', ['tech', 'lead_tech'])
            ->get();

        $todayShifts = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->where('clock_in_at', '<=', $todayEnd)
            ->where(function ($query) use ($todayStart) {
                $query->whereNull('clock_out_at')
                    ->orWhere('clock_out_at', '>=', $todayStart);
            })
            ->orderByDesc('clock_in_at')
            ->get()
            ->groupBy('user_id')
            ->map->first();

        $todayTimers = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->where('started_at', '<=', $todayEnd)
            ->where(function ($query) use ($todayStart) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $todayStart);
            })
            ->orderBy('started_at')
            ->get()
            ->groupBy('user_id');

        $techOverview = $techUsers->map(function ($user) use ($todayShifts, $todayTimers) {
            $shift = $todayShifts[$user->id] ?? null;
            $timers = $todayTimers[$user->id] ?? collect();
            $lastTimer = $timers->last();

            return [
                'user' => $user,
                'shift' => $shift,
                'timers' => $timers,
                'last_timer_start' => $lastTimer?->started_at,
                'last_timer_end' => $lastTimer?->ended_at,
            ];
        })->values();

        $clockStatus = [];
        if ($userIds->isNotEmpty()) {
            $latestShifts = TechShift::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('user_id', $userIds->all())
                ->orderByDesc('clock_in_at')
                ->get()
                ->groupBy('user_id')
                ->map->first();

            foreach ($userIds as $userId) {
                $clockStatus[] = [
                    'user' => $shiftTotals['users'][$userId] ?? $timerTotalsByUser[$userId]->user ?? null,
                    'shift' => $latestShifts[$userId] ?? null,
                ];
            }
        }

        return view('trades.reports.tech', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'presence' => $presence,
            'timerTotals' => $timerTotals,
            'utilization' => $utilization,
            'clockStatus' => $clockStatus,
            'techOverview' => $techOverview,
        ]);
    }

    public function forceClockOut(Request $request, Tenant $tenant, User $user)
    {
        if ((int) $user->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        $actor = auth()->user();
        $role = strtolower((string) ($actor?->role ?? ''));
        if (!in_array($role, ['admin', 'dispatcher', 'super_admin', 'superadmin', 'provider'], true)) {
            abort(403);
        }

        $now = now();

        TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNull('clock_out_at')
            ->update(['clock_out_at' => $now]);

        TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => $now]);

        AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('presence_status', 'on_site')
            ->update(['presence_status' => 'done', 'done_at' => $now]);

        return back()->with('success', 'Tech clocked out.');
    }

    protected function exportPayroll(Tenant $tenant, Carbon $from, Carbon $to)
    {
        $shiftTotals = $this->collectShiftTotals($tenant, $from, $to);
        $byUser = [];
        foreach ($shiftTotals['work_seconds'] as $userId => $seconds) {
            $byUser[$userId] = [
                'user' => $shiftTotals['users'][$userId] ?? null,
                'work_seconds' => $seconds,
                'pto_seconds' => (int) ($shiftTotals['pto_seconds'][$userId] ?? 0),
                'daily_work_seconds' => $shiftTotals['daily_work_seconds'][$userId] ?? [],
                'weekly_work_seconds' => $shiftTotals['weekly_work_seconds'][$userId] ?? [],
            ];
        }
        foreach ($shiftTotals['pto_seconds'] as $userId => $seconds) {
            if (isset($byUser[$userId])) {
                continue;
            }
            $byUser[$userId] = [
                'user' => $shiftTotals['users'][$userId] ?? null,
                'work_seconds' => 0,
                'pto_seconds' => $seconds,
                'daily_work_seconds' => [],
                'weekly_work_seconds' => [],
            ];
        }

        $rows = [];
        foreach ($byUser as $data) {
            $overtimeSeconds = $this->computeOvertimeSeconds(
                $data['daily_work_seconds'],
                $data['weekly_work_seconds'],
                $tenant
            );

            $user = $data['user'];
            $name = $user?->name ?: trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));
            $rows[] = [
                $name ?: 'Tech',
                $user?->email ?? '',
                round($data['work_seconds'] / 3600, 2),
                round($data['pto_seconds'] / 3600, 2),
                round(($data['work_seconds'] + $data['pto_seconds']) / 3600, 2),
                round($overtimeSeconds / 3600, 2),
            ];
        }

        $filename = 'trades-payroll-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Trades payroll export']);
            fputcsv($handle, ['From', $from->toDateString(), 'To', $to->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, ['Tech', 'Email', 'Work Hours', 'PTO Hours', 'Total Hours', 'Overtime Hours']);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function parseDateRange(Request $request): array
    {
        // Default: last 7 days
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(6)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        // Ensure ordering
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function collectShiftTotals(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        $shifts = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('clock_in_at')
            ->whereNotNull('clock_out_at')
            ->where('clock_in_at', '<=', $to)
            ->where('clock_out_at', '>=', $from)
            ->with('user')
            ->orderBy('clock_in_at')
            ->get();

        $workSeconds = [];
        $ptoSeconds = [];
        $dailyWorkSeconds = [];
        $weeklyWorkSeconds = [];
        $users = [];

        foreach ($shifts as $shift) {
            $userId = (int) $shift->user_id;
            $users[$userId] = $shift->user;

            $start = Carbon::parse($shift->clock_in_at);
            $end = Carbon::parse($shift->clock_out_at);
            $rangeStart = $start->greaterThan($from) ? $start : $from;
            $rangeEnd = $end->lessThan($to) ? $end : $to;

            if ($rangeEnd->lessThanOrEqualTo($rangeStart)) {
                continue;
            }

            $seconds = $rangeEnd->diffInSeconds($rangeStart);
            $shiftType = $shift->shift_type ?: 'work';

            if ($shiftType === 'pto') {
                $ptoSeconds[$userId] = ($ptoSeconds[$userId] ?? 0) + $seconds;
                continue;
            }

            $workSeconds[$userId] = ($workSeconds[$userId] ?? 0) + $seconds;

            $dayKey = $rangeStart->copy()->startOfDay()->toDateString();
            $dailyWorkSeconds[$userId][$dayKey] = ($dailyWorkSeconds[$userId][$dayKey] ?? 0) + $seconds;

            $weekKey = $rangeStart->copy()->startOfWeek()->toDateString();
            $weeklyWorkSeconds[$userId][$weekKey] = ($weeklyWorkSeconds[$userId][$weekKey] ?? 0) + $seconds;
        }

        return [
            'users' => $users,
            'work_seconds' => $workSeconds,
            'pto_seconds' => $ptoSeconds,
            'daily_work_seconds' => $dailyWorkSeconds,
            'weekly_work_seconds' => $weeklyWorkSeconds,
        ];
    }

    private function computeOvertimeSeconds(array $dailyWorkSeconds, array $weeklyWorkSeconds, Tenant $tenant): int
    {
        if (!$tenant->overtime_enabled) {
            return 0;
        }

        $dailyLimit = (float) ($tenant->overtime_daily_hours ?? 0);
        $weeklyLimit = (float) ($tenant->overtime_weekly_hours ?? 0);

        $dailyOvertime = 0;
        if ($dailyLimit > 0) {
            foreach ($dailyWorkSeconds as $seconds) {
                $dailyOvertime += max(0, $seconds - ($dailyLimit * 3600));
            }
        }

        $weeklyOvertime = 0;
        if ($weeklyLimit > 0) {
            foreach ($weeklyWorkSeconds as $seconds) {
                $weeklyOvertime += max(0, $seconds - ($weeklyLimit * 3600));
            }
        }

        // Avoid double-counting when both daily + weekly thresholds exist.
        return (int) max($dailyOvertime, $weeklyOvertime);
    }
}
