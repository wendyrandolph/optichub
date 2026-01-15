<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware(['auth:admin', CheckRole::class . ':admin,super_admin,provider,guard=admin'])
     */
    // app/Http/Middleware/CheckRole.php
    public function handle($request, \Closure $next, ...$params)
    {
        $guard = null;
        $roles = [];
        foreach ($params as $p) {
            if (is_string($p) && str_starts_with($p, 'guard=')) $guard = substr($p, 6);
            else $roles[] = $p;
        }

        $user = $guard ? auth($guard)->user()
            : (auth('admin')->user() ?? auth('client')->user() ?? auth()->user());

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403); // or redirect()->route('marketing.home')
        }

        return $next($request);
    }
}
