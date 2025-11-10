<?php

namespace App\Services\Reports;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportService
{
  public function get(Tenant $tenant, string $type, array $filters = []): array
  {
    return match ($type) {
      'invoices' => $this->invoices($tenant, $filters),
      'collected' => $this->payments($tenant, $filters),
      'forecast' => $this->forecast($tenant, $filters),
      'ar_aging' => $this->arAging($tenant, $filters),
      'tasks_due' => $this->tasksDue($tenant, $filters),
      default    => [],
    };
  }

  protected function invoices(Tenant $tenant, array $filters): array
  {
    if (!Schema::hasTable('invoices')) return [];
    return DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->select('id', 'number', 'status', 'due_date', 'balance_due', 'created_at', 'updated_at')
      ->orderByDesc('created_at')
      ->limit(1000)
      ->get()->map(fn($r) => (array)$r)->all();
  }

  protected function payments(Tenant $tenant, array $filters): array
  {
    if (!Schema::hasTable('payments')) return [];
    return DB::table('payments')
      ->where('tenant_id', $tenant->id)
      ->select('id', 'invoice_id', 'amount', 'paid_at', 'method')
      ->orderByDesc('paid_at')
      ->limit(1000)
      ->get()->map(fn($r) => (array)$r)->all();
  }


  protected function arAging(Tenant $tenant, array $filters): array
  {
    if (!Schema::hasTable('invoices')) return [];
    return DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->whereIn('status', ['sent', 'overdue'])
      ->select('id', 'number', 'client_name', 'due_date', 'balance_due')
      ->orderBy('due_date')
      ->get()->map(fn($r) => (array)$r)->all();
  }

  protected function tasksDue(Tenant $tenant, array $filters): array
  {
    if (!Schema::hasTable('tasks')) return [];
    return DB::table('tasks')
      ->where('tenant_id', $tenant->id)
      ->whereDate('due_date', now()->toDateString())
      ->whereIn('status', ['open', 'in_progress'])
      ->select('id', 'title', 'assignee_id', 'due_date', 'status')
      ->orderBy('due_date')
      ->get()->map(fn($r) => (array)$r)->all();
  }
  protected function collected(Tenant $tenant, array $filters): array
  {
    $hasAmount = Schema::hasColumn('payments', 'amount');
    $hasCents  = Schema::hasColumn('payments', 'amount_cents');
    $amountExpr = $hasAmount ? 'amount' : ($hasCents ? '(amount_cents/100)' : '0');
    $hasPaidAt  = Schema::hasColumn('payments', 'paid_at');
    $dateCol    = $hasPaidAt ? 'paid_at' : 'created_at';

    [$start, $end] = match ($filters['range'] ?? 'wtd') {
      'mtd' => [now()->startOfMonth(), now()],
      'qtd' => [now()->firstOfQuarter(), now()],
      'ytd' => [now()->startOfYear(), now()],
      'last30' => [now()->subDays(30), now()],
      default => [now()->startOfWeek(), now()],
    };

    $q = DB::table('payments')
      ->where('tenant_id', $tenant->id)
      ->whereBetween($dateCol, [$start, $end])
      ->when(!empty($filters['method']), fn($qq) => $qq->where('method', $filters['method']))
      ->when(!empty($filters['status']), fn($qq) => $qq->where('status', $filters['status']))
      ->selectRaw("id, invoice_id, provider, provider_ref, currency, status, $dateCol, $amountExpr as amount");

    if (Schema::hasColumn('payments', 'method')) {
      $q->addSelect('method');
    }

    return $q->orderBy($dateCol, 'desc')->limit(5000)
      ->get()->map(fn($r) => (array)$r)->all();
  }

