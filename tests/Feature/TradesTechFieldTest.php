<?php

namespace Tests\Feature;

use App\Models\AppointmentAssignment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeJobTimer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesTechFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_trades_tech_redirects_to_field_today(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $tech = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Taylor',
            'last_name' => 'Tech',
            'username' => 'tech.trades',
            'email' => 'tech@trades.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($tech, 'web')
            ->get(route('tenant.home', ['tenant' => $tenant->id]));

        $response->assertRedirect(route('tenant.trades.field.today', ['tenant' => $tenant->id]));
    }

    public function test_trades_tech_cannot_access_job_create(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $tech = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Taylor',
            'last_name' => 'Tech',
            'username' => 'tech.trades',
            'email' => 'tech@trades.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($tech, 'web')
            ->get(route('tenant.trades.jobs.create', ['tenant' => $tenant->id]));

        $response->assertRedirect(route('tenant.trades.field.today', ['tenant' => $tenant->id]));
    }

    public function test_my_jobs_shows_only_assigned_or_active_timer_jobs(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
        ]);

        $tech = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Taylor',
            'last_name' => 'Tech',
            'username' => 'tech.trades',
            'email' => 'tech@trades.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $otherTech = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Sam',
            'last_name' => 'Tech',
            'username' => 'tech.other',
            'email' => 'other@trades.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
        ]);

        $jobAssigned = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'service',
            'status' => 'scheduled',
            'summary' => 'Assigned job',
        ]);

        $appointmentAssigned = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $jobAssigned->id,
            'start_at' => Carbon::now()->addHours(2),
            'end_at' => Carbon::now()->addHours(3),
            'status' => 'scheduled',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointmentAssigned->id,
            'user_id' => $tech->id,
            'presence_status' => 'assigned',
        ]);

        $jobOther = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'service',
            'status' => 'scheduled',
            'summary' => 'Other tech job',
        ]);

        $appointmentOther = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $jobOther->id,
            'start_at' => Carbon::now()->addHours(4),
            'end_at' => Carbon::now()->addHours(5),
            'status' => 'scheduled',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointmentOther->id,
            'user_id' => $otherTech->id,
            'presence_status' => 'assigned',
        ]);

        $jobTimerOnly = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'service',
            'status' => 'in_progress',
            'summary' => 'Active timer job',
        ]);

        TradeJobTimer::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $jobTimerOnly->id,
            'user_id' => $tech->id,
            'started_at' => Carbon::now()->subMinutes(15),
        ]);

        $response = $this->actingAs($tech, 'web')
            ->get(route('tenant.trades.jobs.index', ['tenant' => $tenant->id]));

        $response->assertStatus(200);
        $response->assertSee('Assigned job');
        $response->assertSee('Active timer job');
        $response->assertDontSee('Other tech job');
    }
}
