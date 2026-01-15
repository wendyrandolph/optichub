<?php

namespace Tests\Feature;

use App\Models\AppointmentAssignment;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeJobTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesScheduleListTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_list_scopes_to_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'workspace_type' => 'trades',
        ]);
        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'workspace_type' => 'trades',
        ]);

        $admin = User::create([
            'tenant_id' => $tenantA->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.a',
            'email' => 'admin@a.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $clientA = Client::create([
            'tenant_id' => $tenantA->id,
            'firstName' => 'Alex',
            'lastName' => 'Alpha',
        ]);
        $clientB = Client::create([
            'tenant_id' => $tenantB->id,
            'firstName' => 'Betty',
            'lastName' => 'Beta',
        ]);

        $jobA = TradeJob::create([
            'tenant_id' => $tenantA->id,
            'client_id' => $clientA->id,
            'summary' => 'Tenant A Job',
            'type' => 'service',
            'status' => 'open',
        ]);
        $jobB = TradeJob::create([
            'tenant_id' => $tenantB->id,
            'client_id' => $clientB->id,
            'summary' => 'Tenant B Job',
            'type' => 'service',
            'status' => 'open',
        ]);

        TradeAppointment::create([
            'tenant_id' => $tenantA->id,
            'trade_job_id' => $jobA->id,
            'start_at' => Carbon::now()->addHour(),
            'end_at' => Carbon::now()->addHours(2),
            'status' => 'scheduled',
        ]);
        TradeAppointment::create([
            'tenant_id' => $tenantB->id,
            'trade_job_id' => $jobB->id,
            'start_at' => Carbon::now()->addHour(),
            'end_at' => Carbon::now()->addHours(2),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('tenant.trades.schedule.index', ['tenant' => $tenantA->id, 'view' => 'list']));

        $response->assertOk();
        $response->assertSee('Tenant A Job');
        $response->assertDontSee('Tenant B Job');
    }

    public function test_schedule_list_search_matches_job_and_client(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Search',
            'workspace_type' => 'trades',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.search',
            'email' => 'admin@search.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Olivia',
            'lastName' => 'Query',
            'email' => 'olivia@example.test',
        ]);

        $matchJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Boiler Service',
            'type' => 'service',
            'status' => 'open',
        ]);
        $otherJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Other Work',
            'type' => 'service',
            'status' => 'open',
        ]);

        TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $matchJob->id,
            'start_at' => Carbon::now()->addHour(),
            'end_at' => Carbon::now()->addHours(2),
            'status' => 'scheduled',
        ]);
        TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $otherJob->id,
            'start_at' => Carbon::now()->addHours(3),
            'end_at' => Carbon::now()->addHours(4),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('tenant.trades.schedule.index', [
                'tenant' => $tenant->id,
                'view' => 'list',
                'q' => 'Boiler',
            ]));

        $response->assertOk();
        $response->assertSee('Boiler Service');
        $response->assertDontSee('Other Work');
    }

    public function test_schedule_list_orders_by_start_time(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Order',
            'workspace_type' => 'trades',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.order',
            'email' => 'admin@order.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Casey',
            'lastName' => 'Order',
        ]);

        $firstJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'First Job',
            'type' => 'service',
            'status' => 'open',
        ]);
        $secondJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Second Job',
            'type' => 'service',
            'status' => 'open',
        ]);

        TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $firstJob->id,
            'start_at' => Carbon::now()->addHours(2),
            'end_at' => Carbon::now()->addHours(3),
            'status' => 'scheduled',
        ]);
        TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $secondJob->id,
            'start_at' => Carbon::now()->addHours(4),
            'end_at' => Carbon::now()->addHours(5),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('tenant.trades.schedule.index', ['tenant' => $tenant->id, 'view' => 'list']));

        $response->assertOk();
        $response->assertSeeInOrder(['First Job', 'Second Job']);
    }

    public function test_schedule_list_flags_conflicts_for_shared_tech(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Conflict',
            'workspace_type' => 'trades',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.conflict',
            'email' => 'admin@conflict.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $tech = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Pat',
            'last_name' => 'Tech',
            'username' => 'tech.conflict',
            'email' => 'tech@conflict.test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Sam',
            'lastName' => 'Client',
        ]);

        $jobA = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Overlap A',
            'type' => 'service',
            'status' => 'open',
        ]);
        $jobB = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Overlap B',
            'type' => 'service',
            'status' => 'open',
        ]);

        $start = Carbon::now()->addHours(2);
        $appointmentA = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $jobA->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(2),
            'status' => 'scheduled',
        ]);
        $appointmentB = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $jobB->id,
            'start_at' => $start->copy()->addHour(),
            'end_at' => $start->copy()->addHours(3),
            'status' => 'scheduled',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointmentA->id,
            'user_id' => $tech->id,
            'presence_status' => 'assigned',
        ]);
        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointmentB->id,
            'user_id' => $tech->id,
            'presence_status' => 'assigned',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('tenant.trades.schedule.index', ['tenant' => $tenant->id, 'view' => 'list']));

        $response->assertOk();
        $response->assertSee('Conflict');
    }

    public function test_schedule_list_can_filter_for_issue_rows(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Issues',
            'workspace_type' => 'trades',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.issues',
            'email' => 'admin@issues.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $techA = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Alex',
            'last_name' => 'Tech',
            'username' => 'tech.a',
            'email' => 'tech.a@test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);
        $techB = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Blake',
            'last_name' => 'Tech',
            'username' => 'tech.b',
            'email' => 'tech.b@test',
            'role' => 'tech',
            'password' => Hash::make('password123'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
        ]);

        $template = TradeJobTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => 'Template',
            'suggested_tech_count' => 2,
        ]);

        $understaffedJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Understaffed Job',
            'type' => 'service',
            'status' => 'open',
            'job_template_id' => $template->id,
        ]);
        $unassignedJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Unassigned Job',
            'type' => 'service',
            'status' => 'open',
        ]);
        $staffedJob = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'summary' => 'Staffed Job',
            'type' => 'service',
            'status' => 'open',
            'job_template_id' => $template->id,
        ]);

        $start = Carbon::now()->addHours(2);
        $understaffedAppt = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $understaffedJob->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'status' => 'scheduled',
        ]);
        $unassignedAppt = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $unassignedJob->id,
            'start_at' => $start->copy()->addHours(2),
            'end_at' => $start->copy()->addHours(3),
            'status' => 'scheduled',
        ]);
        $staffedAppt = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $staffedJob->id,
            'start_at' => $start->copy()->addHours(4),
            'end_at' => $start->copy()->addHours(5),
            'status' => 'scheduled',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $understaffedAppt->id,
            'user_id' => $techA->id,
            'presence_status' => 'assigned',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $staffedAppt->id,
            'user_id' => $techA->id,
            'presence_status' => 'assigned',
        ]);
        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $staffedAppt->id,
            'user_id' => $techB->id,
            'presence_status' => 'assigned',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('tenant.trades.schedule.index', [
                'tenant' => $tenant->id,
                'view' => 'list',
                'issues' => '1',
            ]));

        $response->assertOk();
        $response->assertSee('Understaffed Job');
        $response->assertSee('Unassigned Job');
        $response->assertDontSee('Staffed Job');
    }
}
