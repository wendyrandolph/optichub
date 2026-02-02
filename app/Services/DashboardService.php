<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Invoice;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
  /**
   * Entry point: return everything the dashboard view needs.
   * Cached per-tenant for a short TTL to keep it snappy.
   */
  public function getTenantDashboardPayload(int $tenantId): array
  {
    $cacheKey = "dashboard.v1.tenant.{$tenantId}";
    $ttl = now()->addMinutes(2);

    return Cache::remember($cacheKey, $ttl, function () use ($tenantId) {
      // Core datasets
      $taskData         = $this->tasksCompletedByUserBar($tenantId);
      $projectHours     = $this->hoursPerProjectLine($tenantId);
      $leadStatusCounts = $this->leadStatusPie($tenantId);
      $leadsGrowthData  = $this->leadMonthlyGrowthBar($tenantId);

      // Small tables / filters
      $recentLeads = $this->recentLeads($tenantId);
      $owners      = $this->ownersList($tenantId);
      $sources     = $this->leadSources($tenantId);

      // KPIs
      $metrics = $this->kpis($tenantId);

      $signalCounts = $this->profitabilitySignals($tenantId);
      $avgEhr = $this->averageProjectEhr($tenantId);
      return compact(
        'taskData',
        'projectHours',
        'leadStatusCounts',
        'leadsGrowthData',
        'recentLeads',
        'owners',
        'sources',
        'metrics',
        'signalCounts',
        'avgEhr'
      );
    });
  }

  public function getTenantWorkspaceOverviewPayload(int $tenantId, array $filters = []): array
  {
    $rangeKey = $filters['range'] ?? 'wtd';
    $queueKey = $filters['queue'] ?? null;

    $cacheKey = "dashboard.workspace.v1.tenant.{$tenantId}.{$rangeKey}.{$queueKey}";
    $ttl = now()->addMinutes(2);

    return Cache::remember($cacheKey, $ttl, function () use ($tenantId, $rangeKey, $queueKey) {
      $range = $this->resolveRange($rangeKey);

      $tasksBase = Task::query()
        ->where('tasks.tenant_id', $tenantId)
        ->open()
        ->with([
          'project:id,project_name',
          'user:id,first_name,last_name,email',
        ]);

      $tasksDueTodayCount = (clone $tasksBase)->dueToday()->count();
      $tasksOverdueCount = (clone $tasksBase)->overdue()->count();
      $tasksBlockedCount = (clone $tasksBase)->where('tasks.status', 'blocked')->count();

      $dueSoonEnd = now()->addDays(7)->toDateString();
      $tasksDueSoonQuery = (clone $tasksBase)
        ->whereNotNull('tasks.due_date')
        ->whereBetween('tasks.due_date', [now()->toDateString(), $dueSoonEnd]);
      $tasksDueSoonCount = (int) $tasksDueSoonQuery->count();

      $tasksDueToday = (clone $tasksBase)
        ->dueToday()
        ->orderBy('due_date')
        ->limit(6)
        ->get(['id', 'title', 'due_date', 'project_id', 'status', 'user_id']);

      $tasksOverdue = (clone $tasksBase)
        ->overdue()
        ->orderBy('due_date')
        ->limit(6)
        ->get(['id', 'title', 'due_date', 'project_id', 'status', 'user_id']);

      $tasksDueSoon = (clone $tasksDueSoonQuery)
        ->orderBy('due_date')
        ->limit(6)
        ->get(['id', 'title', 'due_date', 'project_id', 'status', 'user_id']);

      $queue = $queueKey;
      if (!in_array($queue, ['overdue', 'today', 'soon', 'blocked'], true)) {
        $queue = $tasksOverdueCount > 0 ? 'overdue' : 'today';
      }
      $tasksQueue = match ($queue) {
        'overdue' => $tasksOverdue,
        'soon' => $tasksDueSoon,
        'blocked' => (clone $tasksBase)->where('tasks.status', 'blocked')->orderBy('due_date')->limit(6)->get(['id', 'title', 'due_date', 'project_id', 'status', 'user_id']),
        default => $tasksDueToday,
      };

      $overdueInvoicesQuery = Invoice::query()
        ->where('invoices.tenant_id', $tenantId)
        ->whereNotNull('due_date')
        ->whereDate('due_date', '<', now()->toDateString())
        ->whereNotIn('status', ['paid'])
        ->where(function ($q) {
          $q->where('balance_due', '>', 0)->orWhereNull('balance_due');
        });

      $invoicesOverdueCount = (int) $overdueInvoicesQuery->count();
      $invoicesOverdueTotal = (float) $overdueInvoicesQuery
        ->sum(DB::raw('COALESCE(balance_due, total_amount, total, 0)'));

      $dueSoonEnd = now()->addDays(7)->toDateString();
      $dueSoonInvoicesQuery = Invoice::query()
        ->where('invoices.tenant_id', $tenantId)
        ->whereNotNull('due_date')
        ->whereBetween('due_date', [now()->toDateString(), $dueSoonEnd])
        ->whereNotIn('status', ['paid'])
        ->where(function ($q) {
          $q->where('balance_due', '>', 0)->orWhereNull('balance_due');
        });

      $invoicesDueSoonCount = (int) $dueSoonInvoicesQuery->count();
      $invoicesDueSoonTotal = (float) $dueSoonInvoicesQuery
        ->sum(DB::raw('COALESCE(balance_due, total_amount, total, 0)'));
      $invoicesDueSoon = (clone $dueSoonInvoicesQuery)
        ->with('contact:id,firstName,lastName,email')
        ->orderBy('due_date')
        ->limit(3)
        ->get(['id', 'invoice_number', 'contact_id', 'due_date', 'balance_due', 'total_amount', 'total']);

      $draftInvoicesCount = (int) Invoice::query()
        ->where('invoices.tenant_id', $tenantId)
        ->where('status', 'draft')
        ->count();

      $outstandingTotal = (float) Invoice::query()
        ->where('invoices.tenant_id', $tenantId)
        ->whereNotIn('status', ['paid'])
        ->sum(DB::raw('COALESCE(balance_due, total_amount, total, 0)'));

      $collectedTotal = (float) Invoice::paymentsSumBetween($range['start'], $range['end']);

      $hoursLogged = (float) TimeEntry::query()
        ->where('time_entries.tenant_id', $tenantId)
        ->whereNotNull('end_time')
        ->whereBetween('date', [$range['start']->toDateString(), $range['end']->toDateString()])
        ->sum('hours');

      $showHoursLogged = TimeEntry::query()
        ->where('time_entries.tenant_id', $tenantId)
        ->exists();

      $staleDate = now()->subDays(14);
      $atRiskBase = Project::query()
        ->where('tenant_id', $tenantId)
        ->whereNotIn('status', ['closed'])
        ->where('updated_at', '<', $staleDate)
        ->with([
          'client:id,firstName,lastName,email',
          'company:id,company_name',
        ])
        ->orderBy('updated_at')
        ->limit(5);
      $atRiskProjectsCount = (clone $atRiskBase)->count();
      $atRiskProjects = $atRiskBase
        ->get(['id', 'project_name', 'updated_at', 'contact_id', 'client_company_id', 'status'])
        ->map(function (Project $project) {
          $days = max(1, now()->diffInDays($project->updated_at));
          $clientName = trim(($project->contact?->firstName ?? '') . ' ' . ($project->contact?->lastName ?? ''))
            ?: ($project->company?->name ?? 'Client');

          return [
            'id' => $project->id,
            'name' => $project->project_name,
            'client' => $clientName,
            'updated_at' => $project->updated_at,
            'reason' => "No activity in {$days} days",
          ];
        });

      $recentActivityQuery = ActivityLog::query()
        ->forTenant($tenantId)
        ->with('user:id,first_name,last_name,email')
        ->latest()
        ->limit(5);

      $recentActivity = $recentActivityQuery->get();

      return [
        'range' => $rangeKey,
        'tasksDueToday' => $tasksDueToday,
        'tasksOverdue' => $tasksOverdue,
        'tasksDueSoon' => $tasksDueSoon,
        'tasksDueTodayCount' => $tasksDueTodayCount,
        'tasksOverdueCount' => $tasksOverdueCount,
        'tasksDueSoonCount' => $tasksDueSoonCount,
        'tasksBlockedCount' => $tasksBlockedCount,
        'tasksQueue' => $tasksQueue,
        'queue' => $queue,
        'invoicesOverdueCount' => $invoicesOverdueCount,
        'invoicesOverdueTotal' => $invoicesOverdueTotal,
        'invoicesDueSoonCount' => $invoicesDueSoonCount,
        'invoicesDueSoonTotal' => $invoicesDueSoonTotal,
        'invoicesDueSoon' => $invoicesDueSoon,
        'outstandingTotal' => $outstandingTotal,
        'collectedTotal' => $collectedTotal,
        'draftInvoicesCount' => $draftInvoicesCount,
        'hoursLogged' => $hoursLogged,
        'showHoursLogged' => $showHoursLogged,
        'atRiskProjects' => $atRiskProjects,
        'atRiskProjectsCount' => $atRiskProjectsCount,
        'recentActivity' => $recentActivity,
      ];
    });
  }

  /** -----------------------
   *  Aggregations + Charts
   * ----------------------*/

  public function tasksCompletedByUser(int $tenantId)
  {
    return Task::query()
      ->leftJoin('users', 'users.id', '=', 'tasks.user_id')
      ->where('tasks.tenant_id', $tenantId)          // qualify table
      ->where('tasks.status', 'completed')           // properly bound
      ->selectRaw("
            COALESCE(
              NULLIF(TRIM(CONCAT(users.first_name, ' ', users.last_name)), ''),
              users.email,
              'Unassigned'
            ) as name
        ")
      ->selectRaw('COUNT(*) as completed_count')
      ->groupBy('name')
      ->orderByDesc('completed_count')
      ->get();
  }

  public function hoursPerProjectLine(int $tenantId): array
  {
    $rows = TimeEntry::query()
      ->where('time_entries.tenant_id', $tenantId)
      ->join('projects', 'projects.id', '=', 'time_entries.project_id')
      ->selectRaw("COALESCE(projects.project_name, 'Unassigned') as name, SUM(time_entries.hours) as total_hours")
      ->groupBy('name')
      ->orderByDesc('total_hours')
      ->get();

    return $this->toLineData(
      $rows->pluck('name')->all(),
      $rows->pluck('total_hours')->map(fn($h) => (float)$h)->all(),
      'Hours Logged',
      '#3b82f6',
      'rgba(59,130,246,0.15)',
      0.3,
      true
    );
    // If you prefer bar, switch to $this->toBarData(...)
  }
  public function tasksCompletedByUserBar(int $tenantId): array
  {
    $rows   = $this->tasksCompletedByUser($tenantId);
    $labels = $rows->pluck('name')->values();
    $data   = $rows->pluck('completed_count')->values();

    // Renlo palette (feel free to tweak)
    $palette = ['#2E5D95', '#679CD5', '#62AC39', '#5E4587', '#A586CB', '#F59E0B', '#EF4444', '#10B981', '#3B82F6'];
    // Repeat colors if needed
    $colors  = collect(range(0, max(0, $data->count() - 1)))
      ->map(fn($i) => $palette[$i % count($palette)])->all();

    return [
      'labels'   => $labels,
      'datasets' => [[
        'label'           => 'Tasks Completed',
        'data'            => $data,
        'backgroundColor' => $colors,
        'borderRadius'    => 6,
      ]],
    ];
  }
  public function leadStatusPie(int $tenantId): array
  {
    $statusOrder = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];

    $counts = Lead::query()
      ->where('tenant_id', $tenantId)
      ->select('status', DB::raw('COUNT(*) as c'))
      ->groupBy('status')
      ->pluck('c', 'status');

    $data = array_map(fn($s) => (int)($counts[$s] ?? 0), $statusOrder);

    return $this->toPieData(
      array_map('ucfirst', $statusOrder),
      $data,
      ['#60a5fa', '#facc15', '#34d399', '#10b981', '#f87171', '#9ca3af']
    );
  }

  public function leadMonthlyGrowthBar(int $tenantId): array
  {
    $since = now()->subMonths(6)->startOfMonth();

    $rows = Lead::query()
      ->where('tenant_id', $tenantId)
      ->where('created_at', '>=', $since)
      ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as c')
      ->groupBy('ym')
      ->orderBy('ym')
      ->get();

    $labels = [];
    $values = [];

    $cursor = now()->subMonths(5)->startOfMonth();
    for ($i = 0; $i < 6; $i++, $cursor = $cursor->addMonth()) {
      $ym = $cursor->format('Y-m');
      $labels[] = $cursor->format('M Y');
      $values[] = (int)($rows->firstWhere('ym', $ym)->c ?? 0);
    }

    return $this->toBarData(
      $labels,
      $values,
      'Leads per Month',
      ['#60a5fa']
    );
  }

  public function recentLeads(int $tenantId, int $limit = 10): Collection
  {
    return Lead::query()
      ->where('tenant_id', $tenantId)
      ->latest('created_at')
      ->limit($limit)
      ->get(['id', 'name', 'email', 'owner_id', 'source', 'status', 'created_at']);
  }

  public function ownersList(int $tenantId): Collection
  {
    return User::query()
      ->where('tenant_id', $tenantId)
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->get(['id', 'email', 'first_name', 'last_name'])
      ->map(fn($u) => [
        'id'   => $u->id,
        'name' => trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? ''))
          ?: $u->email
          ?: 'User #' . $u->id,
      ]);
  }

  public function leadSources(int $tenantId): Collection
  {
    return Lead::query()
      ->where('tenant_id', $tenantId)
      ->whereNotNull('source')
      ->distinct()
      ->pluck('source')
      ->values();
  }



  public function profitabilitySignals(int $tenantId): array
  {
    $counts = Project::query()
      ->where('tenant_id', $tenantId)
      ->get()
      ->map(fn(Project $project) => $project->ehrSignal())
      ->countBy()
      ->all();

    return [
      'healthy' => (int) ($counts['healthy'] ?? 0),
      'drifting' => (int) ($counts['drifting'] ?? 0),
      'time-heavy' => (int) ($counts['time-heavy'] ?? 0),
    ];
  }

  public function averageProjectEhr(int $tenantId): string
  {
    $avg = Project::query()
      ->where('tenant_id', $tenantId)
      ->get()
      ->map->currentEhr()
      ->filter()
      ->map(fn($value) => (float) $value)
      ->avg();

    if ($avg === null) {
      return '—';
    }

    return '$' . number_format($avg, 2);
  }

  public function kpis(int $tenantId): array
  {
    // New leads WTD
    $newLeads = Lead::where('tenant_id', $tenantId)
      ->where('created_at', '>=', now()->startOfWeek())
      ->count();

    // Active leads
    $active = Lead::where('tenant_id', $tenantId)
      ->whereIn('status', ['new', 'contacted', 'interested'])
      ->count();

    // Conversion rate
    $won = Lead::where('tenant_id', $tenantId)->whereIn('status', ['client', 'closed'])->count();
    $total = max(1, Lead::where('tenant_id', $tenantId)->count());
    $convRate = round(($won / $total) * 100, 1);

    // Avg days to convert
    $avgDays = (float) Lead::query()
      ->where('tenant_id', $tenantId)
      ->whereIn('status', ['client', 'closed'])
      ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
      ->value('avg_days') ?? 0.0;
    $avgDays = round($avgDays, 1);

    return [
      'new'              => $newLeads,
      'active'           => $active,
      'convRate'         => $convRate,
      'avgDaysToConvert' => $avgDays,
    ];
  }

  /** -----------------------
   *  Small chart helpers
   * ----------------------*/

  private function toBarData(array $labels, array $data, string $label, array $colors): array
  {
    return [
      'labels'   => $labels,
      'datasets' => [[
        'label'           => $label,
        'data'            => $data,
        'backgroundColor' => $this->repeatPalette($colors, count($data)),
        'borderWidth'     => 0,
      ]],
    ];
  }

  private function toLineData(
    array $labels,
    array $data,
    string $label,
    string $border,
    string $bg,
    float $tension = 0.3,
    bool $fill = true
  ): array {
    return [
      'labels'   => $labels,
      'datasets' => [[
        'label'           => $label,
        'data'            => $data,
        'borderColor'     => $border,
        'backgroundColor' => $bg,
        'tension'         => $tension,
        'fill'            => $fill,
      ]],
    ];
  }

  private function toPieData(array $labels, array $data, array $colors): array
  {
    return [
      'labels'   => $labels,
      'datasets' => [[
        'data'            => $data,
        'backgroundColor' => $this->repeatPalette($colors, count($data)),
      ]],
    ];
  }

  private function repeatPalette(array $palette, int $n): array
  {
    if (empty($palette)) return array_fill(0, $n, '#e5e7eb');
    $out = [];
    for ($i = 0; $i < $n; $i++) {
      $out[] = $palette[$i % count($palette)];
    }
    return $out;
  }

  private function resolveRange(string $rangeKey): array
  {
    $now = now();
    return match ($rangeKey) {
      'today' => [
        'start' => $now->copy()->startOfDay(),
        'end' => $now->copy()->endOfDay(),
      ],
      'mtd' => [
        'start' => $now->copy()->startOfMonth(),
        'end' => $now->copy()->endOfDay(),
      ],
      '30d' => [
        'start' => $now->copy()->subDays(29)->startOfDay(),
        'end' => $now->copy()->endOfDay(),
      ],
      default => [
        'start' => $now->copy()->startOfWeek(),
        'end' => $now->copy()->endOfDay(),
      ],
    };
  }
}
