<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent Model for the 'client_companies' table.
 * Replaces the procedural ClientCompany class, leveraging Eloquent and tenancy features.
 */
class ClientCompany extends Model
{
  // Use the Factory and the tenant scoping trait for automatic multi-tenancy filtering
  use HasFactory, HasTenantScope;

  protected $table = 'client_companies';

  // The fields that can be mass-assigned. 
  // The tenant_id will be automatically populated via the HasTenantScope trait.
  protected $fillable = [
    'tenant_id',
    'company_name',
    'industry',
    'website',
    'phone',
    'address',
    'notes',
  ];

  /**
   * Define the relationship to the organization (tenant).
   */
  public function organization()
  {
    return $this->belongsTo(Organization::class, 'tenant_id');
  }

  // --- Explicit Static Wrappers to Match Legacy API ---

  /**
   * Replaces the procedural getAll($limit, $offset) method using Eloquent.
   * The HasTenantScope trait automatically filters by tenant_id.
   */
  public static function getAll(?int $limit = null, ?int $offset = null): Collection
  {
    $query = static::orderBy('company_name', 'asc');

    if ($limit !== null) {
      $query->limit($limit);
    }

    if ($offset !== null) {
      $query->offset($offset);
    }

    return $query->get();
  }

  /**
   * Replaces the procedural getById($id) method using Eloquent.
   * find() automatically respects the HasTenantScope.
   */
  public static function getById(int $id): ?self
  {
    return static::find($id);
  }

  /**
   * Replaces the procedural create($data) method, returning the ID of the new record.
   */
  public static function createCompany(array $data): int
  {
    // The HasTenantScope trait automatically sets the tenant_id before saving.
    $company = static::query()->create($data);
    return $company->id;
  }

  /**
   * Replaces the procedural update($id, $data) method.
   */
  public static function updateCompany(int $id, array $data): bool
  {
    // Find the company (tenant scoped) and update it.
    $company = static::find($id);

    if (!$company) {
      return false;
    }

    return $company->update($data);
  }

  /**
   * Replaces the procedural delete($id) method.
   */
  public static function deleteCompany(int $id): bool
  {
    // Find the company (tenant scoped) and delete it.
    $company = static::find($id);

    if (!$company) {
      return false;
    }

    return (bool)$company->delete();
  }
}
