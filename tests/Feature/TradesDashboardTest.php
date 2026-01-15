<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_trades_dashboard_loads_with_operations_overview(): void
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
            ->get(route('tenant.trades.dashboard', ['tenant' => $tenant->id]));

        $response->assertOk();
        $response->assertSee('Operations Overview');
        $response->assertDontSee('>Reports<', false);
    }

    public function test_trades_reports_pages_still_load(): void
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

        $this->actingAs($user, 'web')
            ->get(route('tenant.trades.reports.schedule', ['tenant' => $tenant->id]))
            ->assertOk();

        $this->actingAs($user, 'web')
            ->get(route('tenant.trades.reports.jobs', ['tenant' => $tenant->id]))
            ->assertOk();

        $this->actingAs($user, 'web')
            ->get(route('tenant.trades.reports.tech', ['tenant' => $tenant->id]))
            ->assertOk();
    }
}
