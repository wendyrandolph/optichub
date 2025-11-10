<?php

namespace App\Models;

use App\Traits\HasTenantScope; // Consistent with other tenant-scoped models
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent Model for the 'events' table, used for internal activity logging.
 * Uses HasTenantScope for automatic tenant filtering.
 */
class EventLog extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'events';

  // Laravel uses created_at and updated_at by default
  public $timestamps = true;

  protected $fillable = [
    'tenant_id',
    'type',
    'payload', // This will be stored as JSON
  ];

  /**
   * The attributes that should be cast.
   * Eloquent automatically handles serializing the array to JSON when saving 
   * and deserializing it to a PHP array when retrieving.
   */
  protected $casts = [
    'payload' => 'array',
  ];

  // --- Static Methods ---

  /**
   * Records a new event log entry.
   * Replaces the procedural record() method.
   *
   * @param int $tenantId The ID of the organization to scope the event to.
   * @param string $type The type of event (e.g., 'task_created').
   * @param array $payload The event data.
   */
  public static function recordEvent(int $tenantId, string $type, array $payload): void
  {
    self::create([
      'tenant_id' => $tenantId,
      'type'      => $type,
      'payload'   => $payload,
    ]);
  }

  // --- Query Scopes ---

  /**
   * Scope a query to include events with an ID greater than the given ID.
   * Replaces the procedural 'since' filtering.
   * Usage: EventLog::listSince(123)->get()
   */
  public function scopeListSince(Builder $query, int $sinceId): Builder
  {
    return $query->where('id', '>', $sinceId)
      ->orderBy('id', 'asc');
  }
}
