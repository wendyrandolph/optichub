<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
  /**
   * Main dashboards index page — task completion stats, time per project, lead stats.
   */    /** @var DashboardService */
  protected $dashboard;

  public function __construct(DashboardService $dashboard)
  {
    $this->middleware(['auth', 'tenant']);
    $this->dashboard = $dashboard; // <- assign it
  }

  public function index(?Tenant $tenant = null): View
  {
    $tenant = $tenant ?: request()->route('tenant');
    $payload = [];
    if ($tenant) {
      $payload = $this->dashboard->getTenantWorkspaceOverviewPayload(
        (int) $tenant->getKey(),
        [
          'range' => request('range', 'wtd'),
          'queue' => request('queue'),
        ]
      );
    }
    // Tenant workspace overview dashboard
    return view('dashboards.tenant', [
      'tenant' => $tenant,
      'range' => $payload['range'] ?? 'wtd',
      'tasksDueToday' => $payload['tasksDueToday'] ?? collect(),
      'tasksOverdue' => $payload['tasksOverdue'] ?? collect(),
      'tasksDueSoon' => $payload['tasksDueSoon'] ?? collect(),
      'tasksDueTodayCount' => $payload['tasksDueTodayCount'] ?? 0,
      'tasksOverdueCount' => $payload['tasksOverdueCount'] ?? 0,
      'tasksDueSoonCount' => $payload['tasksDueSoonCount'] ?? 0,
      'tasksBlockedCount' => $payload['tasksBlockedCount'] ?? 0,
      'tasksQueue' => $payload['tasksQueue'] ?? collect(),
      'queue' => $payload['queue'] ?? 'today',
      'invoicesOverdueCount' => $payload['invoicesOverdueCount'] ?? 0,
      'invoicesOverdueTotal' => $payload['invoicesOverdueTotal'] ?? 0,
      'invoicesDueSoonCount' => $payload['invoicesDueSoonCount'] ?? 0,
      'invoicesDueSoonTotal' => $payload['invoicesDueSoonTotal'] ?? 0,
      'invoicesDueSoon' => $payload['invoicesDueSoon'] ?? collect(),
      'outstandingTotal' => $payload['outstandingTotal'] ?? 0,
      'collectedTotal' => $payload['collectedTotal'] ?? 0,
      'draftInvoicesCount' => $payload['draftInvoicesCount'] ?? 0,
      'hoursLogged' => $payload['hoursLogged'] ?? 0,
      'showHoursLogged' => $payload['showHoursLogged'] ?? false,
      'atRiskProjects' => $payload['atRiskProjects'] ?? collect(),
      'atRiskProjectsCount' => $payload['atRiskProjectsCount'] ?? 0,
      'recentActivity' => $payload['recentActivity'] ?? collect(),
    ]);
  }

  /** -----------------------
   * Chart helpers (tiny)
   * --------------------- */
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

  private function toLineData(array $labels, array $data, string $label, string $border, string $bg, float $tension = 0.3, bool $fill = true): array
  {
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

  /**
   * If you wired tab routes (admin.dashboards.tasks/time/opportunities/leads)
   * you can reuse the same payload for now:
   */
  public function tasks(Tenant $tenant): View
  {
    return $this->leadInsights($tenant);
  }
  public function time(Tenant $tenant): View
  {
    return $this->leadInsights($tenant);
  }
  public function opportunities(Tenant $tenant): View
  {
    return $this->leadInsights($tenant);
  }
  public function leads(Tenant $tenant): View
  {
    return $this->leadInsights($tenant);
  }

  public function leadInsights(?Tenant $tenant = null): View
  {
    $tenant = $tenant ?: request()->route('tenant');
    $payload = [];
    if ($tenant) {
      $payload = $this->dashboard->getTenantDashboardPayload((int) $tenant->getKey());
    }

    $byStatus = $payload['leadStatusCounts'] ?? ['labels' => [], 'datasets' => []];
    $growth = $payload['leadsGrowthData'] ?? ['labels' => [], 'datasets' => []];

    return view('dashboards.index', [
      'tenant' => $tenant,
      'metrics' => $payload['metrics'] ?? ['new' => 0, 'convRate' => 0, 'avgDaysToConvert' => 0, 'active' => 0],
      'byStatus' => $byStatus,
      'bySource' => $payload['bySource'] ?? ['labels' => [], 'datasets' => []],
      'growth' => $growth,
      'funnel' => $payload['funnel'] ?? ['labels' => [], 'datasets' => []],
      'recentLeads' => $payload['recentLeads'] ?? collect(),
      'owners' => $payload['owners'] ?? [],
      'sources' => $payload['sources'] ?? [],
    ]);
  }

  /**
   * Retrieves monthly lead creation counts for the last 12 months,
   * returning an associative array: ['Oct' => 5, 'Sep' => 3, ...] (oldest → newest).
   */
  protected function getMonthlyLeadGrowth(): array
  {
    // Raw: ['2025-10' => 9, '2025-09' => 4, ...]
    $raw = Lead::select(
      DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
      DB::raw('COUNT(id) as cnt')
    )
      ->groupBy('ym')
      ->orderBy('ym')
      ->get()
      ->keyBy('ym')
      ->toArray();

    // Build last 12 months (inclusive), newest → oldest
    $months = [];
    $cursor = Carbon::now();

    for ($i = 0; $i < 12; $i++) {
      $ym      = $cursor->format('Y-m');
      $label   = $cursor->format('M'); // 'Jan', 'Feb'… (short) — use 'F' for full
      $months[$label] = (int) ($raw[$ym]['cnt'] ?? 0);
      $cursor->subMonth();
    }

    // Reverse so oldest first (left→right chronological)
    return array_reverse($months, true);
  }

  // -------------------------------
  // Helpers to normalize data for Chart.js
  // -------------------------------

  /**
   * Accepts:
   *  - Associative array: ['Key' => value, ...]
   *  - Or Chart.js-shaped ['labels' => [...], 'datasets' => [...]]
   * Returns a BAR dataset.
   */
  protected function toChartBar($raw, array $defaults = []): array
  {
    if (is_array($raw) && isset($raw['labels'], $raw['datasets'])) {
      return $raw; // already Chart.js-shaped
    }

    if (is_array($raw)) {
      $labels = array_keys($raw);
      $data   = array_values($raw);

      return [
        'labels'   => $labels,
        'datasets' => [[
          'label'           => $defaults['label']           ?? 'Dataset',
          'data'            => $data,
          'backgroundColor' => $defaults['backgroundColor'] ?? '#3b82f6',
        ]],
      ];
    }

    // Fallback empty
    return ['labels' => [], 'datasets' => []];
  }

  /**
   * Same as bar, but returns a LINE dataset with sane defaults.
   */
  protected function toChartLine($raw, array $defaults = []): array
  {
    if (is_array($raw) && isset($raw['labels'], $raw['datasets'])) {
      return $raw; // already Chart.js-shaped
    }

    if (is_array($raw)) {
      $labels = array_keys($raw);
      $data   = array_values($raw);

      return [
        'labels'   => $labels,
        'datasets' => [[
          'label'           => $defaults['label']           ?? 'Dataset',
          'data'            => $data,
          'borderColor'     => $defaults['borderColor']     ?? '#3b82f6',
          'backgroundColor' => $defaults['backgroundColor'] ?? 'rgba(59,130,246,0.15)',
          'tension'         => $defaults['tension']         ?? 0.3,
          'fill'            => $defaults['fill']            ?? true,
        ]],
      ];
    }

    return ['labels' => [], 'datasets' => []];
  }
}
