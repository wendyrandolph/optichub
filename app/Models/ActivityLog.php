<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
  protected $table = 'activity_logs';

  protected $fillable = [
    'tenant_id',
    'user_id',
    'related_type',
    'related_id',
    'action',
    'description',
    'properties',
  ];

  protected $casts = [
    'properties' => 'array',
  ];

  // --- Relationships ---
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function related()
  {
    return $this->morphTo(null, 'related_type', 'related_id');
  }

  // --- Scopes ---
  public function scopeForTenant(Builder $q, int $tenantId): Builder
  {
    return $q->where('tenant_id', $tenantId);
  }

  public function scopeRecent(Builder $q, int $limit = 100): Builder
  {
    return $q->latest()->limit($limit);
  }

  // --- Convenience: create from context ---
  public static function record(
    int $tenantId,
    ?int $userId,
    Model $subject,
    string $action,
    ?string $description = null,
    array $properties = []
  ): self {
    return self::create([
      'tenant_id'    => $tenantId,
      'user_id'      => $userId,
      'related_type' => get_class($subject),
      'related_id'   => $subject->getKey(),
      'action'       => $action,
      'description'  => $description,
      'properties'   => $properties,
    ]);
  }
}
