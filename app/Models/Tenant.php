<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TradePtoType;

// use App\Enums\OrganizationType;
// use App\Enums\TrialStatus;

class Tenant extends Model
{
  use HasFactory;
  use \App\Traits\FormatsPhone;
  protected $guarded = [];
  // 1) Point to the correct table
  protected $table = 'tenants';
  protected $primaryKey = 'id';
  // 2) Allow mass assignment (include slug)
  protected $fillable = [
    'type',
    'workspace_type',
    'name',
    'industry',
    'location',
    'website',
    'phone',
    'notes',
    'support_email',
    'business_type',
    'default_uses_phases',
    'primary_color',
    'secondary_color',
    'accent_color',
    'logo_path',
    'brand_tagline',
    'invoice_footer',
    'timezone',
    'trial_started_at',
    'trial_ends_at',
    'trial_status',
    'subscription_status',
    'client_type_preference',
    'default_client_type',
    'client_type_prompt',
    'onboarding_completed_at',
    'allow_partial_payments',
    'pricing_visible_to_techs',
    'reminders_enabled',
    'reminder_offsets',
    'trades_recurring_enabled',
    'trades_warranty_scope',
    'trades_work_type',
    'inbox_key',
    'lead_notification_recipients',
    'lead_field_mapping',
    'pto_approver_id',
    'pto_backup_approver_id',
    'overtime_enabled',
    'overtime_daily_hours',
    'overtime_weekly_hours',
    // and anything else you legitimately need to mass assign
  ];

  // 3) Casts (uncomment enums if you actually have them)
  protected $casts = [
    //'type'               => OrganizationType::class,
    'trial_started_at'   => 'datetime',
    'trial_ends_at'      => 'datetime',
    'beta_until'         => 'datetime',
    // 'trial_status'       => TrialStatus::class,
    'trial_convert_date' => 'datetime',
    'onboarding_completed_at' => 'datetime',
    'onboarded_at'       => 'datetime',
    'created_at'         => 'datetime',
    'updated_at'         => 'datetime',
    'tax_enabled'    => 'bool',
    'tax_inclusive'  => 'bool',
    'allow_partial_payments' => 'bool',
    'pricing_visible_to_techs' => 'bool',
    'reminders_enabled' => 'bool',
    'reminder_offsets' => 'array',
    'trades_recurring_enabled' => 'bool',
    'trades_warranty_scope' => 'string',
    'trades_work_type' => 'string',
    'lead_notification_recipients' => 'array',
    'lead_field_mapping' => 'array',
    'pto_approver_id' => 'int',
    'pto_backup_approver_id' => 'int',
    'overtime_enabled' => 'bool',
    'overtime_daily_hours' => 'float',
    'overtime_weekly_hours' => 'float',
  ];

  // 4) Route model binding by id
  public function getRouteKeyName(): string
  {
    return 'id';
  }

  public function getPhoneFormattedAttribute(): ?string
  {
    return $this->formatPhone($this->attributes['phone'] ?? null);
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
  public function serviceLocations(): HasMany
  {
    return $this->hasMany(ServiceLocation::class, 'tenant_id');
  }
  public function tradeJobs(): HasMany
  {
    return $this->hasMany(TradeJob::class, 'tenant_id');
  }
  public function tradeServicePlans(): HasMany
  {
    return $this->hasMany(TradeServicePlan::class, 'tenant_id');
  }
  public function tradePtoTypes(): HasMany
  {
    return $this->hasMany(TradePtoType::class, 'tenant_id');
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

  public function invoices()
  {
    return $this->hasMany(Invoice::class, 'tenant_id');
  }
  public function taxRates()
  {
    return $this->hasMany(TaxRate::class);
  }



  public function subscriptions()
  {
    return $this->hasMany(\App\Models\Subscription::class);
  }
  public function currentSubscription()
  {
    return $this->hasOne(\App\Models\Subscription::class)
      ->latestOfMany('id'); // or a proper "active" scope if you keep history
  }



  public function getTenantAccessStatus(): ?array
  {
    $sub = $this->currentSubscription;
    $now = now();

    if ($sub) {
      $isTrialing = $sub->status === 'trialing' && $sub->trial_ends_at && $sub->trial_ends_at->isFuture();
      $hasActive  = in_array($sub->status, ['active', 'past_due'], true);

      return [
        'is_trialing'             => $isTrialing,
        'has_active_subscription' => $hasActive,
        'access_granted'          => $isTrialing || $hasActive,
        'trial_ends_at'           => $sub->trial_ends_at,
      ];
    }

    // Fallback to your existing fields (keeps older code working)
    $isTrialing = $this->subscription_status === 'trialing'
      && $this->trial_ends_at
      && $this->trial_ends_at->isFuture();

    $hasActive = in_array($this->subscription_status, ['active', 'past_due'], true);

    return [
      'is_trialing'             => $isTrialing,
      'has_active_subscription' => $hasActive,
      'access_granted'          => $isTrialing || $hasActive,
      'trial_ends_at'           => $this->trial_ends_at,
    ];
  }


  public function getTrialInfo(): ?array
  {
    $sub = $this->currentSubscription;

    if ($sub && $sub->trial_ends_at) {
      return [
        'started_at' => $sub->created_at,
        'ends_at'    => $sub->trial_ends_at,
        'days_left'  => max(0, now()->diffInDays($sub->trial_ends_at, false)),
      ];
    }

    // Fallback to existing fields
    if (!$this->trial_started_at || !$this->trial_ends_at) return null;

    return [
      'started_at' => $this->trial_started_at,
      'ends_at'    => $this->trial_ends_at,
      'days_left'  => $this->days_left_in_trial,
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

  //Helper file for Tenant Colors 
  public function brandColorRgb(string $key): string
  {
    // map logical key → column name
    $column = match ($key) {
      'primary'   => 'primary_color',
      'secondary' => 'secondary_color',
      'accent'    => 'accent_color',
      default     => null,
    };

    // Defaults (match your app.css tokens)
    $defaults = [
      'primary'   => '39 56 104',   // #273868
      'secondary' => '95 180 168',  // #5FB4A8
      'accent'    => '234 125 81',  // #EA7D51
    ];

    // If we have a custom color for this tenant, convert hex → "r g b"
    if ($column && !empty($this->{$column})) {
      $hex = ltrim($this->{$column}, '#');

      // support short hex like #abc
      if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
      }

      if (strlen($hex) === 6) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r} {$g} {$b}";
      }
    }

    // fallback to Renlo defaults
    return $defaults[$key] ?? '0 0 0';
  }

  public function brandColorHex(string $key): string
  {
    $column = match ($key) {
      'primary'   => 'primary_color',
      'secondary' => 'secondary_color',
      'accent'    => 'accent_color',
      default     => null,
    };

    $defaults = [
      'primary'   => '#273868',
      'secondary' => '#5FB4A8',
      'accent'    => '#EA7D51',
    ];

    if ($column && !empty($this->{$column})) {
      $hex = ltrim($this->{$column}, '#');
      if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
      }
      return '#' . substr($hex, 0, 6);
    }

    return $defaults[$key] ?? '#273868';
  }

  public function clientCompanies()
  {
    return $this->hasMany(ClientCompany::class);
  }
}
