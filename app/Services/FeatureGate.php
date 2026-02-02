<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class FeatureGate
{
    public const PAYMENTS_FRAMEWORK = 'PAYMENTS_FRAMEWORK';
    public const PAYMENTS_EXTERNAL_LINK = 'PAYMENTS_EXTERNAL_LINK';
    public const PAYMENTS_STRIPE = 'PAYMENTS_STRIPE';
    public const PAYMENTS_WAVE = 'PAYMENTS_WAVE';
    public const PAYMENTS_MOCK_PROVIDERS = 'PAYMENTS_MOCK_PROVIDERS';

    public function allows(Tenant $tenant, string $capability, ?Model $user = null): bool
    {
        $actor = $user ?? Auth::guard('admin')->user() ?? Auth::user();
        $isProviderAdmin = $actor && method_exists($actor, 'isProviderAdmin') && $actor->isProviderAdmin();

        if ($isProviderAdmin) {
            return true;
        }

        return $this->allowsTenantTier($tenant, $capability);
    }

    public function allowsTenantTier(Tenant $tenant, string $capability): bool
    {
        $tier = $this->resolveTenantTier($tenant);
        $capabilities = LicenseCapabilities::capabilitiesForTier($tier);

        return in_array($capability, $capabilities, true);
    }

    public function logOverride(Tenant $tenant, string $capability, ?Model $user = null): void
    {
        $actor = $user ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$actor) {
            return;
        }

        ActivityLog::record(
            tenantId: $tenant->getKey(),
            userId: $actor->getKey(),
            subject: $tenant,
            action: 'provider_override',
            description: 'Provider override for capability access',
            properties: [
                'capability' => $capability,
                'path' => Request::path(),
                'ip' => Request::ip(),
            ]
        );
    }

    private function resolveTenantTier(Tenant $tenant): string
    {
        $status = strtolower((string) ($tenant->subscription_status ?? ''));

        if ($status === 'beta') {
            return LicenseCapabilities::TIER_BETA;
        }

        if (in_array($status, ['trialing', 'trial'], true)) {
            return LicenseCapabilities::TIER_TRIAL;
        }

        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
            return LicenseCapabilities::TIER_TRIAL;
        }

        $plan = (string) (
            $tenant->currentSubscription?->plan_code
            ?? $tenant->plan_name
            ?? $tenant->subscription_plan
            ?? ''
        );

        $plan = Str::of($plan)->lower()->trim()->replace(' ', '')->toString();

        return match ($plan) {
            'starter', 'basic' => LicenseCapabilities::TIER_BASIC,
            'pro', 'growth' => LicenseCapabilities::TIER_PRO,
            'studio', 'premium', 'enterprise', 'business' => LicenseCapabilities::TIER_PREMIUM,
            default => LicenseCapabilities::TIER_BASIC,
        };
    }
}
