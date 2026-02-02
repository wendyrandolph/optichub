<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCapability
{
    public function __construct(private FeatureGate $featureGate)
    {
    }

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $tenantParam = $request->route('tenant');
        $tenant = $tenantParam instanceof Tenant
            ? $tenantParam
            : (is_numeric($tenantParam) ? Tenant::find((int) $tenantParam) : null);

        if (!$tenant) {
            $tenantId = auth()->user()->tenant_id ?? null;
            if (!$tenantId) {
                $tenantId = auth('client')->user()->tenant_id ?? null;
            }
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
        }

        if (!$tenant) {
            abort(404);
        }

        $actor = auth('admin')->user() ?? auth()->user() ?? auth('client')->user();
        $tenantAllows = $this->featureGate->allowsTenantTier($tenant, $capability);

        if ($tenantAllows) {
            return $next($request);
        }

        if ($actor && method_exists($actor, 'isProviderAdmin') && $actor->isProviderAdmin()) {
            $this->featureGate->logOverride($tenant, $capability, $actor);
            return $next($request);
        }

        abort(403, 'This feature is not available on your plan.');
    }
}