  protected function forecast(\App\Models\Tenant $tenant, array $filters): array
  {
    $window = $filters['window'] ?? 'next14';
    [$start, $end] = match ($window) {
      'next7'  => [now(), now()->addDays(7)],
      'next14' => [now(), now()->addDays(14)],
      'next30' => [now(), now()->addDays(30)],
      'custom' => [
        \Carbon\Carbon::parse($filters['from'] ?? now()),
        \Carbon\Carbon::parse($filters['to'] ?? now()->addDays(14)),
      ],
      default  => [now(), now()->addDays(14)],
    };

    $q = DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->whereBetween('due_date', [$start, $end])
      ->when(
        !empty($filters['status']),
        fn($qq) => $qq->where('status', $filters['status']),
        fn($qq) => $qq->whereIn('status', ['draft', 'sent'])
      )
      ->when(!empty($filters['q']), fn($qq) =>
      $qq->where(function ($w) use ($filters) {
        $q = trim((string)$filters['q']);
        $w->where('client_name', 'like', "%{$q}%")
          ->orWhere('number', 'like', "%{$q}%");
      }))
      ->when(isset($filters['min']), fn($qq) => $qq->where('balance_due', '>=', (float)$filters['min']))
      ->when(isset($filters['max']), fn($qq) => $qq->where('balance_due', '<=', (float)$filters['max']))
      ->select('id', 'number', 'client_name', 'status', 'due_date', 'balance_due', 'created_at')
      ->orderBy('due_date', 'asc');

    return $q->limit(5000)->get()->map(fn($r) => (array)$r)->all();
  }

