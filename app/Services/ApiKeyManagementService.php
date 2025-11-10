<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class ApiKeyManagementService
{
  /**
   * Hashing is handled by Laravel's Hash facade for consistency,
   * but we use SHA-256 for resolution compatibility with the original service.
   */
  protected function hashToken(string $plain): string
  {
    return hash('sha256', $plain);
  }

  /**
   * Issue a new API key for an organization and return the *plain* token.
   * Replaces the issue() method in the old helper.
   */
  public function issue(int $tenantId, string $name = 'CLI Key', array $scopes = [], ?Carbon $expiresAt = null): string
  {
    $token = Str::random(64); // Generates a 64-char hex string
    $hash = $this->hashToken($token);

    ApiKey::create([
      'tenant_id' => $tenantId,
      'name' => $name,
      'token_hash' => $hash,
      'scopes' => $scopes,
      'status' => 'active',
      'expires_at' => $expiresAt,
      'created_at' => Carbon::now(),
    ]);

    return $token; // MUST return the unhashed token for the user
  }

  /**
   * Resolve a presented *plain* token into the ApiKey model instance, or null.
   * Replaces the resolve() method in the old helper.
   */
  public function resolve(string $token): ?ApiKey
  {
    $hash = $this->hashToken($token);

    /** @var ApiKey|null $key */
    $key = ApiKey::query()
      ->byTokenHash($hash)
      ->first();

    if (!$key) {
      return null;
    }

    // Check status and expiration
    if ($key->status !== 'active') {
      return null;
    }
    if ($key->expires_at && $key->expires_at->isPast()) {
      return null;
    }

    // Best-effort touch (use the model's updated_at)
    $key->forceFill(['last_used_at' => Carbon::now()])->save();

    return $key;
  }

  /**
   * Revoke a key by its *plain* token hash.
   * Replaces the revoke() method in the old helper.
   */
  public function revoke(string $token): bool
  {
    $hash = $this->hashToken($token);

    $updatedCount = ApiKey::query()
      ->byTokenHash($hash)
      ->where('status', 'active')
      ->update(['status' => 'revoked']);

    return $updatedCount > 0;
  }
}
