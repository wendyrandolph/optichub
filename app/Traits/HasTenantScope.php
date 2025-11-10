<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait to automatically apply the tenant-aware global scope and helpers.
 */
trait HasTenantScope
{
  /**
   * Register the TenantScope and set the tenant_id automatically when creating.
   */
  protected static function bootHasTenantScope(): void
  {
    static::addGlobalScope(new TenantScope);

    static::creating(function ($model) {
      $user = Auth::user();

      if ($user && $user->tenant_id && empty($model->tenant_id)) {
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
