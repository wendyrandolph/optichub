<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class BlockReportsForTradesWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        // If tenant isn't bound for some reason, just continue; other middleware will handle.
        if (!$tenant instanceof Tenant) {
            return $next($request);
        }

        if (strtolower((string) ($tenant->workspace_type ?? 'creative')) === 'trades') {
            abort(404);
        }

        return $next($request);
    }
}
