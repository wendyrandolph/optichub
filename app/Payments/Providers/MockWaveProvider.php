<?php

namespace App\Payments\Providers;

use App\Payments\Contracts\PaymentProvider;
use Illuminate\Support\Facades\URL;

class MockWaveProvider implements PaymentProvider
{
    public function __construct(private array $credentials = [], private array $options = [])
    {
    }

    public static function id(): string
    {
        return 'mock_wave';
    }

    public static function displayName(): string
    {
        return 'Mock Wave';
    }

    public static function credentialRules(): array
    {
        return [];
    }

    public function createPaymentIntent(array $payload): array
    {
        $invoiceId = $payload['metadata']['invoice_id'] ?? null;
        $tenantId = $payload['metadata']['tenant_id'] ?? null;

        $redirectUrl = $invoiceId && $tenantId
            ? URL::route('tenant.payments.mock.show', ['tenant' => $tenantId, 'invoice' => $invoiceId])
            : null;

        return [
            'id' => 'mock_wave_' . uniqid(),
            'status' => 'requires_action',
            'clientSecret' => null,
            'redirectUrl' => $redirectUrl,
        ];
    }

    public function capture(string $paymentIntentId, ?int $amount = null): array
    {
        return ['status' => 'succeeded'];
    }

    public function refund(string $paymentIntentId, ?int $amount = null): array
    {
        return ['refundId' => 'mock_refund_' . uniqid()];
    }

    public function createCustomer(array $data): array
    {
        return ['customerId' => 'mock_customer_' . uniqid()];
    }

    public function webhookHandlers(): array
    {
        return [];
    }
}
