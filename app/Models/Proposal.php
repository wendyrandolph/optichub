<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Contact;
use App\Models\Lead;

/**
 * Eloquent Model for the 'proposals' table.
 * Uses HasTenantScope for internal queries but allows public access via a static method.
 */
class Proposal extends Model
{
  use HasFactory, HasTenantScope, SoftDeletes;

  protected $table = 'proposals';

  protected $fillable = [
    'project_id',
    'client_id',
    'contract_id',
    'tenant_id', // Included for the HasTenantScope trait
    'recipient_type',
    'contact_id',
    'lead_id',
    'title',
    'summary',
    'goals',
    'deliverables',
    'total_investment',
    'maintenance_plan',
    'payment_policy',
    'timeline',
    'next_steps',
    'content',
    'status', // e.g., 'draft', 'sent', 'accepted', 'declined'
    'unique_share_token',
    'public_token',
    'accepted_at',
    'declined_at',
    'sent_at',
    'approved_at',
    'approved_pdf_path',
  ];

  protected $casts = [
    'accepted_at' => 'datetime',
    'declined_at' => 'datetime',
    'sent_at' => 'datetime',
    'approved_at' => 'datetime',
    'content' => 'array',
    'goals' => 'array',
    'deliverables' => 'array',
    'maintenance_plan' => 'array',
    'timeline' => 'array',
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

  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class, 'client_id');
  }

  public function contact(): BelongsTo
  {
    return $this->belongsTo(Contact::class, 'contact_id');
  }

  public function lead(): BelongsTo
  {
    return $this->belongsTo(Lead::class, 'lead_id');
  }

  /**
   * A proposal belongs to an Organization (Tenant).
   */
  public function organization(): BelongsTo
  {
    return $this->belongsTo(Tenant::class, 'tenant_id');
  }

  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class, 'tenant_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(ProposalItem::class)->orderBy('sort_order');
  }

  public function goalItems(): HasMany
  {
    return $this->items()->where('type', 'goal');
  }

  public function deliverableItems(): HasMany
  {
    return $this->items()->where('type', 'deliverable');
  }

  public function acceptance(): HasOne
  {
    return $this->hasOne(ProposalAcceptance::class)->latestOfMany();
  }

  public function contract(): BelongsTo
  {
    return $this->belongsTo(Contract::class, 'contract_id');
  }

  public function signatures(): HasMany
  {
    return $this->hasMany(ProposalSignature::class);
  }

  public function paymentScheduleItems(): HasMany
  {
    return $this->hasMany(ProposalPaymentSchedule::class)
      ->withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('tenant_id', $this->tenant_id)
      ->orderBy('sort_order');
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
    $this->status = 'declined';
    $this->declined_at = now();
    return $this->save();
  }

  public function statusLabel(): string
  {
    return ucfirst(str_replace('_', ' ', (string) $this->status));
  }

  public function statusPillClass(): string
  {
    return match (strtolower((string) $this->status)) {
      'sent' => 'oh-pill oh-pill--info',
      'accepted' => 'oh-pill oh-pill--success',
      'approved' => 'oh-pill oh-pill--success',
      'rejected', 'declined' => 'oh-pill oh-pill--danger',
      default => 'oh-pill',
    };
  }

  public function recipientName(): string
  {
    $contact = $this->contact ?: $this->client;
    if ($contact) {
      $name = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));
      return $name !== '' ? $name : ($contact->email ?? '—');
    }
    if ($this->lead) {
      $leadName = trim((string) ($this->lead->name ?? ''));
      return $leadName !== '' ? $leadName : ($this->lead->email ?? '—');
    }
    return '—';
  }
}
