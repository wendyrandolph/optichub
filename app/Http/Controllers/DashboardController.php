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

  public function index(Tenant $tenant): View
  {
    $payload = $this->dashboard->getTenantDashboardPayload((int) $tenant->getKey());

    return view('dashboards.index', array_merge([
      'tenant' => $tenant,
    ], $payload));
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
  public function tasks()
  {
    return $this->index();
  }
  public function time()
  {
    return $this->index();
  }
  public function opportunities()
  {
    return $this->index();
  }
  public function leads()
  {
    return $this->index();
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
