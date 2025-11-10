<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DateTimeInterface;


/**
 * Eloquent Model for the 'leads' table.
 * Replaces the procedural Lead class, leveraging Eloquent and tenancy features.
 */
class Lead extends Model
{
  // Use the Factory and the tenant scoping trait for automatic multi-tenancy filtering
  use HasFactory, HasTenantScope;

  protected $table = 'leads';

  // The fields that can be mass-assigned (and will be saved via create/update).
  // Note: Assuming 'lead_name' in your old code is simply 'name' in the database
  // and 'organization_id' is 'tenant_id' for consistency with the HasTenantScope trait.
  protected $fillable = [
    'tenant_id',
    'name',
    'first_name',
    'last_name',
    'email',
    'phone',
    'status',
    'source',
  ];

  /**
   * Relationship to the owning Organization (Tenant).
   */
  public function organization()
  {
    // Assuming Organization is the model for the tenant
    return $this->belongsTo(Tenant::class, 'tenant_id');
  }
  public function owner()
  {
    return $this->belongsTo(\App\Models\User::class, 'owner_id');
  }

  // --- Core Analytical/Counting methods replacing old procedural logic ---

  /**
   * Replaces countLeads(): Count leads visible to the current tenant/user.
   * The HasTenantScope trait handles the WHERE tenant_id filter.
   */
  public static function countLeads(): int
  {
    return static::count();
  }

  /**
   * Replaces countLeadsThisWeek(): Count leads created this week.
   */
  public static function countLeadsThisWeek(): int
  {
    return static::whereBetween(
      'created_at',
      [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]
    )->count();
  }

  /**
   * Replaces recentLeads(): Fetch the 5 most recent leads.
   */
  public static function recentLeads(): \Illuminate\Database\Eloquent\Collection
  {
    return static::latest()->limit(5)->get();
  }

  /**
   * Replaces countByStatus(string $status): Count leads by a specific status.
   */
  public static function countByStatus(string $status): int
  {
    return static::where('status', $status)->count();
  }

  /**
   * Replaces countCreatedBetween($from, $to): Count leads created between two dates.
   */
  public static function countCreatedBetween(DateTimeInterface $from, DateTimeInterface $to): int
  {
    return static::whereBetween('created_at', [$from, $to])->count();
  }

  /**
   * Replaces pipelineWtd($from, $to): Generate the status pipeline count.
   * The old logic's manual Admin bypass is removed, relying entirely on the scope.
   */
  public static function pipelineWtd(DateTimeInterface $from, DateTimeInterface $to): array
  {
    // Group by status and return an associative array of ['status' => count]
    return static::selectRaw('status, COUNT(*) as count')
      ->whereBetween('created_at', [$from, $to])
      ->groupBy('status')
      ->pluck('count', 'status')
      ->toArray();
  }

  /**
   * Replaces updateStatus(int $id, string $newStatus) functionality for a single instance.
   * @param string $newStatus
   * @return bool
   */
  public function updateStatus(string $newStatus): bool
  {
    // Called on an existing model instance, e.g., Lead::find(1)->updateStatus('New')
    return $this->update(['status' => $newStatus]);
  }
}
