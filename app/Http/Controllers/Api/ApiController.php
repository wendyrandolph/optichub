<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;

class ApiController extends Controller
{
  public function ping()
  {
    return response()->json(['ok' => true, 'ts' => now()->toIso8601String()]);
  }

  public function authPing()
  {
    return response()->json([
      'ok'        => true,
      'tenant_id' => app('api.tenant_id'),
      'api_key_id' => app('api.key_id'),
      'ts'        => now()->toIso8601String(),
    ]);
  }
}
