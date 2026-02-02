<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureProviderSupportAccess
{
    public function handle(Request $request, Closure $next)
    {
        $actor = auth('admin')->user() ?? auth()->user();

        if (! $actor) {
            abort(403);
        }

        if (method_exists($actor, 'isProviderAdmin') && $actor->isProviderAdmin()) {
            return $next($request);
        }

        if (! empty($actor->can_manage_support)) {
            return $next($request);
        }

        abort(403);
    }
}
