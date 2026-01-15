<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\TradeQuote;
use App\Models\TradeQuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradesQuoteBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_quote_renders_tenant_branding(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Trades',
            'workspace_type' => 'trades',
            'support_email' => 'help@acme.test',
            'phone' => '555-201-8899',
            'primary_color' => '#112233',
            'logo_path' => 'images/renlo.svg',
        ]);

        $client = Contact::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
            'email' => 'jamie@acme.test',
        ]);

        $token = 'test-token-123';

        $quote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Site Visit Quote',
            'status' => 'sent',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'token_hash' => hash('sha256', $token),
            'sent_at' => now(),
        ]);

        TradeQuoteItem::create([
            'trade_quote_id' => $quote->id,
            'description' => 'Inspection',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $response = $this->get(route('public.trade-quotes.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Acme Trades');
        $response->assertSee('help@acme.test');
        $response->assertSee('555-201-8899');
        $response->assertSee('Site Visit Quote');
    }
}
