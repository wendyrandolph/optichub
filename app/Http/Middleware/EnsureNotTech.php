<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotTech
{
    public function handle($request, \Closure $next)
    {
        $user = auth('web')->user(); // <- important

        if (!$user) {
            abort(403);
        }

        if (method_exists($user, 'isTech') && $user->isTech()) {
            $tenantParam = $request->route('tenant');
            $tenantId = $tenantParam?->id ?? $tenantParam ?? $user->tenant_id;
            if ($tenantId) {
                return redirect()->route('tenant.trades.field.today', ['tenant' => $tenantId]);
            }
            abort(403);
        }

        return $next($request);
    }
}
