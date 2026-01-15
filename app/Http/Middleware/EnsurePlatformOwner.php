<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('admin')->user();

        if (! $user || ! $user->isPlatformOwner()) {
            abort(403, 'You are not allowed to manage tenants.');
        }

        return $next($request);
    }
}
