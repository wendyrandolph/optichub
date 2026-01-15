<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAssignment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Lead;
use App\Models\TechShift;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeJobTimer;
use App\Models\TradeQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PerformanceController extends Controller
{
    private const KPI_KEYS = [
        'leads',
        'sales',
        'capacity',
        'labor',
        'results',
        'cash',
        'unscheduled',
    ];

    public function index(Tenant $tenant, Request $request)
    {
        $range = $this->normalizeRange($request->string('range')->toString());
        [$from, $to] = $this->rangeToDates($range);
        [$prevFrom, $prevTo] = $this->previousRange($from, $to);

        $kpis = [
            'leads' => $this->kpiLeads($tenant, $from, $to, $prevFrom, $prevTo),
            'sales' => $this->kpiSales($tenant, $from, $to, $prevFrom, $prevTo),
            'capacity' => $this->kpiCapacity($tenant, $from, $to, $prevFrom, $prevTo),
            'labor' => $this->kpiLabor($tenant, $from, $to, $prevFrom, $prevTo),
            'results' => $this->kpiResults($tenant, $from, $to, $prevFrom, $prevTo),
            'cash' => $this->kpiCash($tenant, $from, $to, $prevFrom, $prevTo),
            'unscheduled' => $this->kpiUnscheduled($tenant, $from, $to, $prevFrom, $prevTo),
        ];

        return view('trades.performance.index', compact('tenant', 'range', 'from', 'to', 'kpis'));
    }

    public function show(Tenant $tenant, string $kpi, Request $request)
    {
        if (!in_array($kpi, self::KPI_KEYS, true)) {
            abort(404);
        }

        $range = $this->normalizeRange($request->string('range')->toString());
        [$from, $to] = $this->rangeToDates($range, $request);
        [$prevFrom, $prevTo] = $this->previousRange($from, $to);

        $summary = $this->index($tenant, new Request(['range' => $range]))->getData()['kpis'][$kpi] ?? null;
        $detail = $this->buildDetail($tenant, $kpi, $from, $to);

        return view('trades.performance.show', compact('tenant', 'kpi', 'range', 'from', 'to', 'prevFrom', 'prevTo', 'summary', 'detail'));
    }

    private function normalizeRange(?string $range): string
    {
        $range = $range ?: 'this_week';
        return in_array($range, ['this_week', 'this_month', 'last_30'], true) ? $range : 'this_week';
    }

    private function rangeToDates(string $range, ?Request $request = null): array
    {
        if ($request && $request->filled('from') && $request->filled('to')) {
            $from = $request->date('from')?->startOfDay();
            $to = $request->date('to')?->endOfDay();
            if ($from && $to) {
                if ($from->greaterThan($to)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }
                return [$from, $to];
            }
        }

        $now = now();
        if ($range === 'this_month') {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfDay()];
        }
        if ($range === 'last_30') {
            return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        }

        return [$now->copy()->startOfWeek(), $now->copy()->endOfDay()];
    }

    private function previousRange(Carbon $from, Carbon $to): array
    {
        $seconds = $to->diffInSeconds($from) + 1;
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($seconds - 1);

        return [$prevFrom, $prevTo];
    }

    private function formatDelta(?float $current, ?float $previous, string $mode = 'percent'): ?string
    {
        if ($current === null || $previous === null) {
            return null;
        }

        if ($mode === 'points') {
            $diff = $current - $previous;
            return sprintf('%+0.1f pts', $diff);
        }

        if ($previous == 0.0) {
            return null;
        }

        $pct = (($current - $previous) / abs($previous)) * 100;
        return sprintf('%+0.1f%%', $pct);
    }

    private function kpiLeads(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('leads')) {
            return $this->emptyKpi('Lead Flow', 'Requires leads table.');
        }

        $current = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $previous = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->count();
        $response = $this->leadResponseStats($tenant, $from, $to);
        $secondary = null;
        if ($response['available'] ?? false) {
            if ($response['median'] !== null) {
                $secondary = 'Median response: ' . $response['median'] . ' min';
            } elseif ($current > 0) {
                $secondary = 'Median response: —';
            }
        }

        return [
            'label' => 'Lead flow',
            'value' => $current,
            'delta' => $this->formatDelta($current, $previous),
            'secondary' => $secondary,
            'note' => $response['available'] && $current > 0 && $response['median'] === null
                ? 'No contacted leads in range.'
                : null,
        ];
    }

    private function kpiSales(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('trade_quotes')) {
            return $this->emptyKpi('Sales performance', 'Requires trades quotes.');
        }

        [$currentSent, $currentAccepted] = $this->quoteCounts($tenant, $from, $to);
        [$prevSent, $prevAccepted] = $this->quoteCounts($tenant, $prevFrom, $prevTo);

        $currentRate = $currentSent > 0 ? round(($currentAccepted / $currentSent) * 100, 1) : null;
        $prevRate = $prevSent > 0 ? round(($prevAccepted / $prevSent) * 100, 1) : null;

        return [
            'label' => 'Sales performance',
            'value' => $currentRate,
            'value_suffix' => '%',
            'delta' => $this->formatDelta($currentRate, $prevRate, 'points'),
            'note' => $currentSent === 0 ? 'No sent quotes in range.' : null,
        ];
    }

    private function kpiCapacity(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('trade_appointments')) {
            return $this->emptyKpi('Fulfillment capacity', 'Requires trade appointments.');
        }

        $currentScheduled = $this->appointmentSeconds($tenant, $from, $to);
        $currentAvailable = $this->shiftSeconds($tenant, $from, $to, false);

        $prevScheduled = $this->appointmentSeconds($tenant, $prevFrom, $prevTo);
        $prevAvailable = $this->shiftSeconds($tenant, $prevFrom, $prevTo, false);

        $currentRate = $currentAvailable > 0 ? round(($currentScheduled / $currentAvailable) * 100, 1) : null;
        $prevRate = $prevAvailable > 0 ? round(($prevScheduled / $prevAvailable) * 100, 1) : null;

        return [
            'label' => 'Fulfillment capacity',
            'value' => $currentRate,
            'value_suffix' => '%',
            'delta' => $this->formatDelta($currentRate, $prevRate, 'points'),
            'note' => $currentAvailable === 0 ? 'Requires tech shifts.' : null,
        ];
    }

    private function kpiLabor(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('trade_job_timers')) {
            return $this->emptyKpi('Labor efficiency', 'Requires job timers.');
        }

        $currentBillable = $this->timerSeconds($tenant, $from, $to);
        $currentPaid = $this->shiftSeconds($tenant, $from, $to, false);
        $prevBillable = $this->timerSeconds($tenant, $prevFrom, $prevTo);
        $prevPaid = $this->shiftSeconds($tenant, $prevFrom, $prevTo, false);

        $currentRate = $currentPaid > 0 ? round(($currentBillable / $currentPaid) * 100, 1) : null;
        $prevRate = $prevPaid > 0 ? round(($prevBillable / $prevPaid) * 100, 1) : null;
        $currentBillableHours = round($currentBillable / 3600, 1);
        $currentPaidHours = round($currentPaid / 3600, 1);

        return [
            'label' => 'Labor efficiency',
            'value' => [
                'billable_hours' => $currentBillableHours,
                'paid_hours' => $currentPaidHours,
            ],
            'value_format' => 'labor_hours',
            'secondary' => $currentRate !== null ? 'Efficiency: ' . $currentRate . '%' : null,
            'delta' => $this->formatDelta($currentRate, $prevRate, 'points'),
            'note' => $currentPaid === 0 ? 'Requires tech shifts.' : null,
        ];
    }

    private function kpiResults(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('trade_appointments')) {
            return $this->emptyKpi('Results', 'Requires trade appointments.');
        }

        [$currentCompleted, $currentOnTime] = $this->appointmentCompletionStats($tenant, $from, $to);
        [$prevCompleted, $prevOnTime] = $this->appointmentCompletionStats($tenant, $prevFrom, $prevTo);

        $currentRate = $currentCompleted > 0 ? round(($currentOnTime / $currentCompleted) * 100, 1) : null;
        $prevRate = $prevCompleted > 0 ? round(($prevOnTime / $prevCompleted) * 100, 1) : null;

        return [
            'label' => 'Results',
            'value' => $currentRate,
            'value_suffix' => '%',
            'delta' => $this->formatDelta($currentRate, $prevRate, 'points'),
            'note' => $currentCompleted === 0 ? 'No completed appointments.' : null,
        ];
    }

    private function kpiCash(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('invoices')) {
            return $this->emptyKpi('Cash flow', 'Requires invoices.');
        }

        $currentOutstanding = (float) Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('status', '!=', 'paid')->orWhereNull('status');
            })
            ->sum('balance_due');
        $previousOutstanding = (float) Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('status', '!=', 'paid')->orWhereNull('status');
            })
            ->whereBetween('issue_date', [$prevFrom, $prevTo])
            ->sum('balance_due');

        $avgDays = $this->avgDaysToPay($tenant, $from, $to);
        $prevAvgDays = $this->avgDaysToPay($tenant, $prevFrom, $prevTo);

        return [
            'label' => 'Cash flow',
            'value' => $currentOutstanding,
            'value_format' => 'money',
            'secondary' => $avgDays !== null ? 'Avg days to pay: ' . $avgDays : 'Avg days to pay: —',
            'delta' => $this->formatDelta($currentOutstanding, $previousOutstanding),
            'note' => null,
            'avg_days_delta' => $this->formatDelta($avgDays, $prevAvgDays, 'points'),
        ];
    }

    private function kpiUnscheduled(Tenant $tenant, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        if (!Schema::hasTable('trade_jobs')) {
            return $this->emptyKpi('Unscheduled jobs', 'Requires trade jobs.');
        }

        $current = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', now()))
            ->count();
        $previous = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', $prevFrom))
            ->count();

        return [
            'label' => 'Unscheduled jobs',
            'value' => $current,
            'delta' => $this->formatDelta($current, $previous),
            'note' => null,
        ];
    }

    private function emptyKpi(string $label, string $note): array
    {
        return [
            'label' => $label,
            'value' => null,
            'delta' => null,
            'note' => $note,
        ];
    }

    private function quoteCounts(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        $sent = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['sent', 'accepted'])
            ->count();
        $accepted = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'accepted')
            ->count();

        return [$sent, $accepted];
    }

    private function appointmentSeconds(Tenant $tenant, Carbon $from, Carbon $to): int
    {
        if (!Schema::hasTable('trade_appointments')) {
            return 0;
        }

        $appointments = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $to)
            ->where('end_at', '>=', $from)
            ->get(['start_at', 'end_at']);

        $total = 0;
        foreach ($appointments as $appointment) {
            $start = Carbon::parse($appointment->start_at);
            $end = Carbon::parse($appointment->end_at);
            $rangeStart = $start->greaterThan($from) ? $start : $from;
            $rangeEnd = $end->lessThan($to) ? $end : $to;
            if ($rangeEnd->greaterThan($rangeStart)) {
                $total += $rangeEnd->diffInSeconds($rangeStart);
            }
        }

        return $total;
    }

    private function shiftSeconds(Tenant $tenant, Carbon $from, Carbon $to, bool $includePto): int
    {
        if (!Schema::hasTable('tech_shifts')) {
            return 0;
        }

        $query = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('clock_in_at')
            ->whereNotNull('clock_out_at')
            ->where('clock_in_at', '<=', $to)
            ->where('clock_out_at', '>=', $from);

        if (!$includePto) {
            $query->where(function ($q) {
                $q->whereNull('shift_type')
                    ->orWhere('shift_type', '!=', 'pto');
            });
        }

        $shifts = $query->get(['clock_in_at', 'clock_out_at', 'shift_type']);
        $total = 0;

        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->clock_in_at);
            $end = Carbon::parse($shift->clock_out_at);
            $rangeStart = $start->greaterThan($from) ? $start : $from;
            $rangeEnd = $end->lessThan($to) ? $end : $to;
            if ($rangeEnd->greaterThan($rangeStart)) {
                $total += $rangeEnd->diffInSeconds($rangeStart);
            }
        }

        return $total;
    }

    private function timerSeconds(Tenant $tenant, Carbon $from, Carbon $to): int
    {
        if (!Schema::hasTable('trade_job_timers')) {
            return 0;
        }

        $timers = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '<=', $to)
            ->where('ended_at', '>=', $from)
            ->get(['started_at', 'ended_at']);

        $total = 0;
        foreach ($timers as $timer) {
            $start = Carbon::parse($timer->started_at);
            $end = Carbon::parse($timer->ended_at);
            $rangeStart = $start->greaterThan($from) ? $start : $from;
            $rangeEnd = $end->lessThan($to) ? $end : $to;
            if ($rangeEnd->greaterThan($rangeStart)) {
                $total += $rangeEnd->diffInSeconds($rangeStart);
            }
        }

        return $total;
    }

    private function appointmentCompletionStats(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        $appointments = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('end_at', [$from, $to])
            ->get(['end_at', 'updated_at']);

        $completed = $appointments->count();
        $onTime = $appointments->filter(function ($appointment) {
            if (!$appointment->end_at) {
                return true;
            }
            return $appointment->updated_at && $appointment->updated_at->lessThanOrEqualTo($appointment->end_at);
        })->count();

        return [$completed, $onTime];
    }

    private function avgDaysToPay(Tenant $tenant, Carbon $from, Carbon $to): ?float
    {
        if (!Schema::hasTable('invoice_payments')) {
            return null;
        }

        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereNotNull('issue_date')
            ->with(['payments' => function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to]);
            }])
            ->get();

        $days = [];
        foreach ($invoices as $invoice) {
            $paidAt = $invoice->payments->max('paid_at');
            if ($paidAt && $invoice->issue_date) {
                $days[] = Carbon::parse($paidAt)->diffInDays(Carbon::parse($invoice->issue_date));
            }
        }

        if (empty($days)) {
            return null;
        }

        return round(array_sum($days) / count($days), 1);
    }

    private function buildDetail(Tenant $tenant, string $kpi, Carbon $from, Carbon $to): array
    {
        return match ($kpi) {
            'leads' => $this->detailLeads($tenant, $from, $to),
            'sales' => $this->detailSales($tenant, $from, $to),
            'capacity' => $this->detailCapacity($tenant, $from, $to),
            'labor' => $this->detailLabor($tenant, $from, $to),
            'results' => $this->detailResults($tenant, $from, $to),
            'cash' => $this->detailCash($tenant, $from, $to),
            'unscheduled' => $this->detailUnscheduled($tenant),
            default => [],
        };
    }

    private function detailLeads(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('leads')) {
            return ['note' => 'Leads table not available.'];
        }

        $rows = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn($row) => ['day' => $row->day, 'count' => (int) $row->count])
            ->all();

        $sources = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($row) => ['source' => $row->source ?: 'Unknown', 'count' => (int) $row->count])
            ->all();

        $statusCounts = [];
        if (Schema::hasColumn('leads', 'status')) {
            $statusCounts = Lead::query()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->orderByDesc('count')
                ->get()
                ->map(fn($row) => ['status' => $row->status ?: 'unknown', 'count' => (int) $row->count])
                ->all();
        }

        return [
            'rows' => $rows,
            'sources' => $sources,
            'status_counts' => $statusCounts,
            'response' => $this->leadResponseStats($tenant, $from, $to),
            'attribution' => $this->leadAttribution($tenant, $from, $to),
        ];
    }

    private function leadResponseStats(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('leads') || !Schema::hasColumn('leads', 'first_contacted_at')) {
            return ['available' => false, 'note' => 'Response time requires first_contacted_at.'];
        }

        $leads = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('first_contacted_at')
            ->get(['created_at', 'first_contacted_at']);

        $durations = [];
        foreach ($leads as $lead) {
            if (!$lead->created_at || !$lead->first_contacted_at) {
                continue;
            }
            $minutes = Carbon::parse($lead->created_at)->diffInMinutes(Carbon::parse($lead->first_contacted_at));
            $durations[] = $minutes;
        }

        if (empty($durations)) {
            return [
                'available' => true,
                'median' => null,
                'p90' => null,
                'avg' => null,
                'count' => 0,
                'breaches' => 0,
                'threshold' => 60,
                'note' => 'No contacted leads in range.',
            ];
        }

        sort($durations);
        $median = $this->percentile($durations, 0.5);
        $p90 = $this->percentile($durations, 0.9);
        $avg = round(array_sum($durations) / count($durations), 1);
        $threshold = 60;
        $breaches = count(array_filter($durations, fn($value) => $value > $threshold));

        return [
            'available' => true,
            'median' => $median,
            'p90' => $p90,
            'avg' => $avg,
            'count' => count($durations),
            'breaches' => $breaches,
            'threshold' => $threshold,
            'note' => null,
        ];
    }

    private function percentile(array $values, float $percentile): int
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        $index = (int) ceil($percentile * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (int) $values[$index];
    }

    private function leadAttribution(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('leads')) {
            return [];
        }

        $leads = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->get(['source', 'source_detail']);

        $counts = [];
        foreach ($leads as $lead) {
            $detail = (array) ($lead->source_detail ?? []);
            $utm = $detail['utm_source'] ?? null;
            $referrer = $detail['referrer'] ?? null;
            $host = $referrer ? (parse_url($referrer, PHP_URL_HOST) ?: $referrer) : null;

            $label = $utm
                ? 'UTM: ' . $utm
                : ($host ?: ($lead->source ?: 'Unknown'));

            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);

        $rows = [];
        foreach (array_slice($counts, 0, 5, true) as $label => $count) {
            $rows[] = ['label' => $label, 'count' => $count];
        }

        return $rows;
    }

    private function detailSales(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('trade_quotes')) {
            return ['note' => 'Quotes table not available.'];
        }

        $rows = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE(created_at) as day, SUM(status in ('sent','accepted')) as sent_count, SUM(status = 'accepted') as accepted_count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(function ($row) {
                $sent = (int) $row->sent_count;
                $accepted = (int) $row->accepted_count;
                $rate = $sent > 0 ? round(($accepted / $sent) * 100, 1) : null;
                return [
                    'day' => $row->day,
                    'sent' => $sent,
                    'accepted' => $accepted,
                    'rate' => $rate,
                ];
            })
            ->all();

        return [
            'rows' => $rows,
        ];
    }

    private function detailCapacity(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('trade_appointments')) {
            return ['note' => 'Appointments table not available.'];
        }

        $days = [];
        $cursor = $from->copy();
        while ($cursor->lessThanOrEqualTo($to)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $scheduled = $this->appointmentSeconds($tenant, $dayStart, $dayEnd);
            $available = $this->shiftSeconds($tenant, $dayStart, $dayEnd, false);
            $rate = $available > 0 ? round(($scheduled / $available) * 100, 1) : null;
            $days[] = [
                'day' => $dayStart->toDateString(),
                'scheduled_hours' => round($scheduled / 3600, 1),
                'available_hours' => round($available / 3600, 1),
                'rate' => $rate,
            ];
            $cursor->addDay();
        }

        return ['rows' => $days];
    }

    private function detailLabor(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('trade_job_timers') || !Schema::hasTable('tech_shifts')) {
            return ['note' => 'Timers or shifts data not available.'];
        }

        $timerByUser = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '<=', $to)
            ->where('ended_at', '>=', $from)
            ->get(['user_id', 'started_at', 'ended_at'])
            ->groupBy('user_id')
            ->map(fn($items) => $items->sum(function ($timer) use ($from, $to) {
                $start = Carbon::parse($timer->started_at);
                $end = Carbon::parse($timer->ended_at);
                $rangeStart = $start->greaterThan($from) ? $start : $from;
                $rangeEnd = $end->lessThan($to) ? $end : $to;
                return $rangeEnd->greaterThan($rangeStart) ? $rangeEnd->diffInSeconds($rangeStart) : 0;
            }))
            ->all();

        $shiftByUser = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('clock_in_at')
            ->whereNotNull('clock_out_at')
            ->where('clock_in_at', '<=', $to)
            ->where('clock_out_at', '>=', $from)
            ->where(function ($q) {
                $q->whereNull('shift_type')->orWhere('shift_type', '!=', 'pto');
            })
            ->get(['user_id', 'clock_in_at', 'clock_out_at'])
            ->groupBy('user_id')
            ->map(fn($items) => $items->sum(function ($shift) use ($from, $to) {
                $start = Carbon::parse($shift->clock_in_at);
                $end = Carbon::parse($shift->clock_out_at);
                $rangeStart = $start->greaterThan($from) ? $start : $from;
                $rangeEnd = $end->lessThan($to) ? $end : $to;
                return $rangeEnd->greaterThan($rangeStart) ? $rangeEnd->diffInSeconds($rangeStart) : 0;
            }))
            ->all();

        $userIds = array_unique(array_merge(array_keys($timerByUser), array_keys($shiftByUser)));
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $rows = [];
        foreach ($userIds as $userId) {
            $billable = $timerByUser[$userId] ?? 0;
            $paid = $shiftByUser[$userId] ?? 0;
            $rate = $paid > 0 ? round(($billable / $paid) * 100, 1) : null;
            $rows[] = [
                'name' => $this->resolveTechName($users[$userId] ?? null),
                'billable_hours' => round($billable / 3600, 1),
                'paid_hours' => round($paid / 3600, 1),
                'rate' => $rate,
            ];
        }

        usort($rows, fn($a, $b) => ($b['billable_hours'] <=> $a['billable_hours']));

        return ['rows' => array_slice($rows, 0, 10)];
    }

    private function resolveTechName(?User $user): string
    {
        if (!$user) {
            return 'Tech';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return $name !== '' ? $name : ('Tech #' . ($user->id ?? ''));
    }

    private function detailResults(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('trade_appointments')) {
            return ['note' => 'Appointments table not available.'];
        }

        $rows = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->whereBetween('end_at', [$from, $to])
            ->selectRaw('DATE(end_at) as day, COUNT(*) as completed, SUM(updated_at <= end_at) as on_time')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(function ($row) {
                $completed = (int) $row->completed;
                $onTime = (int) $row->on_time;
                $rate = $completed > 0 ? round(($onTime / $completed) * 100, 1) : null;
                return [
                    'day' => $row->day,
                    'completed' => $completed,
                    'on_time' => $onTime,
                    'rate' => $rate,
                ];
            })
            ->all();

        return ['rows' => $rows];
    }

    private function detailCash(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('invoices')) {
            return ['note' => 'Invoices table not available.'];
        }

        $outstanding = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('status', '!=', 'paid')->orWhereNull('status');
            })
            ->get(['balance_due', 'due_date']);

        $buckets = [
            '0-7 days' => 0,
            '8-30 days' => 0,
            '31-60 days' => 0,
            '61+ days' => 0,
        ];

        foreach ($outstanding as $invoice) {
            if (!$invoice->due_date) {
                continue;
            }
            $days = Carbon::parse($invoice->due_date)->diffInDays(now(), false);
            if ($days <= 7) {
                $buckets['0-7 days'] += (float) $invoice->balance_due;
            } elseif ($days <= 30) {
                $buckets['8-30 days'] += (float) $invoice->balance_due;
            } elseif ($days <= 60) {
                $buckets['31-60 days'] += (float) $invoice->balance_due;
            } else {
                $buckets['61+ days'] += (float) $invoice->balance_due;
            }
        }

        $avgDays = $this->avgDaysToPay($tenant, $from, $to);

        return [
            'buckets' => $buckets,
            'avg_days' => $avgDays,
        ];
    }

    private function detailUnscheduled(Tenant $tenant): array
    {
        $jobs = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', now()))
            ->orderBy('created_at')
            ->limit(15)
            ->get(['id', 'summary', 'status', 'created_at']);

        $rows = $jobs->map(function ($job) {
            $age = $job->created_at ? $job->created_at->diffInDays(now()) : null;
            return [
                'id' => $job->id,
                'summary' => $job->summary ?: 'Job #' . $job->id,
                'status' => $job->status,
                'age_days' => $age,
            ];
        })->all();

        return ['rows' => $rows];
    }
}
