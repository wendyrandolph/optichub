<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TrackTenantActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only track "real" web navigation (skip assets, etc.)
        if ($this->shouldSkip($request)) {
            return $response;
        }

        // Resolve tenant from route model binding or route param
        $tenant = $request->route('tenant');

        // If route('tenant') is an id and not a model, try to resolve it
        if (!is_object($tenant) && $tenant) {
            $tenant = \App\Models\Tenant::find($tenant);
        }

        if ($tenant) {
            // Throttle writes: only update at most once every 5 minutes per tenant
            $now = Carbon::now();
            $last = $tenant->last_activity_at ? Carbon::parse($tenant->last_activity_at) : null;

            if (!$last || $last->diffInMinutes($now) >= 5) {
                $tenant->forceFill(['last_activity_at' => $now])->save();
            }
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        if (!$request->isMethod('GET') && !$request->isMethod('POST') && !$request->isMethod('PUT') && !$request->isMethod('PATCH') && !$request->isMethod('DELETE')) {
            return true;
        }

        // Skip pings/health endpoints if you add them
        if ($request->is('_keepalive')) return true;

        // Skip typical asset paths
        if ($request->is('build/*') || $request->is('storage/*') || $request->is('favicon.ico')) {
            return true;
        }

        return false;
    }
}
