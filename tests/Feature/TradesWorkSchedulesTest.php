<?php

namespace Tests\Feature;

use App\Models\AppointmentAssignment;
use App\Models\Client;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeBusinessHour;
use App\Models\TradeJob;
use App\Models\TradeWorkSchedule;
use App\Models\TradeWorkScheduleBlock;
use App\Models\User;
use App\Services\Trades\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TradesWorkSchedulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_save_company_hours(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'timezone' => 'UTC',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades',
            'email' => 'admin@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'hours' => [
                1 => [
                    'start_time' => '08:00',
                    'end_time' => '17:00',
                    'is_closed' => false,
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'web')
            ->patch(route('tenant.trades.settings.time.work-schedules.company', ['tenant' => $tenant->id]), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('trade_business_hours', [
            'tenant_id' => $tenant->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_closed' => 0,
        ]);
    }

    public function test_tenant_can_save_user_schedule_biweekly(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'timezone' => 'UTC',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades',
            'email' => 'admin@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
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

        $payload = [
            'cadence' => 'biweekly',
            'starts_on' => '2026-03-02',
            'is_active' => true,
            'blocks' => [
                0 => [
                    1 => ['start_time' => '09:00', 'end_time' => '12:00'],
                ],
                1 => [
                    2 => ['start_time' => '13:00', 'end_time' => '16:00'],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'web')
            ->patch(route('tenant.trades.settings.time.work-schedules.user', [
                'tenant' => $tenant->id,
                'user' => $tech->id,
            ]), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('trade_work_schedules', [
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'cadence' => 'biweekly',
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('trade_work_schedule_blocks', [
            'week_index' => 0,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
        $this->assertDatabaseHas('trade_work_schedule_blocks', [
            'week_index' => 1,
            'day_of_week' => 2,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00',
        ]);
    }

    public function test_availability_unavailable_when_overlapping_appointment_exists(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'timezone' => 'UTC',
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

        $schedule = TradeWorkSchedule::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'cadence' => 'weekly',
            'is_active' => true,
        ]);

        TradeWorkScheduleBlock::create([
            'schedule_id' => $schedule->id,
            'week_index' => 0,
            'day_of_week' => 4,
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
        ]);

        $location = ServiceLocation::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'address_line1' => '123 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
        ]);

        $job = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'service_location_id' => $location->id,
            'summary' => 'Job',
            'type' => 'service',
            'status' => 'open',
        ]);

        $start = Carbon::parse('2026-03-05 10:00:00', 'UTC');
        $appointment = TradeAppointment::create([
            'tenant_id' => $tenant->id,
            'trade_job_id' => $job->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'status' => 'scheduled',
        ]);

        AppointmentAssignment::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'user_id' => $tech->id,
            'presence_status' => 'assigned',
        ]);

        $service = app(AvailabilityService::class);
        $result = $service->isUserAvailable(
            $tech,
            Carbon::parse('2026-03-05 10:30:00', 'UTC'),
            Carbon::parse('2026-03-05 11:30:00', 'UTC')
        );

        $this->assertFalse($result->available);
        $this->assertContains('Booked', $result->reasons);
    }

    public function test_availability_unavailable_when_outside_schedule_blocks(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'timezone' => 'UTC',
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

        $schedule = TradeWorkSchedule::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'cadence' => 'weekly',
            'is_active' => true,
        ]);

        TradeWorkScheduleBlock::create([
            'schedule_id' => $schedule->id,
            'week_index' => 0,
            'day_of_week' => 4,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $service = app(AvailabilityService::class);
        $result = $service->isUserAvailable(
            $tech,
            Carbon::parse('2026-03-05 12:00:00', 'UTC'),
            Carbon::parse('2026-03-05 13:00:00', 'UTC')
        );

        $this->assertFalse($result->available);
        $this->assertContains('Outside hours', $result->reasons);
    }

    public function test_scheduling_still_works_but_flashes_availability_warnings(): void
    {
        $tenant = Tenant::create([
            'name' => 'Trades Co',
            'workspace_type' => 'trades',
            'timezone' => 'UTC',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin.trades',
            'email' => 'admin@trades.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
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

        $schedule = TradeWorkSchedule::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'cadence' => 'weekly',
            'is_active' => true,
        ]);

        TradeWorkScheduleBlock::create([
            'schedule_id' => $schedule->id,
            'week_index' => 0,
            'day_of_week' => 4,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'firstName' => 'Jamie',
            'lastName' => 'Client',
        ]);

        $location = ServiceLocation::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'address_line1' => '123 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
        ]);

        $job = TradeJob::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'service_location_id' => $location->id,
            'summary' => 'Job',
            'type' => 'service',
            'status' => 'open',
        ]);

        $payload = [
            'trade_job_id' => $job->id,
            'start_at' => '2026-03-05 12:00',
            'end_at' => '2026-03-05 13:00',
            'status' => 'scheduled',
            'tech_ids' => [$tech->id],
        ];

        $response = $this->actingAs($admin, 'web')
            ->post(route('tenant.trades.schedule.store', ['tenant' => $tenant->id]), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('availability_warnings');
    }
}
