<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use App\Enums\OrganizationType;
// use App\Enums\TrialStatus;

class Tenant extends Model
{
  use HasFactory;

  // 1) Point to the correct table
  protected $table = 'tenants';

  // 2) Allow mass assignment (include slug)
  protected $fillable = [
    'type',
    'name',
    'industry',
    'location',
    'website',
    'phone',
    'notes',
    'trial_started_at',
    'trial_ends_at',
    'beta_until',
    'trial_status',
    'subscription_status',
    'trial_source',
    'trial_convert_date',
    'onboarded_at',
  ];

  // 3) Casts (uncomment enums if you actually have them)
  protected $casts = [
    //'type'               => OrganizationType::class,
    'trial_started_at'   => 'datetime',
    'trial_ends_at'      => 'datetime',
    'beta_until'         => 'datetime',
    // 'trial_status'       => TrialStatus::class,
    'trial_convert_date' => 'datetime',
    'onboarded_at'       => 'datetime',
    'created_at'         => 'datetime',
    'updated_at'         => 'datetime',
  ];

  // 4) Route model binding by id
  public function getRouteKeyName(): string
  {
    return 'id';
  }




  // 5) Relationships
  public function users(): HasMany
  {
    return $this->hasMany(User::class, 'tenant_id');
  }
  public function clients(): HasMany
  {
    return $this->hasMany(Client::class, 'tenant_id');
  }
  public function projects(): HasMany
  {
    return $this->hasMany(Project::class, 'tenant_id');
  }
  public function opportunities(): HasMany
  {
    return $this->hasMany(Opportunity::class, 'tenant_id');
  }
  public function leads(): HasMany
  {
    return $this->hasMany(Lead::class, 'tenant_id');
  }
  public function mailSetting()
  {
    return $this->hasOne(\App\Models\TenantMailSetting::class);
  }





  public function getTrialInfo(): ?array
  {
    if (!$this->trial_started_at || !$this->trial_ends_at) {
      return null;
    }
    return [
      'started_at' => $this->trial_started_at,
      'ends_at'    => $this->trial_ends_at,
      'days_left'  => $this->days_left_in_trial,
    ];
  }
  public function getTenantAccessStatus(): ?array
  {
    // Example logic; adjust based on your actual access control implementation
    $now = now();
    $isTrialing = $this->subscription_status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    $hasActiveSubscription = in_array($this->subscription_status, ['active', 'past_due'], true);

    return [
      'is_trialing'            => $isTrialing,
      'has_active_subscription' => $hasActiveSubscription,
      'access_granted'         => $isTrialing || $hasActiveSubscription,
    ];
  }
  // 6) Accessors
  public function getDaysLeftInTrialAttribute(): ?int
  {
    if (!$this->trial_ends_at) return null;
    return max(0, now()->diffInDays($this->trial_ends_at, false));
  }

  // 7) Scopes
  public function scopeIsTrialing($q)
  {
    // If you later switch to an enum, update this accordingly
    return $q->where('subscription_status', 'trialing');
  }
}
