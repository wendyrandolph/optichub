<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for the 'proposals' table.
 * Uses HasTenantScope for internal queries but allows public access via a static method.
 */
class Proposal extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'proposals';

  protected $fillable = [
    'project_id',
    'tenant_id', // Included for the HasTenantScope trait
    'title',
    'content',
    'status', // e.g., 'draft', 'sent', 'accepted', 'rejected'
    'unique_share_token',
    'accepted_at',
  ];

  protected $casts = [
    'accepted_at' => 'datetime',
  ];

  // --- Relationships ---

  /**
   * A proposal belongs to a Project.
   * This relationship automatically uses the tenant scope defined on the Project model.
   */
  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class, 'project_id');
  }

  /**
   * A proposal belongs to an Organization (Tenant).
   */
  public function organization(): BelongsTo
  {
    return $this->belongsTo(Organization::class, 'tenant_id');
  }

  // --- Static Methods to Replace Procedural Logic ---

  /**
   * Finds a proposal using a unique, secure token for public viewing.
   * CRITICAL: This method explicitly ignores the global HasTenantScope
   * so that public users (clients) can view proposals outside of a tenant context.
   *
   * @param string $token The unique share token.
   * @return self|null The proposal model instance or null if not found.
   */
  public static function findByToken(string $token): ?self
  {
    // Use withoutGlobalScope to bypass the tenant_id check
    return static::withoutGlobalScope(HasTenantScope::class)
      ->where('unique_share_token', $token)
      ->where('status', '!=', 'draft') // Only allow viewing of sent/accepted/rejected proposals
      ->first();
  }

  // --- Instance Methods to Replace Procedural Logic ---

  /**
   * Marks the current proposal instance as accepted.
   * Replaces Proposal::markAsAccepted(int $id).
   *
   * @return bool True on success, false on failure.
   */
  public function markAsAccepted(): bool
  {
    $this->status = 'accepted';
    $this->accepted_at = now(); // Uses Laravel's now() helper
    return $this->save();
  }

  /**
   * Marks the current proposal instance as rejected.
   * Replaces Proposal::markAsRejected(int $id).
   *
   * @return bool True on success, false on failure.
   */
  public function markAsRejected(): bool
  {
    $this->status = 'rejected';
    return $this->save();
  }
}
