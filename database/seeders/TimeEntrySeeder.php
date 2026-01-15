<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeEntrySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            return;
        }

        $user = User::where('tenant_id', $tenant->id)->first();
        if (! $user) {
            // minimal user for seeding
            $user = User::create([
                'tenant_id' => $tenant->id,
                'first_name' => 'Time',
                'last_name' => 'Seeder',
                'email' => 'time.seeder@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $project = Project::where('tenant_id', $tenant->id)->first();
        if (! $project) {
            $project = Project::create([
                'tenant_id' => $tenant->id,
                'project_name' => 'Seeded Project',
                'status' => 'open',
                'contact_id' => null,
            ]);
        }

        $task = Task::where('tenant_id', $tenant->id)->first();

        $dates = collect(range(0, 20))->map(fn ($i) => Carbon::today()->subDays(rand(0, 30)));

        DB::transaction(function () use ($tenant, $user, $project, $task, $dates) {
            foreach ($dates as $date) {
                TimeEntry::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'task_id' => $task?->id,
                    'date' => $date,
                    'hours' => round(rand(1, 8) + rand(0, 99) / 100, 2),
                    'billable' => rand(0, 10) > 1, // most are billable
                    'notes' => fake()->sentence(),
                ]);
            }
        });
    }
}
