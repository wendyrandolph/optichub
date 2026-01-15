<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AppSession
{
    public function handle(Request $request, Closure $next)
    {
        // Tenant app gets its own cookie
        config([
            'session.cookie' => env('SESSION_COOKIE_APP', 'renlo_app_session'),
        ]);

        return $next($request);
    }
}
