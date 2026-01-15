<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantHomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_creative_tenant_redirects_to_creative_dashboard(): void
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
            ->get(route('tenant.home', ['tenant' => $tenant->id]));

        $response->assertRedirect(route('tenant.dashboards.index', ['tenant' => $tenant->id]));
    }

    public function test_trades_admin_redirects_to_trades_dashboard(): void
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
            ->get(route('tenant.home', ['tenant' => $tenant->id]));

        $response->assertRedirect(route('tenant.trades.dashboard', ['tenant' => $tenant->id]));
    }

    public function test_trades_tech_redirects_to_field_today(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Taylor',
            'last_name' => 'Tech',
            'username' => 'tech.trades',
            'email' => 'tech@trades.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.home', ['tenant' => $tenant->id]));

        $response->assertRedirect(route('tenant.trades.field.today', ['tenant' => $tenant->id]));
    }
}
