<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// CRITICAL: Need to import the Authenticatable base class for a login model
use Illuminate\Foundation\Auth\User as Authenticatable;
// Assuming you have this Enum defined
use App\Enums\TeamRole;


/**
 * Class Admin
 * * This model represents a user record used for back-office authentication 
 * and authorization, corresponding to a person in the 'team_members' table.
 */
class Admin extends Authenticatable
{
  use HasFactory;

  /**
   * The table associated with the model.
   * We are using 'team_members' as per your provided configuration.
   *
   * @var string
   */
  protected $table = 'team_members';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'firstName',
    'lastName',
    'email',
    'phone',
    'password', // Ensure password is fillable if you are creating admins
    'role',
    'title',
    'notes',
    'tenant_id',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'tenant_id' => 'integer',
    'role'      => TeamRole::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'email_verified_at' => 'datetime', // Add if admins use email verification
  ];

  // --- RELATIONSHIPS ---

  /**
   * An Admin belongs to a Tenant (Organization).
   */
  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }

  // --- SCOPES (Multi-tenancy and Role Filtering) ---

  /**
   * Scope a query to only include admins for a given tenant.
   */
  public function scopeForTenant(Builder $query, int $tenantId): Builder
  {
    return $query->where('tenant_id', $tenantId);
  }

  /**
   * Scope a query to only include admins with a specific internal role.
   */
  public function scopeHasRole(Builder $query, TeamRole $role): Builder
  {
    return $query->where('role', $role);
  }
}
