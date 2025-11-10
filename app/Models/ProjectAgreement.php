<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for the 'project_agreements' table.
 * All queries are automatically filtered by the current user's organization (tenant_id)
 * using the HasTenantScope trait.
 */
class ProjectAgreement extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'project_agreements';

  protected $fillable = [
    'project_id',
    'tenant_id', // Included for the HasTenantScope trait
    'is_signed',
    'signed_date',
    'signed_by',
    'agreed_cost',
    'wants_maintenance',
  ];

  protected $casts = [
    'is_signed' => 'boolean',
    'wants_maintenance' => 'boolean',
    'signed_date' => 'date', // Casts to a Carbon instance
    'agreed_cost' => 'float',
  ];

  // Disable default created_at/updated_at if your table doesn't use them,
  // though the procedural code included updated_at=NOW(), so we'll keep them enabled.

  // --- Relationships ---

  /**
   * An agreement belongs to a Project.
   */
  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class, 'project_id');
  }

  /**
   * An agreement belongs to an Organization (Tenant).
   */
  public function organization(): BelongsTo
  {
    return $this->belongsTo(Organization::class, 'tenant_id');
  }

  // --- Replacement for addAgreementDetails ---

  /**
   * Static helper to add or update agreement details.
   * This replaces the manual check for existing records and uses Eloquent's built-in feature.
   *
   * @param int $projectId The ID of the project.
   * @param array $details The agreement data.
   * @param int $organizationId The ID of the organization (taken from the project).
   * @return self The created or updated ProjectAgreement model instance.
   */
  public static function saveAgreementDetails(int $projectId, array $details, int $organizationId): self
  {
    // 1. Define the attributes to match against (Project ID and Tenant ID)
    $attributes = [
      'project_id' => $projectId,
      'tenant_id' => $organizationId,
    ];

    // 2. Add the remaining details to be updated/inserted
    $values = array_merge($details, [
      'is_signed' => $details['isSigned'] ?? false,
      'signed_date' => $details['signedDate'] ?? null,
      'signed_by' => $details['signedBy'] ?? null,
      'agreed_cost' => $details['agreedCost'] ?? 0.00,
      'wants_maintenance' => $details['wantsMaintenance'] ?? false,
    ]);

    // 3. Use updateOrCreate, which handles the entire insert/update logic in one query.
    // NOTE: This relies on the controller ensuring the Project ID belongs to the tenant.
    return static::updateOrCreate($attributes, $values);
  }
}
