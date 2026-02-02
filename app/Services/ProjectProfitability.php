<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;

class ProjectProfitability
{
    public function forProject(Project $project): array
    {
        $timeQuery = TimeEntry::query()
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->whereNotNull('end_time');

        $actualHours = (float) $timeQuery->sum('hours');
        $billableHours = (float) (clone $timeQuery)->where('billable', true)->sum('hours');

        $projectFee = (float) ($project->project_fee_total ?? 0);
        $externalCosts = (float) ($project->external_costs ?? 0);
        $net = $projectFee - $externalCosts;

        $ehr = $billableHours > 0 ? round($net / $billableHours, 2) : null;
        $forecastEhr = ($project->budgeted_hours ?? 0) > 0
            ? round($net / (float) $project->budgeted_hours, 2)
            : null;

        $target = (float) ($project->target_hourly_rate ?? 0);
        $signal = $this->signalFor($forecastEhr, $target);
        $gap = ($target > 0 && $forecastEhr !== null) ? round($target - $forecastEhr, 2) : null;

        $amountExpr = DB::raw('COALESCE(invoices.total_amount, invoices.total, invoices.subtotal, 0)');
        $invoicedTotal = (float) Invoice::query()
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->sum($amountExpr);

        $paidTotal = (float) Invoice::query()
            ->where('tenant_id', $project->tenant_id)
            ->where('project_id', $project->id)
            ->where('status', 'paid')
            ->sum($amountExpr);

        return [
            'actual_hours' => $actualHours,
            'billable_hours' => $billableHours,
            'project_fee' => $projectFee,
            'external_costs' => $externalCosts,
            'budgeted_hours' => (float) ($project->budgeted_hours ?? 0),
            'ehr' => $ehr,
            'forecast_ehr' => $forecastEhr,
            'target_rate' => $target,
            'target_gap' => $gap,
            'signal' => $signal,
            'invoiced_total' => $invoicedTotal,
            'paid_total' => $paidTotal,
        ];
    }

    public function signalFor(?float $forecastEhr, float $target): string
    {
        if ($forecastEhr === null || $target <= 0) {
            return 'neutral';
        }
        if ($forecastEhr >= $target) {
            return 'healthy';
        }
        if ($forecastEhr >= $target * 0.8) {
            return 'drifting';
        }
        return 'time-heavy';
    }
}
