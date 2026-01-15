<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('tenant')) {
    /**
     * Retrieve the current tenant model or a specific attribute.
     */
    function tenant(?string $key = null)
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (!$tenant) {
            $tenant = Auth::user()?->tenant;
        }

        if (!$tenant && ($tenantId = session('tenant_id'))) {
            $tenant = Tenant::find($tenantId);
        }

        if (!$tenant) {
            return null;
        }

        return $key ? data_get($tenant, $key) : $tenant;
    }
}

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

if (!function_exists('renlo_root_domain')) {
    function renlo_root_domain(): string
    {
        return config('renlo.root_domain', '127.0.0.1.nip.io');
    }
}

if (!function_exists('renlo_scheme')) {
    function renlo_scheme(): string
    {
        return app()->environment('local') ? 'http' : 'https';
    }
}

if (!function_exists('renlo_admin_base_url')) {
    function renlo_admin_base_url(): string
    {
        $root = renlo_root_domain();
        $hostConfig = config('renlo.admin_host', 'admin.' . $root);
        $parsed = parse_url($hostConfig);
        $host = $parsed['host'] ?? ltrim($hostConfig, 'http://https://');
        $portOverride = $parsed['port'] ?? null;

        $port = '';
        if (app()->environment('local')) {
            $port = $portOverride ?? (config('renlo.local_port') ?: request()->getPort());
        } elseif ($portOverride) {
            $port = $portOverride;
        }
        if (in_array((int) $port, [80, 443], true)) {
            $port = '';
        }

        $scheme = $parsed['scheme'] ?? renlo_scheme();
        $base = $scheme . '://' . $host;

        return $base . ($port ? ':' . $port : '');
    }
}

if (!function_exists('renlo_app_base_url')) {
    function renlo_app_base_url(): string
    {
        $root = renlo_root_domain();
        $hostConfig = config('renlo.app_host', 'app.' . $root);
        $parsed = parse_url($hostConfig);
        $host = $parsed['host'] ?? ltrim($hostConfig, 'http://https://');
        $portOverride = $parsed['port'] ?? null;

        $port = '';
        if (app()->environment('local')) {
            $port = $portOverride ?? (config('renlo.local_port') ?: request()->getPort());
        } elseif ($portOverride) {
            $port = $portOverride;
        }
        if (in_array((int) $port, [80, 443], true)) {
            $port = '';
        }

        $scheme = $parsed['scheme'] ?? renlo_scheme();
        $base = $scheme . '://' . $host;

        return $base . ($port ? ':' . $port : '');
    }
}
