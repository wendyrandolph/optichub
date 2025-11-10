<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope that automatically filters records by the authenticated user's tenant.
 */
class TenantScope implements Scope
{
  /**
   * Apply the scope to a given Eloquent query builder.
   */
  public function apply(Builder $builder, Model $model): void
  {
    $user = Auth::user();

    if (!$user) {
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
