<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminSession
{
    public function handle(Request $request, Closure $next)
    {
        // Admin console gets its own cookie
        config([
            'session.cookie' => env('SESSION_COOKIE_ADMIN', 'renlo_admin_session'),
        ]);

        return $next($request);
    }
}
