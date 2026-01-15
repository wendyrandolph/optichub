<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesLeadsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_speed_to_lead_is_tracked(): void
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

        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jamie Client',
            'status' => 'new',
        ]);

        $this->actingAs($user, 'web')
            ->post(route('tenant.trades.leads.contact', ['tenant' => $tenant->id, 'lead' => $lead->id]))
            ->assertRedirect();

        $lead->refresh();
        $this->assertNotNull($lead->first_contacted_at);
        $this->assertDatabaseHas('trade_lead_events', [
            'tenant_id' => $tenant->id,
            'lead_id' => $lead->id,
            'type' => 'contacted',
        ]);
    }

    public function test_performance_leads_page_renders_response_time_section(): void
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
            'email' => 'admin2@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        Lead::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jamie Client',
            'status' => 'contacted',
            'created_at' => now()->subHours(3),
            'first_contacted_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.performance.show', ['tenant' => $tenant->id, 'kpi' => 'leads']));

        $response->assertOk();
        $response->assertSee('Response time');
    }

    public function test_public_inbox_stores_attribution(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'inbox_key' => 'test-inbox-key',
        ]);

        $this->withoutMiddleware(ThrottleRequests::class);

        $payload = [
            'name' => 'Web Lead',
            'email' => 'web@lead.test',
            'utm_source' => 'google',
            'utm_campaign' => 'winter',
            'page_url' => 'https://example.com/quote',
        ];

        $this->postJson(route('public.leads.inbox', ['inbox_key' => $tenant->inbox_key]), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $lead = Lead::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame('google', $lead->source_detail['utm_source'] ?? null);
        $this->assertSame('https://example.com/quote', $lead->source_detail['page_url'] ?? null);
    }
}
