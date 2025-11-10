<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model for the 'contact_submissions' table.
 * Replaces the procedural ContactSubmissionModel.
 * This model does NOT use HasTenantScope as it is for public-facing data.
 */
class ContactSubmission extends Model
{
  use HasFactory;

  protected $table = 'contact_submissions';

  // Define the columns that can be mass-assigned (based on the original SQL)
  protected $fillable = [
    'name',
    'email',
    'topic',
    'message',
    'ip_address', // Captured server-side
    'user_agent', // Captured server-side
  ];

  // Disable default timestamps if your table doesn't have `created_at` / `updated_at`.
  // If your table has a `created_at` column but not `updated_at`, set this to false:
  // public $timestamps = true; 

  /**
   * Records a new contact form submission into the database, mirroring the
   * functionality of the procedural record() method by enriching the data.
   *
   * NOTE: In a robust application, capturing $_SERVER data should ideally happen 
   * in the Controller or a dedicated Service, not the Model.
   *
   * @param array $data Associative array containing form fields.
   * @return int|null The ID of the new submission or null on failure.
   */
  public static function record(array $data): ?int
  {
    // 1. Augment data with server information, replicating the procedural logic
    $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
    $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // 2. Eloquent handles insertion
    try {
      $submission = static::query()->create($data);
      // 3. Return the ID
      return $submission->id;
    } catch (\Throwable $e) {
      // Log the error and return null (replaces returning false on failure)
      report($e);
      return null;
    }
  }
}
