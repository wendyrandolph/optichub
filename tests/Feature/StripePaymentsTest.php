<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Tenant;
use App\Models\TenantPaymentAccount;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Stripe\AccountLink;
use Stripe\Checkout\Session as StripeSession;

class StripePaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_start_stripe_connect(): void
    {
        $tenant = Tenant::create([
            'name' => 'Studio',
            'workspace_type' => 'creative',
            'subscription_status' => 'beta',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $fake = new class extends StripeConnectService {
            public function __construct() {}
            public function enabled(): bool { return true; }
            public function createOrGetAccount(Tenant $tenant): TenantPaymentAccount
            {
                return TenantPaymentAccount::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'provider' => 'stripe'],
                    ['status' => 'pending', 'secret_data' => ['stripe_account_id' => 'acct_test_123']]
                );
            }
            public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl): AccountLink
            {
                return AccountLink::constructFrom(['url' => 'https://stripe.test/onboard']);
            }
        };

        $this->app->instance(StripeConnectService::class, $fake);

        $this->actingAs($user)
            ->get(route('tenant.settings.payments.stripe.connect', ['tenant' => $tenant->id]))
            ->assertRedirect('https://stripe.test/onboard');

        $this->assertDatabaseHas('tenant_payment_accounts', [
            'tenant_id' => $tenant->id,
            'provider' => 'stripe',
        ]);
    }

    public function test_creating_checkout_session_records_pending_payment(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Alex',
            'lastName' => 'Client',
            'email' => 'client@example.com',
            'role' => 'client',
        ]);
        $clientUser = User::factory()->client($client)->create();
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $client->id,
            'invoice_number' => 'INV-1001',
            'status' => 'sent',
            'total_amount' => 100,
            'total' => 100,
            'balance_due' => 100,
            'currency' => 'usd',
        ]);

        TenantPaymentAccount::create([
            'tenant_id' => $tenant->id,
            'provider' => 'stripe',
            'status' => 'active',
            'secret_data' => ['stripe_account_id' => 'acct_test_123'],
        ]);

        $fake = new class extends StripeConnectService {
            public function __construct() {}
            public function enabled(): bool { return true; }
            public function createCheckoutSession(Invoice $invoice, float $amount, string $successUrl, string $cancelUrl, string $accountId): StripeSession
            {
                return StripeSession::constructFrom(['id' => 'cs_test_123', 'url' => 'https://checkout.test/session']);
            }
        };

        $this->app->instance(StripeConnectService::class, $fake);

        Gate::shouldReceive('authorize')->andReturn(true);

        $this->actingAs($clientUser, 'client')
            ->post(route('portal.invoices.pay', $invoice), ['amount' => 100])
            ->assertRedirect('https://checkout.test/session');

        $this->assertDatabaseHas('invoice_payments', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'provider_checkout_id' => 'cs_test_123',
        ]);
    }

    public function test_webhook_marks_invoice_paid(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $contact = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
            'email' => 'client@example.com',
            'role' => 'client',
        ]);
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'INV-2001',
            'status' => 'sent',
            'total_amount' => 100,
            'total' => 100,
            'balance_due' => 100,
            'currency' => 'usd',
        ]);

        $payloadData = [
            'id' => 'evt_test_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'amount_total' => 10000,
                    'currency' => 'usd',
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'tenant_id' => (string) $tenant->id,
                    ],
                ],
            ],
        ];

        $payload = json_encode($payloadData);

        $sig = $this->buildStripeSignature($payload, 'whsec_test');

        $response = $this->withHeaders([
            'Stripe-Signature' => $sig,
            'X-Stripe-Test' => '1',
        ])->postJson('/stripe/webhook', $payloadData);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.0, (float) $invoice->balance_due);

        $this->assertDatabaseHas('invoice_payments', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'provider_checkout_id' => 'cs_test_123',
        ]);
    }

    public function test_webhook_rejects_tenant_mismatch(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $tenantA = Tenant::create(['name' => 'Studio A', 'workspace_type' => 'creative']);
        $tenantB = Tenant::create(['name' => 'Studio B', 'workspace_type' => 'creative']);

        $invoice = Invoice::create([
            'tenant_id' => $tenantA->id,
            'invoice_number' => 'INV-3001',
            'status' => 'sent',
            'total_amount' => 100,
            'total' => 100,
            'balance_due' => 100,
            'currency' => 'usd',
        ]);

        $payloadData = [
            'id' => 'evt_test_2',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_456',
                    'amount_total' => 10000,
                    'currency' => 'usd',
                    'payment_intent' => 'pi_test_456',
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'tenant_id' => (string) $tenantB->id,
                    ],
                ],
            ],
        ];

        $payload = json_encode($payloadData);

        $sig = $this->buildStripeSignature($payload, 'whsec_test');

        $response = $this->withHeaders([
            'Stripe-Signature' => $sig,
            'X-Stripe-Test' => '1',
        ])->postJson('/stripe/webhook', $payloadData);

        $response->assertStatus(403);
    }

    private function buildStripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return sprintf('t=%s,v1=%s', $timestamp, $signature);
    }
}
