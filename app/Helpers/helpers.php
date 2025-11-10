<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('adminOnly')) {
    /**
     * Execute the callback only when the current user is an internal admin/provider.
     *
     * Falls back to a 403 if the conditions are not met.
     */
    function adminOnly(callable $callback)
    {
        $user = Auth::user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Authentication required.');
        }

        $role = $user->role ?? null;
        $isProvider = $role === 'provider';
        $isAdmin = $role === 'admin';

        if (!$isProvider && !$isAdmin) {
            abort(Response::HTTP_FORBIDDEN, 'Admins only.');
        }

        return $callback();
    }
}

if (!function_exists('clientOnly')) {
    /**
     * Restrict access to client users.
     */
    function clientOnly(callable $callback)
    {
        $user = Auth::user();

        if (!$user || ($user->role ?? null) !== 'client') {
            abort(Response::HTTP_FORBIDDEN, 'Clients only.');
        }

        return $callback();
    }
}

if (!function_exists('saasTenantOnly')) {
    /**
     * Restrict access to SaaS tenant users (non-provider internal users).
     */
    function saasTenantOnly(callable $callback)
    {
        $user = Auth::user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN, 'Authentication required.');
        }

        $role = $user->role ?? null;

        if (in_array($role, ['provider', 'client'], true)) {
            abort(Response::HTTP_FORBIDDEN, 'Tenant users only.');
        }

        return $callback();
    }
}
