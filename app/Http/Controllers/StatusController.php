<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
  public function show()
  {
    // Super-light health checks (stubs)
    $checks = [
      'app' => ['ok' => true, 'message' => 'Running'],
    ];

    try {
      DB::connection()->getPdo();
      $checks['db'] = ['ok' => true, 'message' => 'Connected'];
    } catch (\Throwable $e) {
      Log::warning('Status DB check failed: ' . $e->getMessage());
      $checks['db'] = ['ok' => false, 'message' => 'Unavailable'];
    }

    // You can add: queue, cache, mail, storage checks here later.

    return view('static.status', [
      'checks'  => $checks,
      'version' => config('app.version', '1.0.0'),
    ]);
  }
}
