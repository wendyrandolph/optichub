<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    'username',
    'first_name',
    'last_name',
    'email',
    'password',
    'tenant_id',
    'role', // Added 'role' to fillable for easy creation
    'client_id',
    'admin_id',
    'must_change_password',
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
  ];

  // --- RELATIONSHIPS ---

  /**
   * Get the login activities for the user.
   */
  public function loginActivities(): HasMany
  {
    return $this->hasMany(ActivityLog::class, 'user_id');
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
   * Find a user by username and dynamically generate a 'name' attribute.
   * Requires 'admin_id' and 'client_id' to be set.
   *
   * @param string $username
   * @return static|null
   */
  public static function findByUsername(string $username): ?self
  {
    // NOTE: This complex join is kept here because it was in your original code.
    // In a true Laravel app, you'd likely use relations or a simpler query.
    return static::query()
      ->select('users.*')
      ->selectRaw("
                CONCAT(
                    COALESCE(admins.first_name, clients.first_name),
                    ' ',
                    COALESCE(admins.last_name, clients.last_name)
                ) AS full_name
            ")
      ->leftJoin('admins', 'users.admin_id', '=', 'admins.id')
      ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
      ->where('users.username', $username)
      ->first();
  }

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
   * Finds a client user by their client_id, ensuring multi-tenancy.
   *
   * @param int $clientId
   * @param int $tenantId (Required to enforce the organization filter)
   * @return static|null
   */
  public static function findByClientId(int $clientId, int $tenantId): ?self
  {
    // Combines where clauses with the tenant scope.
    return static::where('client_id', $clientId)
      ->where('role', 'client') // Optional: assuming client_id implies 'client' role
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
   * Checks if a user for the given client_id exists within the tenant.
   *
   * @param int $clientId
   * @param int $tenantId
   * @return bool
   */
  public static function clientUserExists(int $clientId, int $tenantId): bool
  {
    return static::where('role', 'client')
      ->where('client_id', $clientId)
      ->forTenant($tenantId)
      ->exists();
  }


  /**
   * Gets an array of client_ids for all client users within the tenant.
   *
   * @param int $tenantId
   * @return array<int>
   */
  public static function getClientLogins(int $tenantId): array
  {
    // Use pluck() to get an array of values for a single column.
    return static::where('role', 'client')
      ->forTenant($tenantId)
      ->pluck('client_id')
      ->toArray();
  }

  // --- CRUD AND UTILITY ---

  /**
   * Creates a new client user using Eloquent's create method.
   */
  public static function createClientUser(
    string $username,
    string $email,
    string $hashedPassword,
    int $clientId,
    int $tenantId
  ): self {
    return static::create([
      'username'             => $username,
      'email'                => $email,
      'password'             => $hashedPassword, // Should be pre-hashed
      'role'                 => 'client',
      'client_id'            => $clientId,
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
    string $username,
    ?string $email,
    string $hashedPassword,
    int $tenantId
  ): self {
    // Fallback for required fields
    $email = trim((string)($email ?? '')) ?: $username . '@trial.local';

    return static::create([
      'username'             => $username,
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
    // The tenant filter must be applied before calling update(), 
    // e.g., in a Policy or Controller where you fetch the user:
    // $user = User::forTenant(auth()->user()->tenant_id)->find($id);
    // $user->updateAdminUser($data);

    return $this->update([
      'username' => $data['username'],
      'email'    => $data['email'],
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

    // Fallbacks if you sometimes still have `name` or `username`
    return $this->name ?? $this->username ?? 'User';
  }


  // --- UNIQUE USERNAME HELPER (Kept logic, refactored query) ---

  /**
   * Ensures a unique username by appending a number if necessary.
   */
  public function ensureUniqueUsername(string $base): string
  {
    $base = preg_replace('/\W+/', '', strtolower($base)) ?: 'user';
    $try  = $base;
    $n = 1;
    while ($this->usernameExists($try)) {
      $try = $base . (++$n);
      if ($n > 100) {
        $try = $base . time();
        break;
      }
    }
    return $try;
  }

  private function usernameExists(string $username): bool
  {
    // Eloquent equivalent of SELECT 1 FROM users WHERE username = :u LIMIT 1
    return static::where('username', $username)->exists();
  }
}
