<?php

namespace App\Services\Reports;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class SummaryService
{
  /**
   * Build the small numbers/tiles you show on the Reports landing.
   * You can replace these DB::table() calls with model queries later.
   */
  public function forTenant(Tenant $tenant, array $filters = []): array
  {
    $range = strtolower((string) Arr::get($filters, 'range', 'wtd')); // wtd|mtd|qtd|ytd|last30

    // ---- Finance (safe defaults if tables not present) ----
    $invCount   = 0;
    $invTotal   = 0.0;
    $collected  = 0.0;
    $forecast   = ['hasAmount' => false, 'count' => 0, 'total' => 0.0];

    if (Schema::hasTable('invoices')) {
      $invCount = (int) DB::table('invoices')
        ->where('tenant_id', $tenant->id)
        ->whereIn('status', ['sent', 'overdue'])
        ->count();

      $invTotal = (float) DB::table('invoices')
        ->where('tenant_id', $tenant->id)
        ->whereIn('status', ['sent', 'overdue'])
        ->sum('balance_due');

      // Collected (simple example for the chosen range)
      $collected = (float) DB::table('payments')
        ->when(!Schema::hasTable('payments'), fn($q) => 0)
        ->where('tenant_id', $tenant->id)
        ->when(true, fn($q) => $this->applyRange($q, $range, 'paid_at'))
        ->sum('amount');

      // Forecast: next 14 days expected invoices (example)
      $forecastRows = DB::table('invoices')
        ->where('tenant_id', $tenant->id)
        ->where('due_date', '>=', now())
        ->where('due_date', '<=', now()->addDays(14))
        ->whereIn('status', ['draft', 'sent'])
        ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(balance_due),0) as total')
        ->first();

      if ($forecastRows) {
        $forecast = [
          'hasAmount' => ((float) $forecastRows->total) > 0,
          'count'     => (int) $forecastRows->cnt,
          'total'     => (float) $forecastRows->total,
        ];
      }
    }

    // ---- Operations ----
    $tasksDueTodayCount = 0;
    $onTime = ['pct' => 0, 'on_time' => 0, 'total' => 0];
    $staleProjects = 0;
    $overdueOpen = 0;

    if (Schema::hasTable('tasks')) {
      $tasksDueTodayCount = (int) DB::table('tasks')
        ->where('tenant_id', $tenant->id)
        ->whereDate('due_date', now()->toDateString())
        ->whereIn('status', ['open', 'in_progress'])
        ->count();

      $overdueOpen = (int) DB::table('tasks')
        ->where('tenant_id', $tenant->id)
        ->where('status', '!=', 'completed')
        ->whereDate('due_date', '<', now()->toDateString())
        ->count();

      // On-time: naive example using completed_at <= due_date
      $tot = (int) DB::table('tasks')
        ->where('tenant_id', $tenant->id)
        ->where('status', 'completed')
        ->count();

      $on = (int) DB::table('tasks')
        ->where('tenant_id', $tenant->id)
        ->where('status', 'completed')
        ->whereColumn('completed_at', '<=', 'due_date')
        ->count();

      $onTime = [
        'pct'     => $tot > 0 ? (int) round(($on / $tot) * 100) : 0,
        'on_time' => $on,
        'total'   => $tot,
      ];
    }

    if (Schema::hasTable('projects')) {
      // Stale project: no updates in 14 days (example: updated_at older than 14 days)
      $staleProjects = (int) DB::table('projects')
        ->where('tenant_id', $tenant->id)
        ->where('status', '!=', 'closed')
        ->where('updated_at', '<', now()->subDays(14))
        ->count();
    }

    // ---- CRM / Email ----
    $newLeads = 0;
    $emailOutbound = 0;
    $emailInbound = 0;

    if (Schema::hasTable('leads')) {
      $newLeads = (int) DB::table('leads')
        ->where('tenant_id', $tenant->id)
        ->when(true, fn($q) => $this->applyRange($q, $range, 'created_at'))
        ->count();
    }

    if (Schema::hasTable('emails')) {
      $emailOutbound = (int) DB::table('emails')
        ->where('tenant_id', $tenant->id)
        ->where('direction', 'outbound')
        ->when(true, fn($q) => $this->applyRange($q, $range, 'sent_at', 'created_at'))
        ->count();

      $emailInbound = (int) DB::table('emails')
        ->where('tenant_id', $tenant->id)
        ->where('direction', 'inbound')
        ->when(true, fn($q) => $this->applyRange($q, $range, 'received_at', 'created_at'))
        ->count();
    }

    // Return exactly the keys your views expect
    return [
      'inv_count'          => $invCount,
      'inv_total'          => $invTotal,
      'cash_collected'     => $collected,
      'forecast'           => $forecast,
      'tasksDueTodayCount' => $tasksDueTodayCount,
      'on_time'            => $onTime,
      'staleProjects'      => $staleProjects,
      'overdue_open'       => $overdueOpen,
      'new_leads'          => $newLeads,
      'email_outbound'     => $emailOutbound,
      'email_inbound'      => $emailInbound,
    ];
  }

  /**
   * Applies a time range to a query using a date column.
   * Falls back to a secondary column if the primary is null.
   */
  protected function applyRange($query, string $range, string $primaryColumn, ?string $fallbackColumn = null)
  {
    $start = match ($range) {
      'mtd'   => now()->startOfMonth(),
      'qtd'   => now()->firstOfQuarter(),
      'ytd'   => now()->startOfYear(),
      'last30' => now()->subDays(30),
      default => now()->startOfWeek(), // wtd
    };
    $end = now();

    // COALESCE-like filter: if you have a fallback column, include those too
    if ($fallbackColumn) {
      return $query->where(function ($q) use ($primaryColumn, $fallbackColumn, $start, $end) {
        $q->whereBetween($primaryColumn, [$start, $end])
          ->orWhere(function ($q2) use ($fallbackColumn, $start, $end) {
            $q2->whereNull($fallbackColumn)->orWhereBetween($fallbackColumn, [$start, $end]);
          });
      });
    }

    return $query->whereBetween($primaryColumn, [$start, $end]);
  }
  private function sumOpenInvoiceDue(int $tenantId): float
  {
    // Common column names across apps
    $c = fn($name) => Schema::hasColumn('invoices', $name);

    $base = DB::table('invoices')
      ->where('tenant_id', $tenantId)
      ->whereIn('status', ['sent', 'overdue']);

    // 1) Direct columns first
    if ($c('balance_due'))      return (float) $base->sum('balance_due');
    if ($c('amount_due'))       return (float) $base->sum('amount_due');
    if ($c('outstanding'))      return (float) $base->sum('outstanding');
    if ($c('due_amount'))       return (float) $base->sum('due_amount');
    if ($c('total_due'))        return (float) $base->sum('total_due');

    // 2) Derive from total - paid
    if ($c('total') && $c('amount_paid')) {
      return (float) $base->selectRaw('COALESCE(SUM(total - amount_paid),0) as due')->value('due');
    }
    if ($c('subtotal') && $c('tax_total') && $c('amount_paid')) {
      return (float) $base->selectRaw('COALESCE(SUM((subtotal + tax_total) - amount_paid),0) as due')->value('due');
    }

    // 3) Last resort: 0 (prevents crashes)
    return 0.0;
  }

  private function countOpenInvoices(int $tenantId): int
  {
    return (int) DB::table('invoices')
      ->where('tenant_id', $tenantId)
      ->whereIn('status', ['sent', 'overdue'])
      ->count();
  }
}
