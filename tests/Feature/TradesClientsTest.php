<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesClientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trades_user_can_view_clients_index(): void
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
            ->get(route('tenant.trades.clients.index', ['tenant' => $tenant->id]));

        $response->assertOk();
    }

    public function test_trades_user_can_create_client(): void
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
            ->post(route('tenant.trades.clients.store', ['tenant' => $tenant->id]), [
                'firstName' => 'Jamie',
                'lastName' => 'Client',
                'email' => 'jamie@trades.test',
                'phone' => '555-123-4567',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contacts', [
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
            'email' => 'jamie@trades.test',
        ]);
    }
}
