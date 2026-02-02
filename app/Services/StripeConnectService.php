<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantPaymentAccount;
use Stripe\Account as StripeAccount;
use Stripe\AccountLink;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeConnectService
{
    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if ($secret) {
            Stripe::setApiKey($secret);
        }
    }

    public function enabled(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    public function createOrGetAccount(Tenant $tenant): TenantPaymentAccount
    {
        $existing = TenantPaymentAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'stripe')
            ->first();

        if ($existing && !empty($existing->secret_data['stripe_account_id'])) {
            return $existing;
        }

        $email = $tenant->support_email
            ?? $tenant->owner_email
            ?? $tenant->users()->orderBy('id')->value('email');

        $account = StripeAccount::create([
            'type' => 'standard',
            'email' => $email,
            'business_profile' => [
                'name' => $tenant->name ?? 'Renlo Tenant',
            ],
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
            ],
        ]);

        return TenantPaymentAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'provider' => 'stripe'],
            [
                'status' => 'pending',
                'public_data' => [
                    'display_name' => $tenant->name,
                ],
                'secret_data' => [
                    'stripe_account_id' => $account->id,
                ],
            ]
        );
    }

    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl): AccountLink
    {
        return AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);
    }

    public function createCheckoutSession(Invoice $invoice, float $amount, string $successUrl, string $cancelUrl, string $accountId): StripeSession
    {
        $currency = strtolower($invoice->currency ?? 'usd');
        $amountCents = (int) round($amount * 100);

        $lineLabel = $invoice->invoice_number
            ? 'Invoice #' . $invoice->invoice_number
            : 'Invoice #' . $invoice->id;

        $client = $invoice->client;

        $params = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $lineLabel,
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'tenant_id' => (string) $invoice->tenant_id,
                'invoice_id' => (string) $invoice->id,
            ],
        ];

        if (!empty($client?->email)) {
            $params['customer_email'] = $client->email;
        }

        return StripeSession::create($params, ['stripe_account' => $accountId]);
    }
}
