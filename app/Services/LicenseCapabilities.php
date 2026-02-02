<?php

namespace App\Services;

class LicenseCapabilities
{
    public const TIER_BETA = 'beta';
    public const TIER_TRIAL = 'trial';
    public const TIER_BASIC = 'basic';
    public const TIER_PRO = 'pro';
    public const TIER_PREMIUM = 'premium';

    /**
     * @return array<int, string>
     */
    public static function capabilitiesForTier(string $tier): array
    {
        $tier = strtolower(trim($tier));

        return match ($tier) {
            self::TIER_BETA => [
                FeatureGate::PAYMENTS_FRAMEWORK,
                FeatureGate::PAYMENTS_EXTERNAL_LINK,
                FeatureGate::PAYMENTS_STRIPE,
                FeatureGate::PAYMENTS_WAVE,
                FeatureGate::PAYMENTS_MOCK_PROVIDERS,
            ],
            self::TIER_TRIAL => [
                FeatureGate::PAYMENTS_FRAMEWORK,
                FeatureGate::PAYMENTS_EXTERNAL_LINK,
                FeatureGate::PAYMENTS_MOCK_PROVIDERS,
            ],
            self::TIER_PRO => [
                FeatureGate::PAYMENTS_FRAMEWORK,
                FeatureGate::PAYMENTS_EXTERNAL_LINK,
                FeatureGate::PAYMENTS_STRIPE,
            ],
            self::TIER_PREMIUM => [
                FeatureGate::PAYMENTS_FRAMEWORK,
                FeatureGate::PAYMENTS_EXTERNAL_LINK,
                FeatureGate::PAYMENTS_STRIPE,
                FeatureGate::PAYMENTS_WAVE,
            ],
            default => [
                FeatureGate::PAYMENTS_FRAMEWORK,
                FeatureGate::PAYMENTS_EXTERNAL_LINK,
            ],
        };
    }
}
