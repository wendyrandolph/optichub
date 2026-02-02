<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Global scope that automatically filters records by the authenticated user's tenant.
 */
class TenantScope implements Scope
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
   * Apply the scope to a given Eloquent query builder.
   */
  public function apply(Builder $builder, Model $model): void
  {
    $user = Auth::user();

    if (!$user) {
      return;
    }

    if (!static::tableHasTenantId($model->getTable())) {
      return;
    }

    // Providers (super admins) can see all tenants.
    if (($user->role ?? null) === 'provider') {
      return;
    }

    if ($user->tenant_id) {
      $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
    }
  }
}
