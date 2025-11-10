<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Eloquent Model for the 'messages' table.
 * This model does NOT use HasTenantScope. Security is enforced by checking 
 * the accessibility of the related User models (sender and receiver), 
 * which are assumed to be tenant-scoped.
 */
class Message extends Model
{
  use HasFactory;

  protected $table = 'messages';

  public $timestamps = true;

  protected $fillable = [
    'sender_id',
    'receiver_id',
    'sender_role',
    'message',
    'attachment',
    'related_task_id',
    'read_at', // Assuming a field for tracking read status
  ];

  /**
   * The attributes that should be cast to native types.
   */
  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'read_at' => 'datetime',
  ];

  // --- Relationships ---

  /**
   * The sender of the message.
   */
  public function sender(): BelongsTo
  {
    // Assumes User::class has HasTenantScope enabled
    return $this->belongsTo(User::class, 'sender_id');
  }

  /**
   * The receiver of the message.
   */
  public function receiver(): BelongsTo
  {
    // Assumes User::class has HasTenantScope enabled
    return $this->belongsTo(User::class, 'receiver_id');
  }

  // --- Core Methods ---

  /**
   * Sends a new message.
   * Ensures that both sender and receiver are accessible within the current tenant scope.
   *
   * @param array $data Message data including sender_id, receiver_id, etc.
   * @return self The created Message instance.
   * @throws ModelNotFoundException If sender or receiver user is not accessible.
   */
  public static function sendMessage(array $data): self
  {
    // 1. Security Check: Validate both users are accessible.
    // User::findOrFail() will automatically apply the tenant scope filter.
    User::findOrFail($data['sender_id']); // Throws exception if sender isn't visible/doesn't exist
    User::findOrFail($data['receiver_id']); // Throws exception if receiver isn't visible/doesn't exist

    // 2. Creation: Eloquent handles the INSERT.
    return static::create($data);
  }

  /**
   * Retrieves all messages (sent and received) for a specific user ID.
   *
   * @param int $userId The ID of the user whose messages to retrieve.
   * @return \Illuminate\Database\Eloquent\Collection An ordered collection of messages.
   * @throws ModelNotFoundException If the target user is not accessible.
   */
  public static function getMessagesForUserId(int $userId)
  {
    // 1. Security Check: Validate the target user is accessible.
    User::findOrFail($userId);

    // 2. Fetch all messages involving this user.
    return static::query()
      ->where('sender_id', $userId)
      ->orWhere('receiver_id', $userId)
      ->orderByDesc('created_at')
      ->get();
  }

  /**
   * Retrieves a message thread between two specific user IDs.
   *
   * @param int $userId The ID of the current user.
   * @param int $otherId The ID of the other user in the thread.
   * @return \Illuminate\Database\Eloquent\Collection An ordered collection of messages in the thread.
   * @throws ModelNotFoundException If either user is not accessible.
   */
  public static function getThreadBetweenUsers(int $userId, int $otherId)
  {
    // 1. Security Check: Validate both users are accessible.
    User::findOrFail($userId);
    User::findOrFail($otherId);

    // 2. Fetch the thread (messages sent from user to other, OR from other to user).
    return static::query()
      ->where(function (Builder $query) use ($userId, $otherId) {
        // Messages from User -> Other
        $query->where('sender_id', $userId)->where('receiver_id', $otherId);
      })
      ->orWhere(function (Builder $query) use ($userId, $otherId) {
        // Messages from Other -> User
        $query->where('sender_id', $otherId)->where('receiver_id', $userId);
      })
      ->orderBy('created_at', 'asc')
      ->get();
  }
}
