<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use DateTimeInterface;

/**
 * Represents a single time entry logged by a user.
 * Uses HasTenantScope for automatic multi-tenant filtering.
 */
class TimeEntry extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'time_entries';

  protected $fillable = [
    'tenant_id',
    'user_id',
    'project_id',
    'task_id',
    'date',
    'hours',
    'start_time',
    'end_time',
    'description',
    'billable',
    'invoice_id',
  ];

  protected $casts = [
    'date' => 'date',
    'hours' => 'float',
    'start_time' => 'datetime',
    'end_time' => 'datetime',
    'billable' => 'boolean',
  ];

  // --- RELATIONSHIPS ---

  /**
   * The user who logged this time entry.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * The project associated with this time entry.
   */
  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class);
  }

  /**
   * The task associated with this time entry.
   */
  public function task(): BelongsTo
  {
    return $this->belongsTo(Task::class);
  }

  // --- SCOPES ---

  /**
   * Scope a query to only include billable time entries.
   */
  public function scopeBillable(Builder $query): Builder
  {
    return $query->where('billable', true);
  }

  /**
   * Scope entries for a specific user.
   */
  public function scopeForUser(Builder $query, int $userId): Builder
  {
    return $query->where('user_id', $userId);
  }

  /**
   * Scope entries logged today.
   */
  public function scopeLoggedToday(Builder $query): Builder
  {
    return $query->whereDate('date', Carbon::today());
  }

  // --- BUSINESS LOGIC / REPORTING METHODS ---

  /**
   * Gets total hours per project for the current organization.
   * The tenant filter is applied automatically by HasTenantScope.
   */
  public static function getTotalHoursPerProject()
  {
    return static::query()
      ->select('projects.project_name', DB::raw('SUM(time_entries.hours) as total_hours'))
      // Join is safe because Project is also tenant-scoped
      ->join('projects', 'time_entries.project_id', '=', 'projects.id')
      ->groupBy('projects.project_name')
      ->orderByDesc('total_hours')
      ->get();
  }

  /**
   * Gets total hours logged for the current organization.
   */
  public static function getTotalHours(): float
  {
    return (float) static::sum('hours');
  }

  /**
   * Sum hours between two dates; optionally limit to a user (model or ID).
   */
  public static function sumHoursBetween(DateTimeInterface $from, DateTimeInterface $to, $user = null): float
  {
    $query = static::query()
      ->whereBetween('date', [$from, $to]);

    if ($user) {
      $userId = $user instanceof \App\Models\User ? $user->id : $user;
      $query->where('user_id', $userId);
    }

    return (float) $query->sum('hours');
  }

  /**
   * Gets weekly time summary for the current organization using MySQL's YEARWEEK function.
   */
  public static function getWeeklyTimeSummary(int $weeks = 6)
  {
    // Using DB::raw for MySQL-specific functions like YEARWEEK
    return static::query()
      ->select(
        DB::raw('YEARWEEK(date, 1) as year_week'),
        DB::raw('MIN(DATE(date)) as week_start'),
        DB::raw('SUM(hours) as total_hours')
      )
      ->groupBy('year_week')
      ->orderByDesc('year_week')
      ->limit($weeks)
      ->get()
      ->reverse(); // Returns oldest first, matching original logic
  }

  /**
   * Gets monthly time summary per user for the current organization (current month).
   */
  public static function getMonthlyUserTime()
  {
    return static::query()
      ->select('users.username', DB::raw('SUM(time_entries.hours) as total_hours'))
      // Join is safe because User is also tenant-scoped
      ->join('users', 'time_entries.user_id', '=', 'users.id')
      ->whereYear('time_entries.date', now()->year)
      ->whereMonth('time_entries.date', now()->month)
      ->groupBy('users.username')
      ->orderByDesc('total_hours')
      ->get();
  }

  /**
   * Gets time logged today for a specific user.
   * HasTenantScope ensures the user and entry belong to the current organization.
   */
  public static function getTimeLoggedToday(int $userId): float
  {
    return (float) static::query()
      ->where('user_id', $userId)
      ->whereDate('date', today())
      ->sum('hours');
  }

  /**
   * Gets total billable hours between two dates for the current organization.
   */
  public static function getBillableHoursBetween(DateTimeInterface $from, DateTimeInterface $to): float
  {
    return (float) static::billable()
      ->whereBetween('created_at', [$from, $to])
      ->sum('hours');
  }

  /**
   * Gets uninvoiced billable hours between two dates for the current organization.
   */
  public static function getUninvoicedBillableHours(DateTimeInterface $from, DateTimeInterface $to): float
  {
    return (float) static::billable()
      ->where(function (Builder $query) {
        // Time is uninvoiced if invoice_id is NULL or 0
        $query->whereNull('invoice_id')
          ->orWhere('invoice_id', 0);
      })
      ->whereBetween('created_at', [$from, $to])
      ->sum('hours');
  }

  /**
   * Sum uninvoiced billable hours (tenant aware wrapper).
   */
  public static function uninvoicedBillableHours(DateTimeInterface $from, DateTimeInterface $to, int $tenantId): float
  {
    return (float) static::billable()
      ->where('tenant_id', $tenantId)
      ->where(function (Builder $query) {
        $query->whereNull('invoice_id')
          ->orWhere('invoice_id', 0);
      })
      ->whereBetween('date', [$from, $to])
      ->sum('hours');
  }

  /**
   * Sum billable hours between two dates, optionally filtering by tenant.
   */
  public static function billableHoursBetween(DateTimeInterface $from, DateTimeInterface $to, ?int $tenantId = null): float
  {
    $query = static::billable()
      ->whereBetween('date', [$from, $to]);

    if ($tenantId !== null) {
      $query->where('tenant_id', $tenantId);
    }

    return (float) $query->sum('hours');
  }
}
