<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class ProfitabilityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            return;
        }

        $user = User::where('tenant_id', $tenant->id)->first();
        if (!$user) {
            return;
        }

        $project = Project::updateOrCreate(
            ['tenant_id' => $tenant->id, 'project_name' => 'Demo Web Build'],
            [
                'status' => 'open',
                'project_fee_total' => 4200,
                'external_costs' => 300,
                'target_hourly_rate' => 140,
                'budgeted_hours' => 30,
                'start_date' => Carbon::now()->subWeeks(2)->format('Y-m-d'),
                'end_date' => Carbon::now()->addWeeks(3)->format('Y-m-d'),
            ]
        );

        $project->timeEntries()->delete();

        $entries = collect([12, 14, 11])->map(fn ($hours) => [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'date' => Carbon::now()->subDays(rand(1, 10))->format('Y-m-d'),
            'hours' => $hours,
            'description' => 'Sample logged time',
            'billable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        TimeEntry::insert($entries);
    }
}
