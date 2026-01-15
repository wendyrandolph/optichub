<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesWorkspaceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_creative_tenant_cannot_access_trades_routes(): void
    {
        $tenant = Tenant::create([
            'name' => 'Creative Co',
            'workspace_type' => 'creative',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.creative',
            'email' => 'admin@creative.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.trades.index', ['tenant' => $tenant->id]));

        $response->assertStatus(404);
    }

    public function test_trades_tenant_can_access_trades_routes(): void
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
            ->get(route('tenant.trades.index', ['tenant' => $tenant->id]));

        $response->assertOk();
    }
}
