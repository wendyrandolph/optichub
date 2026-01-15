<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\TradeJob;
use App\Models\TradeQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesQuotesPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_trades_user_can_view_quotes_index(): void
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

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.quotes.index', ['tenant' => $tenant->id]));

        $response->assertOk();
    }

    public function test_quotes_status_filter_and_schedule_link(): void
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

        $job = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Furnace check',
            'type' => 'service',
            'status' => 'open',
        ]);

        $siteVisitQuote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'trade_job_id' => $job->id,
            'title' => 'Site Visit Quote',
            'status' => 'needs_site_visit',
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
        ]);

        $draftQuote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'title' => 'Draft Quote',
            'status' => 'draft',
            'subtotal' => 120,
            'tax_total' => 0,
            'total' => 120,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.quotes.index', [
                'tenant' => $tenant->id,
                'status' => 'needs_site_visit',
            ]));

        $response->assertOk();
        $response->assertSee('Site Visit Quote');
        $response->assertDontSee('Draft Quote');

        $expectedUrl = route('tenant.trades.schedule.create', [
            'tenant' => $tenant->id,
            'quote' => $siteVisitQuote->id,
            'purpose' => 'site_visit',
            'job' => $job->id,
        ]);

        $response->assertSee($expectedUrl);
    }
}
