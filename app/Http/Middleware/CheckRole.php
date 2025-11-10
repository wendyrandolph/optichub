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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if the user is authenticated
        if (!Auth::check()) {
            return redirect('/login'); // Should not happen for a logged-in user, but good check
        }

        // --- DEBUG LINE 1: See the User's role and the required roles ---
        $user = Auth::user();
        $userRole = $user->role; // Get the role attribute from the authenticated user

        // Use error_log to ensure visibility if Laravel's logger isn't configured for screen output
        error_log("DEBUG: User ID: " . $user->id);
        error_log("DEBUG: User Role from Model: " . $userRole);
        error_log("DEBUG: Required Roles: " . implode(', ', $roles));
        // -----------------------------------------------------------------

        // 2. Check if the user's role is in the list of allowed roles
        if (!in_array($userRole, $roles)) {

            // --- DEBUG LINE 2: Log the block before throwing the error ---
            error_log("DEBUG: BLOCKING ACCESS. Role '{$userRole}' is not in required list.");
            // -------------------------------------------------------------

            // The 403 error is thrown here
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
