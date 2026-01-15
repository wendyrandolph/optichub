<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = auth('client')->user();
        if ($client && !empty($client->tenant_id)) {
            session(['tenant_id' => $client->tenant_id]);
        }

        return $next($request);
    }
}
