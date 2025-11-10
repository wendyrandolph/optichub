<?php
// app/Http/Middleware/ApiKeyAuth.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;

class ApiKeyAuth
{
  public function handle(Request $request, Closure $next)
  {
    $plain = $request->header('X-Api-Key');

    if (!$plain) {
      return response()->json(['message' => 'API key required'], Response::HTTP_UNAUTHORIZED);
    }

    // Compute hash
    $hash = hash('sha256', $plain);

    // Look for active key
    $key = ApiKey::query()
      ->where('key_hash', $hash)
      ->where('status', 'active')
      ->whereNull('revoked_at')
      ->first();

    if (!$key) {
      return response()->json(['message' => 'Invalid or revoked API key'], Response::HTTP_UNAUTHORIZED);
    }

    // Make tenant context available globally
    app()->instance('api.tenant_id', $key->tenant_id);
    app()->instance('api.key_id', $key->id);

    return $next($request);
  }
}
