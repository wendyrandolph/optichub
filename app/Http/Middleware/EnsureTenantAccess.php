<?php

// app/Http/Middleware/EnsureTenantAccess.php
namespace App\Http\Middleware;

use Closure;

class EnsureTenantAccess
{
    public function handle($request, Closure $next)
    {
        $user = auth('admin')->user() ?? auth('client')->user() ?? auth()->user();
        $tenant = $user?->tenant;

        if (! $tenant) {
            abort(403);
        }

        $status = $tenant->getTenantAccessStatus();
        if ($status && ($status['access_granted'] ?? false)) {
            return $next($request);
        }

        return redirect()->route('billing.paywall')
            ->with('status', 'Your trial has ended. Choose a plan to continue.');
    }
}
