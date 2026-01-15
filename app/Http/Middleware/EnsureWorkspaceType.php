<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $tenant = $request->route('tenant');

        if (!$tenant instanceof Tenant) {
            $tenantId = is_numeric($tenant) ? (int) $tenant : null;
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
        }

        if (!$tenant) {
            abort(404);
        }

        $workspaceType = strtolower((string) ($tenant->workspace_type ?? 'creative'));
        if ($workspaceType !== strtolower($type)) {
            abort(404);
        }

        return $next($request);
    }
}
