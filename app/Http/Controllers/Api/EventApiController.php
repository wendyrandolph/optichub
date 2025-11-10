<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Event; // or whatever your model is

class EventApiController extends Controller
{
  public function index()
  {
    $tenantId = app('api.tenant_id');
    $events = Event::where('tenant_id', $tenantId)
      ->latest()
      ->limit(100)
      ->get();

    return response()->json($events);
  }
}
