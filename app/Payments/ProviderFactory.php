<?php

namespace App\Payments;

use App\Models\PaymentIntegration;
use App\Payments\Contracts\PaymentProvider;
use App\Payments\Providers\StripeProvider;
use App\Payments\Providers\AuthNetProvider;
use InvalidArgumentException;

class ProviderFactory
{
  public function forIntegration(?PaymentIntegration $integration): PaymentProvider
  {
    $provider = $integration?->provider ?? 'manual';
    $cfg      = $integration?->credentials ?? [];

    return match ($provider) {
      'stripe' => new StripeProvider(
        apiKey: $cfg['secret'] ?? null,
        connectedAccountId: $cfg['account_id'] ?? null,
      ),
      'authorizenet', 'authnet' => new AuthNetProvider(
        apiLoginId: $cfg['api_login_id'] ?? '',
        transactionKey: $cfg['transaction_key'] ?? '',
        signatureKey: $cfg['signature_key'] ?? null,
        sandbox: (bool)($cfg['sandbox'] ?? true),
      ),
      'manual' => new class implements \App\Payments\Contracts\PaymentProvider {
        public function init($r): array
        {
          return ['redirect_url' => null];
        }
        public function capture($r): array
        {
          return ['status' => 'pending'];
        }
        public function refund($r): array
        {
          return ['status' => 'pending'];
        }
        public function verifyWebhook($h, $b): array
        {
          return [];
        }
      },
      default => throw new InvalidArgumentException("Unknown provider [$provider]"),
    };
  }

  public function forTenantId(int $tenantId): PaymentProvider
  {
    $integration = PaymentIntegration::where('tenant_id', $tenantId)->where('active', true)->first();
    return $this->forIntegration($integration);
  }
}
