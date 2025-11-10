<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL; // <-- add this

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $raw = $request->route('tenant');

        Log::info('ResolveTenant Middleware: Starting execution.', [
            'route_param' => $raw,
            'type'        => is_object($raw) ? get_class($raw) : gettype($raw),
        ]);

        // 1) Normalize to a Tenant model
        if ($raw instanceof Tenant) {
            $tenant = $raw;
        } elseif (is_numeric($raw)) {
            $tenant = Tenant::find($raw);
        } else {
            $tenant = null;
        }


        if (! $tenant) {
            Log::error('ResolveTenant Middleware: Resolution failed. Aborting.', [
                'route_param' => $raw,
                'type'        => gettype($raw),
            ]);
            abort(404);
        }

        // 2) Optional: authorization gate
        $user = Auth::user();
        if ($user) {
            $role = strtolower((string) ($user->role ?? ''));
            $isProviderAdmin = in_array($role, ['provider', 'admin', 'super_admin', 'superadmin'], true);

            if (! $isProviderAdmin && (int) $user->tenant_id !== (int) $tenant->id) {
                Log::warning('ResolveTenant: user/tenant mismatch — forbidding.', [
                    'user_tenant_id'   => $user->tenant_id,
                    'requested_tenant' => $tenant->id,
                    'role'             => $role,
                ]);
                abort(403);
            }
        }

        // 3) Bind tenant for the request and keep route param as the model
        app()->instance('currentTenant', $tenant);
        $request->route()->setParameter('tenant', $tenant);

        // 4) 👈 THE FIX: set URL defaults so route('tenant.*') doesn't need manual params
        \URL::defaults(['tenant' => $tenant->id]);

        return $next($request);
    }
}
