<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantLeadSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_accepts_valid_token_and_creates_lead(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $settings = TenantLeadSetting::create([
            'tenant_id' => $tenant->id,
            'inbound_secret' => 'secret_123',
        ]);

        $payload = [
            'name' => 'Alex Lead',
            'email' => 'alex@example.com',
            'phone' => '555-0101',
            'message' => 'Hello',
        ];

        $response = $this->withHeader('X-Renlo-Token', $settings->inbound_secret)
            ->postJson(route('api.leads.ingest', ['tenant' => $tenant->id]), $payload);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('leads', [
            'tenant_id' => $tenant->id,
            'name' => 'Alex Lead',
            'email' => 'alex@example.com',
            'source' => 'website',
        ]);
    }

    public function test_ingest_rejects_missing_or_invalid_token(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        TenantLeadSetting::create([
            'tenant_id' => $tenant->id,
            'inbound_secret' => 'secret_123',
        ]);

        $payload = ['name' => 'Blocked'];

        $this->postJson(route('api.leads.ingest', ['tenant' => $tenant->id]), $payload)
            ->assertStatus(401);

        $this->withHeader('X-Renlo-Token', 'wrong')
            ->postJson(route('api.leads.ingest', ['tenant' => $tenant->id]), $payload)
            ->assertStatus(403);
    }

    public function test_honeypot_blocks_spam(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $settings = TenantLeadSetting::create([
            'tenant_id' => $tenant->id,
            'inbound_secret' => 'secret_123',
        ]);

        $payload = [
            'name' => 'Spammy',
            'website' => 'bot-field',
        ];

        $response = $this->withHeader('X-Renlo-Token', $settings->inbound_secret)
            ->postJson(route('api.leads.ingest', ['tenant' => $tenant->id]), $payload);

        $response->assertStatus(200)->assertJson(['success' => true, 'spam' => true]);
        $this->assertDatabaseMissing('leads', ['name' => 'Spammy']);
    }

    public function test_tenant_cannot_access_another_tenants_lead(): void
    {
        $tenantA = Tenant::create(['name' => 'Studio A', 'workspace_type' => 'creative']);
        $tenantB = Tenant::create(['name' => 'Studio B', 'workspace_type' => 'creative']);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

        $lead = Lead::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Other Lead',
            'status' => 'new',
        ]);

        $this->actingAs($userA)
            ->get(route('tenant.leads.show', ['tenant' => $tenantA->id, 'lead' => $lead->id]))
            ->assertStatus(404);
    }

    public function test_convert_creates_contact_and_updates_lead(): void
    {
        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'name' => 'Convert Lead',
            'email' => 'convert@example.com',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)
            ->post(route('tenant.leads.convert', ['tenant' => $tenant->id, 'lead' => $lead->id]), [
                'create_opportunity' => true,
            ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_contact_id);
        $this->assertNotNull($lead->converted_opportunity_id);
    }
}
