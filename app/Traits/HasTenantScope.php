<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Trait to automatically apply the tenant-aware global scope and helpers.
 */
trait HasTenantScope
{
  /**
   * Cache for table -> has tenant_id column lookups.
   *
   * @var array<string, bool>
   */
  protected static array $tenantIdColumnCache = [];

  protected static function tableHasTenantId(string $table): bool
  {
    if (!array_key_exists($table, static::$tenantIdColumnCache)) {
      static::$tenantIdColumnCache[$table] = Schema::hasColumn($table, 'tenant_id');
    }

    return static::$tenantIdColumnCache[$table];
  }

  /**
   * Register the TenantScope and set the tenant_id automatically when creating.
   */
  protected static function bootHasTenantScope(): void
  {
    $instance = new static();
    if (static::tableHasTenantId($instance->getTable())) {
      static::addGlobalScope(new TenantScope);
    }

    static::creating(function ($model) {
      $user = Auth::user();

      if ($user && $user->tenant_id && empty($model->tenant_id) && static::tableHasTenantId($model->getTable())) {
        $model->tenant_id = $user->tenant_id;
      }
    });
  }

  /**
   * Allows bypassing the tenant scope (e.g., for superadmins fetching all data).
   */
  public static function withAnyTenant(): Builder
  {
    return (new static())->newQueryWithoutScope(TenantScope::class);
  }
}
