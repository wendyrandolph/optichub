<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// Ensure you have a LoginActivity Model defined if you use logLogin/getLoginStats
// use App\Models\LoginActivity;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  /**
   * The table associated with the model (optional if it’s “users”).
   *
   * @var string
   */
  protected $table = 'users';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'tenant_id',
    'role', // Added 'role' to fillable for easy creation
    'contact_id',
    'admin_id',
    'must_change_password',
    'hired_at',
    'can_view_registered_users',
    'can_manage_support',
  ];

  /**
   * Attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Casts for attributes.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'is_beta' => 'boolean',
    'must_change_password' => 'boolean',
    'hired_at' => 'date',
    'portal_last_seen_at' => 'datetime',
    'can_view_registered_users' => 'boolean',
    'can_manage_support' => 'boolean',
  ];

  public function isPlatformOwner(): bool
  {
    $tenantId = $this->tenant_id ?? null;
    $role     = strtolower($this->role ?? '');

    return $tenantId === (int) config('optichub.platform_tenant_id')
      && in_array($role, ['provider', 'super_admin', 'superadmin'], true);
  }

  public function isProviderAdmin(): bool
  {
    $role = strtolower((string) ($this->role ?? ''));
    $allowlist = config('provider.admin_allowlist', []);
    $emails = array_map('strtolower', $allowlist['emails'] ?? []);
    $ids = array_map('intval', $allowlist['user_ids'] ?? []);

    if (!in_array($role, ['provider', 'super_admin', 'superadmin'], true)) {
      return false;
    }

    if (!empty($emails) && in_array(strtolower((string) $this->email), $emails, true)) {
      return true;
    }

    return !empty($ids) && in_array((int) $this->getKey(), $ids, true);
  }



  // --- RELATIONSHIPS ---
  /**
   * Get the login activities for the user.
   */
  // If you prefer the name "client" instead:
  public function client()
  {
    return $this->belongsTo(Client::class, 'contact_id');
  }
  public function loginActivities(): HasMany
  {
    return $this->hasMany(ActivityLog::class, 'user_id');
  }

  public function contact()
  {
    return $this->belongsTo(Contact::class);
  }
  // nice to have
  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
  public function company()
  {
    return optional($this->contact)->company();
  } // via contact

  public function techShifts(): HasMany
  {
    return $this->hasMany(TechShift::class, 'user_id');
  }

  public function assignedTasks(): HasMany
  {
    return $this->hasMany(Task::class, 'assigned_user_id');
  }

  public function workPreference(): HasOne
  {
    return $this->hasOne(UserWorkPreference::class);
  }

  public function workPreferenceOrCreate(?Tenant $tenant = null): UserWorkPreference
  {
    $tenantId = $tenant?->id ?? $this->tenant_id;
    return UserWorkPreference::firstOrCreate(
      ['tenant_id' => $tenantId, 'user_id' => $this->id],
      [
        'weekly_target_hours' => 40,
        'working_days_json' => ['mon', 'tue', 'wed', 'thu', 'fri'],
      ]
    );
  }

  public function tradeJobTimers(): HasMany
  {
    return $this->hasMany(TradeJobTimer::class, 'user_id');
  }

  public function tradePtoRequests(): HasMany
  {
    return $this->hasMany(TradePtoRequest::class, 'user_id');
  }

  public function tradePtoBalances(): HasMany
  {
    return $this->hasMany(TradePtoBalance::class, 'user_id');
  }

  public function chatChannels(): BelongsToMany
  {
    return $this->belongsToMany(ChatChannel::class, 'chat_channel_users', 'user_id', 'channel_id')
      ->withTimestamps();
  }




  // --- SCOPES (for multi-tenancy) ---

  /**
   * Scope a query to only include users for a given tenant.
   *
   * Usage: User::forTenant(1)->get();
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @param  int $tenantId
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeForTenant(Builder $query, int $tenantId): Builder
  {
    return $query->where('tenant_id', $tenantId);
  }

  // --- FINDERS (Refactored from raw SQL) ---

  /**
   * Find a user by email. The tenant scope will automatically be applied
   * if you use a Global Scope or manually apply the local scope.
   *
   * @param string|null $email
   * @return static|null
   */
  public static function findByEmail(?string $email): ?self
  {
    if (!$email) {
      return null;
    }

    return static::where('email', $email)->first();
  }


  /**
   * Get user records by role, optionally filtered by tenant.
   *
   * @param string $role
   * @param int|null $tenantId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getByRole(string $role, ?int $tenantId = null)
  {
    $query = static::where('role', $role);

    if ($tenantId) {
      $query->forTenant($tenantId);
    }

    return $query->get();
  }


  /**
   * List all internal users (admins/employees).
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getInternalUsers()
  {
    return static::whereIn('role', ['admin', 'employee'])->get();
  }

  // --- BUSINESS LOGIC (Refactored from raw SQL) ---

  /**
   * Finds a client user by their contact_id, ensuring multi-tenancy.
   *
   * @param int $clientId
   * @param int $tenantId (Required to enforce the organization filter)
   * @return static|null
   */
  public static function findByClientId(int $clientId, int $tenantId): ?self
  {
    // Combines where clauses with the tenant scope.
    return static::where('contact_id', $clientId)
      ->where('role', 'client') // Optional: assuming contact_id implies 'client' role
      ->forTenant($tenantId)
      ->first();
  }


  /**
   * Counts clients for the current tenant.
   *
   * @param int $tenantId
   * @return int
   */
  public static function countClients(int $tenantId): int
  {
    return static::where('role', 'client')
      ->forTenant($tenantId)
      ->count();
  }


  /**
   * Checks if a user for the given contact_id exists within the tenant.
   *
   * @param int $clientId
   * @param int $tenantId
   * @return bool
   */
  public static function clientUserExists(int $clientId, int $tenantId): bool
  {
    return static::where('role', 'client')
      ->where('contact_id', $clientId)
      ->forTenant($tenantId)
      ->exists();
  }


  /**
   * Gets an array of contact_ids for all client users within the tenant.
   *
   * @param int $tenantId
   * @return array<int>
   */
  public static function getClientLogins(int $tenantId): array
  {
    // Use pluck() to get an array of values for a single column.
    return static::where('role', 'client')
      ->forTenant($tenantId)
      ->pluck('contact_id')
      ->toArray();
  }

  // --- CRUD AND UTILITY ---

  /**
   * Creates a new client user using Eloquent's create method.
   */
  public static function createClientUser(
    string $email,
    string $hashedPassword,
    int $clientId,
    int $tenantId
  ): self {
    return static::create([
      'email'                => $email,
      'password'             => $hashedPassword, // Should be pre-hashed
      'role'                 => 'client',
      'contact_id'            => $clientId,
      'tenant_id'            => $tenantId,
      'must_change_password' => true,
      'first_name'           => '', // Satisfy NOT NULL
      'last_name'            => '', // Satisfy NOT NULL
    ]);
  }

  /**
   * Creates a new admin user.
   */
  public static function createAdminUser(
    ?string $email,
    string $hashedPassword,
    int $tenantId
  ): self {
    // Fallback for required fields
    $email = trim((string)($email ?? '')) ?: 'user@trial.local';

    return static::create([
      'email'                => $email,
      'first_name'           => '',
      'last_name'            => '',
      'password'             => $hashedPassword, // Should be pre-hashed
      'role'                 => 'admin',
      'tenant_id'            => $tenantId,
      'must_change_password' => true,
    ]);
  }

  /**
   * Update a user's password.
   *
   * @param int $userId
   * @param string $hashedPassword
   * @return bool
   */
  public static function updatePassword(int $userId, string $hashedPassword): bool
  {
    return static::where('id', $userId)
      ->update([
        'password' => $hashedPassword,
        'must_change_password' => false,
      ]);
  }

  /**
   * Wrapper to hash and set the password using a static method.
   */
  public static function setPassword(int $userId, string $plain): bool
  {
    $hash = Hash::make($plain); // Use Laravel's built-in Hashing
    return static::updatePassword($userId, $hash);
  }


  /**
   * Check if the user has any login activity.
   *
   * @param int $userId
   * @return bool
   */
  public static function hasLoggedIn(int $userId): bool
  {
    // Use relationship to check existence
    $user = static::find($userId);
    return $user ? $user->loginActivities()->exists() : false;
  }

  /**
   * Logs a user login using the LoginActivity relationship.
   */
  public function logLogin(): void
  {
    // Assuming LoginActivity::create will handle 'login_time' timestamp
    $this->loginActivities()->create([]);
  }


  /**
   * Gets login statistics (total count, last time) for this user.
   *
   * @return array{total_logins: int, last_login: string|null}
   */
  public function getLoginStats(): array
  {
    $stats = $this->loginActivities()
      ->selectRaw('COUNT(*) AS total_logins, MAX(created_at) AS last_login')
      ->first();

    // Ensure the result is an array with default values if no logins exist
    return [
      'total_logins' => (int) ($stats->total_logins ?? 0),
      'last_login'   => $stats->last_login ?? null,
    ];
  }


  /**
   * Admin Update User. Needs a manual check or a policy to ensure update
   * is constrained by tenant.
   */
  public function updateAdminUser(array $data): bool
  {
    return $this->update([
      'email' => $data['email'],
      // Add other updatable fields here
    ]);
  }

  /**
   * Admin Delete User.
   */
  public function deleteAdminUser(): bool
  {
    // Deleting the model instance handles the row deletion
    return $this->delete();
  }
  public function getDisplayNameAttribute(): string
  {
    // Prefer First + Last; otherwise fall back gracefully
    $full = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    if ($full !== '') return $full;

    // Fallbacks if you sometimes still have `name`
    return $this->name ?? $this->email ?? 'User';
  }
  public function isTech(): bool
  {
    $role = strtolower((string) ($this->role ?? ''));
    return in_array($role, ['tech', 'lead_tech', 'lead tech', 'lead-tech'], true);
  }


}
