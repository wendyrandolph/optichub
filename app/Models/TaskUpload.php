<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a file upload associated with a task/client.
 * Access is secured via the client relationship.
 */
class TaskUpload extends Model
{
  use HasFactory;

  protected $table = 'task_uploads';

  protected $fillable = [
    'task_id',
    'client_id', // Assuming this foreign key exists
    'file_name',
    'file_path',
    'uploaded_by_user_id',
    'mime_type',
    'size',
  ];

  // --- RELATIONSHIPS ---

  /**
   * The task this upload belongs to.
   */
  public function task(): BelongsTo
  {
    // The Task model should also be tenant-scoped
    return $this->belongsTo(Task::class);
  }

  /**
   * The client this upload is secured by.
   */
  public function client(): BelongsTo
  {
    // The Client model MUST use the HasTenantScope
    return $this->belongsTo(Client::class);
  }

  // --- QUERY METHODS ---

  /**
   * Retrieves task uploads associated with a specific client ID.
   * Security is automatically applied because the whereHas('client')
   * query triggers the HasTenantScope on the Client model.
   *
   * @param int $clientId The ID of the client to retrieve uploads for.
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getByClient(int $clientId)
  {
    // 1. Filter by the specific client ID
    return static::where('client_id', $clientId)
      // 2. Crucial Security Step: Use whereHas to ensure the client
      // exists AND belongs to the current tenant (via Client's HasTenantScope).
      ->whereHas('client')
      ->get();
  }

  /**
   * Retrieves a single upload by ID, secured via the associated Client.
   * This is the Eloquent equivalent of the complex join/filter in the procedural example.
   */
  public static function getSecuredById(int $id): ?TaskUpload
  {
    return static::where('id', $id)
      // Ensure the parent client belongs to the current organization
      ->whereHas('client')
      ->first();
  }
}
