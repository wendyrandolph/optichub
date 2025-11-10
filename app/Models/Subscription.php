<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Eloquent Model for the 'subscriptions' table.
 * Uses HasTenantScope for automatic filtering by tenant_id.
 */
class Subscription extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'subscriptions';

  protected $fillable = [
    'tenant_id',
    'plan_code',
    'status', // e.g., 'trialing', 'active', 'canceled'
    'current_period_end',
    'amount',
    'auto_renew',
  ];

  protected $casts = [
    'current_period_end' => 'datetime',
    'auto_renew' => 'boolean',
    'amount' => 'float',
  ];

  // --- Static Methods (Replacing procedural startTrial) ---

  /**
   * Starts a trial for a tenant, inserting or updating an existing record.
   * Replaces the manual INSERT...ON DUPLICATE KEY UPDATE logic.
   *
   * @param int $tenantId The ID of the tenant.
   * @param string $planCode The plan code to start.
   * @param int $days The length of the trial in days.
   */
  public static function startTrial(int $tenantId, string $planCode, int $days = 14): void
  {
    static::updateOrCreate(
      // Key to find (unique by tenant)
      ['tenant_id' => $tenantId],
      // Values to set/update
      [
        'plan_code' => $planCode,
        'status' => 'trialing',
        // Use Carbon for clean date calculation
        'current_period_end' => now()->addDays($days)->toDateTimeString(),
      ]
    );
  }

  // --- Business Logic Query (Replacing procedural renewalsWithin) ---

  /**
   * Retrieves aggregated data (count, total) for active subscriptions renewing soon.
   *
   * @param int $days The number of days ahead to check for renewal.
   * @return array
   */
  public static function renewalsWithin(int $days = 30): array
  {
    $endDate = now()->addDays($days);

    // The HasTenantScope trait automatically filters by the current user's tenant_id,
    // eliminating the manual tenantClause and user role checks.
    $results = static::query()
      ->where('status', 'active')
      ->where('auto_renew', 1)
      // Use Eloquent's whereBetween to replace DATE_ADD(UTC_TIMESTAMP(), INTERVAL :d DAY)
      ->whereBetween('current_period_end', [now(), $endDate])
      // Select the count and sum, ensuring amount is treated as 0 if null
      ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
      ->first();

    // Note: The check for `hasAmount` is replaced by assuming the column exists
    // (standard Eloquent practice) and using COALESCE(SUM(amount), 0) for safety.
    $hasAmount = DB::getSchemaBuilder()->hasColumn('subscriptions', 'amount');

    return [
      'count' => (int)($results->cnt ?? 0),
      'total' => (float)($results->total ?? 0),
      'hasAmount' => $hasAmount,
    ];
  }
}
