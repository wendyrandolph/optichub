<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanViewTradesReports
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user(); // tenant user

        if (!$user) {
            abort(403);
        }

        $role = strtolower((string) ($user->role ?? ''));

        if (method_exists($user, 'isTech') && $user->isTech()) {
            $tenantParam = $request->route('tenant');
            $tenantId = $tenantParam?->id ?? $tenantParam ?? $user->tenant_id;
            if ($tenantId) {
                return redirect()->route('tenant.trades.field.today', ['tenant' => $tenantId]);
            }
            abort(403);
        }

        // Office roles
        $allowed = [
            'admin',
            'dispatcher',
            'owner',
            'superadmin', // if you use it inside tenant context
            'platform owner', // only if this is a tenant user role in your app
        ];

        if (!in_array($role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
