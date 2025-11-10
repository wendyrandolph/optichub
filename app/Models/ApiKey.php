<?php
// app/Models/ApiKey.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ApiKey extends Model
{
  protected $table = 'api_keys';

  protected $fillable = [
    'tenant_id',
    'name',
    'key_hash',
    'key_last4',
    'status',
    'created_by',
    'revoked_at',
  ];

  protected $casts = ['revoked_at' => 'datetime'];

  public function scopeForTenant(Builder $q, int $tenantId): Builder
  {
    return $q->where('tenant_id', $tenantId);
  }

  public function scopeActive(Builder $q): Builder
  {
    return $q->where('status', 'active')->whereNull('revoked_at');
  }

  public static function listActiveByTenant(int $tenantId)
  {
    return static::query()
      ->forTenant($tenantId)
      ->active()
      ->orderByDesc('created_at')
      ->get(['id', 'name', 'key_last4', 'status', 'created_at']);
  }

  public static function issue(int $tenantId, ?string $name = null, ?int $createdBy = null): array
  {
    $plain = Str::random(40);

    $row = static::create([
      'tenant_id' => $tenantId,
      'name' => $name,
      'key_hash' => hash('sha256', $plain),
      'key_last4' => substr($plain, -4),
      'status' => 'active',
      'created_by' => $createdBy,
    ]);

    return [$row, $plain];
  }

  public static function revokeKey(int $tenantId, string|int $keyId): int
  {
    return static::query()
      ->forTenant($tenantId)
      ->whereKey($keyId)
      ->update(['status' => 'revoked', 'revoked_at' => now()]);
  }
}