  /** ===== AR AGING EXPORT ===== */
  protected function arAging(Tenant $tenant, array $filters): array
  {
    $asOf   = isset($filters['as_of']) ? now()->parse($filters['as_of']) : now();
    $status = $filters['status'] ?? null;
    $bucket = $filters['bucket'] ?? 'all';
    $qText  = trim((string) ($filters['q'] ?? ''));
    $min    = $filters['min'] ?? null;
    $max    = $filters['max'] ?? null;

    // Resolve invoice columns safely (duplicate of controller helper)
    $has = fn($c) => Schema::hasColumn('invoices', $c);

    $numberCol = collect(['number', 'invoice_number', 'no', 'code'])->first($has) ?? 'id';
    $clientCol = collect(['client_name', 'customer_name', 'bill_to_name', 'organization_name', 'contact_name'])->first($has);
    $dueCol    = collect(['due_date', 'due_at', 'due_on'])->first($has) ?? 'created_at';

    if ($has('balance_due'))   $balanceExpr = 'balance_due';
    elseif ($has('amount_due'))    $balanceExpr = 'amount_due';
    elseif ($has('outstanding'))   $balanceExpr = 'outstanding';
    elseif ($has('total_due'))     $balanceExpr = 'total_due';
    elseif ($has('total') && $has('amount_paid')) $balanceExpr = '(COALESCE(total,0) - COALESCE(amount_paid,0))';
    elseif ($has('subtotal') && $has('tax_total') && $has('amount_paid')) $balanceExpr = '((COALESCE(subtotal,0)+COALESCE(tax_total,0))-COALESCE(amount_paid,0))';
    else $balanceExpr = '0';

    $asOfSql = $asOf->toDateString();
    $bucketCase = "
            CASE
              WHEN DATEDIFF(?, `$dueCol`) < 0 THEN 'current'
              WHEN DATEDIFF(?, `$dueCol`) BETWEEN 0 AND 30 THEN '0-30'
              WHEN DATEDIFF(?, `$dueCol`) BETWEEN 31 AND 60 THEN '31-60'
              WHEN DATEDIFF(?, `$dueCol`) BETWEEN 61 AND 90 THEN '61-90'
              ELSE '90+'
            END
        ";

    $base = DB::table('invoices')
      ->where('tenant_id', $tenant->id)
      ->when(
        $status,
        fn($q) => $q->where('status', $status),
        fn($q) => $q->whereIn('status', ['sent', 'overdue'])
      )
      ->whereRaw("({$balanceExpr}) > 0")
      ->when($qText !== '', function ($qq) use ($qText, $numberCol, $clientCol) {
        $qq->where(function ($w) use ($qText, $numberCol, $clientCol) {
          $w->where($numberCol, 'like', "%{$qText}%");
          if ($clientCol) $w->orWhere($clientCol, 'like', "%{$qText}%");
        });
      })
      ->when(isset($min), fn($qq) => $qq->whereRaw("{$balanceExpr} >= ?", [(float)$min]))
      ->when(isset($max), fn($qq) => $qq->whereRaw("{$balanceExpr} <= ?", [(float)$max]));

    if ($bucket !== 'all') {
      $base->whereRaw("$bucketCase = ?", [$asOfSql, $asOfSql, $asOfSql, $asOfSql, $bucket]);
    }

    $rows = $base
      ->selectRaw("
                id,
                " . ($numberCol === 'number' ? 'number' : "`$numberCol` as number") . ",
                " . ($clientCol ? "`$clientCol` as client_name" : "'—' as client_name") . ",
                status,
                " . ($dueCol === 'due_date' ? 'due_date' : "`$dueCol` as due_date") . ",
                {$balanceExpr} as balance_due,
                $bucketCase as bucket
            ", [$asOfSql, $asOfSql, $asOfSql, $asOfSql])
      ->orderBy($dueCol, 'asc')
      ->limit(10000)
      ->get();

    // Return as plain arrays for CSV
    return $rows->map(function ($r) {
      return [
        'invoice_id'  => $r->id,
        'number'      => $r->number,
        'client_name' => $r->client_name,
        'status'      => $r->status,
        'due_date'    => (string) $r->due_date,
        'balance_due' => (float)  $r->balance_due,
        'bucket'      => $r->bucket,
      ];
    })->all();
  }
  protected function projectsStale(\App\Models\Tenant $tenant, array $filters): array
  {
    $hasProj  = fn($c) => Schema::hasColumn('projects', $c);

    $nameCol    = $hasProj('name') ? 'name' : ($hasProj('project_name') ? 'project_name' : 'id');
    $clientCol  = $hasProj('client_name') ? 'client_name' : null;
    $ownerIdCol = $hasProj('owner_id') ? 'owner_id' : null;
    $lastCol    = $hasProj('last_activity_at') ? 'last_activity_at' : ($hasProj('updated_at') ? 'updated_at' : 'created_at');

    $baseline = (int)($filters['days'] ?? 14);
    $minDays  = $filters['min_days'] ?? null;
    $maxDays  = $filters['max_days'] ?? null;
    $status   = $filters['status'] ?? null;
    $ownerId  = $filters['owner'] ?? null;
    $qText    = trim((string)($filters['q'] ?? ''));

    $today = now()->toDateString();
    $staleExpr = "GREATEST(DATEDIFF(?, `projects`.`$lastCol`), 0)";

    $q = DB::table('projects')->where('projects.tenant_id', $tenant->id);

    if ($ownerIdCol && Schema::hasTable('users')) {
      $q->leftJoin('users', "projects.$ownerIdCol", '=', 'users.id');
    }

    if ($status) $q->where('projects.status', $status);
    if ($ownerId && $ownerIdCol) $q->where("projects.$ownerIdCol", (int)$ownerId);

    if ($qText !== '') {
      $q->where(function ($w) use ($qText, $nameCol, $clientCol) {
        $w->where("projects.$nameCol", 'like', "%{$qText}%");
        if ($clientCol) $w->orWhere("projects.$clientCol", 'like', "%{$qText}%");
      });
    }

    if ($minDays !== null) {
      $q->whereRaw("$staleExpr >= ?", [$today, (int)$minDays]);
    } else {
      $q->whereRaw("$staleExpr >= ?", [$today, (int)$baseline]);
    }
    if ($maxDays !== null) {
      $q->whereRaw("$staleExpr <= ?", [$today, (int)$maxDays]);
    }

    $rows = $q->selectRaw("
            projects.id,
            " . ($nameCol === 'name' ? 'projects.name' : "projects.`$nameCol` as name") . ",
            " . ($clientCol ? "projects.`$clientCol` as client_name" : "'—' as client_name") . ",
            projects.status,
            projects.`$lastCol` as last_activity_at,
            $staleExpr as stale_days,
            " . ($ownerIdCol && Schema::hasTable('users') ? "COALESCE(users.name,'—') as owner_name" : "'—' as owner_name") . "
        ", [$today, $today])
      ->orderByRaw('stale_days DESC')
      ->limit(10000)
      ->get();

    return $rows->map(fn($r) => [
      'project_id'      => $r->id,
      'name'            => $r->name,
      'client_name'     => $r->client_name,
      'owner_name'      => $r->owner_name,
      'status'          => $r->status,
      'last_activity_at' => (string) $r->last_activity_at,
      'stale_days'      => (int) $r->stale_days,
    ])->all();
  }
}
