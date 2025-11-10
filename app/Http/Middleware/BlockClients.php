<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockClients
{
    public function handle(Request $request, Closure $next)
    {
        // If you have a custom Auth facade method:
        if (method_exists(Auth::class, 'isClient') && Auth::isClient()) {
            return redirect('/client/portal');
        }

        // Fallback check if your User model has a role attribute
        $user = Auth::user();
        if ($user && property_exists($user, 'role') && in_array($user->role, ['client', 'customer'])) {
            return redirect('/client/portal');
        }

        return $next($request);
    }
}
