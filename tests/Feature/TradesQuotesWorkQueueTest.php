<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\TradeQuote;
use App\Models\TradeQuoteAcceptance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesQuotesWorkQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotes_index_is_tenant_scoped(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Trades A',
            'workspace_type' => 'trades',
        ]);
        $tenantB = Tenant::create([
            'name' => 'Trades B',
            'workspace_type' => 'trades',
        ]);

        $user = User::create([
            'tenant_id' => $tenantA->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades.a',
            'email' => 'admin-a@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $clientB = Contact::create([
            'tenant_id' => $tenantB->id,
            'firstName' => 'Jamie',
            'lastName' => 'Other',
        ]);

        TradeQuote::create([
            'tenant_id' => $tenantB->id,
            'client_id' => $clientB->id,
            'title' => 'Other Tenant Quote',
            'status' => 'draft',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.quotes.index', ['tenant' => $tenantA->id]));

        $response->assertOk();
        $response->assertDontSee('Other Tenant Quote');
    }

    public function test_filters_and_kpis_render(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades',
            'email' => 'admin@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $client = Contact::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
            'email' => 'jamie@trades.test',
        ]);

        $sentAt = Carbon::now()->subDays(5);
        $acceptedAt = Carbon::now()->subDays(2);

        $sentQuote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Sent Quote',
            'status' => 'sent',
            'subtotal' => 200,
            'tax_total' => 0,
            'total' => 200,
            'sent_at' => $sentAt,
        ]);

        $acceptedQuote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Accepted Quote',
            'status' => 'accepted',
            'subtotal' => 300,
            'tax_total' => 0,
            'total' => 300,
            'sent_at' => $sentAt,
        ]);

        TradeQuoteAcceptance::create([
            'trade_quote_id' => $acceptedQuote->id,
            'signer_name' => 'Jamie Client',
            'signature' => 'Jamie',
            'accepted_at' => $acceptedAt,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.quotes.index', [
                'tenant' => $tenant->id,
                'status' => 'sent',
                'from' => Carbon::now()->subDays(10)->toDateString(),
                'to' => Carbon::now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('Acceptance rate');
        $response->assertSee('Sent Quote');
        $response->assertDontSee('Accepted Quote');
    }

    public function test_actionable_quotes_sort_first(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades.sort',
            'email' => 'admin.sort@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $client = Contact::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
        ]);

        TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Accepted Quote',
            'status' => 'accepted',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
        ]);

        TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Draft Quote',
            'status' => 'draft',
            'subtotal' => 120,
            'tax_total' => 0,
            'total' => 120,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.quotes.index', ['tenant' => $tenant->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Draft Quote', 'Accepted Quote']);
    }
}
