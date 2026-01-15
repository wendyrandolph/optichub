<?php

namespace App\Models;

use App\Scopes\TenantScope; // <-- 1. Import your custom scope
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Project extends Model
{
  use HasFactory;

  // Define the fillable fields for mass assignment
  protected $fillable = [
    'tenant_id',
    'client_company_id',
    'opportunity_id',
    'contact_id',
    'project_manager_id',
    'project_name',
    'status',
    'description',
    'color',
    'start_date',
    'end_date',
    'uses_phases',
    'budgeted_hours',
    'slug',
  ];


  // --------------------------------------------------------
  // ** NEW: Apply the Global TenantScope **
  // --------------------------------------------------------
  /**
   * The "booted" method of the model.
   * Apply the TenantScope globally on every query.
   */
  protected static function booted(): void
  {
    static::addGlobalScope(new TenantScope);
    static::creating(function ($project) {
      if (empty($project->slug)) {
        $baseSlug = Str::slug($project->project_name);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure uniqueness per tenant
        while (self::where('tenant_id', $project->tenant_id)
          ->where('slug', $slug)
          ->exists()
        ) {
          $slug = "{$baseSlug}-{$counter}";
          $counter++;
        }

        $project->slug = $slug;
      }
    });
  }
  // --------------------------------------------------------

  /**
   * Get the user who owns/manages the project.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function client(): BelongsTo
  {
    // maintain legacy method name but map to the new contact column
    return $this->belongsTo(Contact::class, 'contact_id');
  }

  // nice to have
  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }
  /**
   * Get the client user associated with the project.
   */

  public function messages()
  {
    return $this->hasMany(\App\Models\ProjectMessage::class)->latest();
  }

  // app/Models/Project.php
  public function conversation()
  {
    return $this->hasOne(\App\Models\ProjectConversation::class);
  }

  public function clientUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'client_user_id');
  }

  public function opportunity(): BelongsTo
  {
    return $this->belongsTo(Opportunity::class);
  }

  public function scopeForTenant(Builder $query, int|string $tenantId): Builder
  {
    // This line filters the projects table where the tenant_id matches the one provided
    return $query->where('tenant_id', $tenantId);
  }

  public function scopeRecentlyUpdated(Builder $query): Builder
  {
    // Orders the projects by the 'updated_at' timestamp, descending (newest first)
    return $query->orderBy('updated_at', 'desc');
  }

  public function scopeStale(Builder $query): Builder
  {
    // Stale defined as not updated in the last 90 days.
    $staleDate = Carbon::now()->subDays(90);

    return $query->where('updated_at', '<', $staleDate);
  }

  public static function countStale(): int
  {
    // We call the local scope 'stale' and then count the results.
    return self::stale()->count();
  }
  // app/Models/Project.php
  public function phases()
  {
    return $this->hasMany(Phase::class);
  }
  public function company()
  {
    return $this->belongsTo(ClientCompany::class, 'client_company_id');
  }
  public function contact()
  {
    return $this->belongsTo(Contact::class);
  }

  public function agreements()
  {
    return $this->hasMany(\App\Models\ProjectAgreement::class);
  }

  public function payments()
  {
    return $this->hasMany(\App\Models\ProjectPayment::class);
  }

  public function tasks(): HasMany
  {
    return $this->hasMany(Task::class);
  }
}
