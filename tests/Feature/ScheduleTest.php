<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserWorkPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_is_tenant_scoped(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Creative A',
            'workspace_type' => 'creative',
        ]);
        $tenantB = Tenant::create([
            'name' => 'Creative B',
            'workspace_type' => 'creative',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'first_name' => 'Alex',
            'last_name' => 'User',
            'email' => 'alex-a@example.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);
        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'first_name' => 'Blake',
            'last_name' => 'User',
            'email' => 'blake-b@example.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        Task::create([
            'tenant_id' => $tenantB->id,
            'assigned_user_id' => $userB->id,
            'title' => 'Other tenant task',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($userA, 'web')
            ->get(route('tenant.schedule.index', ['tenant' => $tenantA->id]));

        $response->assertOk();
        $response->assertDontSee('Other tenant task');
    }

    public function test_tasks_render_in_bucket_sections(): void
    {
        $tenant = Tenant::create([
            'name' => 'Creative Schedule',
            'workspace_type' => 'creative',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Riley',
            'last_name' => 'User',
            'email' => 'riley@example.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $today = Carbon::today();
        $weekEnd = Carbon::now()->endOfWeek();

        Task::create([
            'tenant_id' => $tenant->id,
            'assigned_user_id' => $user->id,
            'title' => 'Overdue task',
            'due_date' => $today->copy()->subDay(),
            'status' => 'todo',
        ]);
        Task::create([
            'tenant_id' => $tenant->id,
            'assigned_user_id' => $user->id,
            'title' => 'Today task',
            'due_date' => $today,
            'status' => 'todo',
        ]);
        Task::create([
            'tenant_id' => $tenant->id,
            'assigned_user_id' => $user->id,
            'title' => 'This week task',
            'due_date' => $today->copy()->addDay(),
            'status' => 'todo',
        ]);
        Task::create([
            'tenant_id' => $tenant->id,
            'assigned_user_id' => $user->id,
            'title' => 'Later task',
            'due_date' => $weekEnd->copy()->addDay(),
            'status' => 'todo',
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.schedule.index', ['tenant' => $tenant->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Overdue', 'Overdue task']);
        $response->assertSeeInOrder(['Today', 'Today task']);
        $response->assertSeeInOrder(['This Week', 'This week task']);
        $response->assertSeeInOrder(['Later', 'Later task']);
    }

    public function test_schedule_blocks_cross_tenant_access(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'workspace_type' => 'creative',
        ]);
        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'workspace_type' => 'creative',
        ]);

        $user = User::create([
            'tenant_id' => $tenantA->id,
            'first_name' => 'Jordan',
            'last_name' => 'User',
            'email' => 'jordan@example.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('tenant.schedule.index', ['tenant' => $tenantB->id]));

        $response->assertNotFound();
    }

    public function test_work_preference_defaults_are_created(): void
    {
        $tenant = Tenant::create([
            'name' => 'Creative Default',
            'workspace_type' => 'creative',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Sam',
            'last_name' => 'User',
            'email' => 'sam@example.test',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('tenant.schedule.index', ['tenant' => $tenant->id]))
            ->assertOk();

        $this->assertDatabaseHas('user_work_preferences', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'weekly_target_hours' => 40,
        ]);
    }
}
