<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Eloquent Model for managing time-limited onboarding/invitation tokens.
 *
 * This model contains methods to create, find, and mark a token as used.
 */
class OnboardingToken extends Model
{
  use HasFactory;

  protected $table = 'onboarding_tokens';

  public $timestamps = true; // Uses created_at and updated_at

  protected $fillable = [
    'user_id',
    'token',
    'expires_at',
    'used_at',
  ];

  /**
   * The attributes that should be cast to native types.
   */
  protected $casts = [
    'expires_at' => 'datetime',
    'used_at' => 'datetime',
  ];

  // --- Relationships ---

  /**
   * A token belongs to a User.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  // --- Scopes ---

  /**
   * Scope to find a valid, unused, and unexpired token.
   * Replaces the logic in the procedural findValid() method.
   */
  public function scopeFindValid(Builder $query, string $token): void
  {
    $query->where('token', $token)
      // Ensure the token has not been used
      ->whereNull('used_at')
      // Ensure the token has not expired by comparing expires_at to now()
      ->where('expires_at', '>', now());
  }

  // --- Core Methods ---

  /**
   * Creates a new onboarding token.
   * Replaces the procedural create() method.
   *
   * @param int $userId The ID of the user the token is for.
   * @param int $hours The number of hours until the token expires (default 48).
   * @return string The raw token string to be sent to the user.
   */
  public static function generateToken(int $userId, int $hours = 48): string
  {
    $plainToken = Str::random(64); // Generate a secure token string

    static::create([
      'user_id' => $userId,
      'token' => $plainToken,
      // Use Carbon helper to set the expiration date
      'expires_at' => Carbon::now()->addHours($hours),
      'used_at' => null,
    ]);

    return $plainToken;
  }

  /**
   * Marks the current token instance as used.
   * Replaces the procedural markUsed() method.
   */
  public function markAsUsed(): bool
  {
    $this->used_at = now();
    return $this->save();
  }
}
