<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a custom application notification.
 * Access is secured globally by the HasTenantScope trait.
 */
class Notification extends Model
{
  use HasFactory, HasTenantScope; // Use the scope to filter by tenant_id automatically

  protected $table = 'notifications';

  protected $fillable = [
    'user_id',
    'tenant_id',
    'type',
    'data', // JSON payload for dynamic content
    'read_at',
  ];

  protected $casts = [
    'data' => 'array',
    'read_at' => 'datetime',
  ];

  // --- RELATIONSHIPS ---

  /**
   * The user this notification is intended for.
   */
  public function user(): BelongsTo
  {
    // The User model should also be tenant-scoped
    return $this->belongsTo(User::class);
  }

  // --- SCOPES (For convenient querying) ---

  /**
   * Scope a query to only include unread notifications.
   */
  public function scopeUnread(Builder $query): Builder
  {
    return $query->whereNull('read_at');
  }

  /**
   * Scope a query to only include notifications for a specific user.
   */
  public function scopeForUser(Builder $query, int $userId): Builder
  {
    return $query->where('user_id', $userId);
  }

  /**
   * Scope a query to retrieve the most recent notifications first.
   */
  public function scopeRecent(Builder $query): Builder
  {
    return $query->latest();
  }

  // --- BUSINESS LOGIC ---

  /**
   * Marks the notification as read.
   *
   * @return bool
   */
  public function markAsRead(): bool
  {
    if ($this->read_at === null) {
      $this->read_at = now();
      return $this->save();
    }
    return true;
  }

  /**
   * Marks the notification as unread (optional, for toggling).
   *
   * @return bool
   */
  public function markAsUnread(): bool
  {
    if ($this->read_at !== null) {
      $this->read_at = null;
      return $this->save();
    }
    return true;
  }

  /**
   * Quickly retrieve all unread notifications for a specific user.
   * The tenant scope prevents unauthorized access automatically.
   *
   * @param int $userId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getUnreadForUser(int $userId)
  {
    return static::forUser($userId)
      ->unread()
      ->recent()
      ->get();
  }
}
