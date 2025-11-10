<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Phase;
use Illuminate\Support\Facades\Log;

class ProjectPhaseSeeder extends Seeder
{
    public function run(): void
    {
        // Define a few typical project phases
        $defaultPhases = [
            ['name' => 'Planning',       'code' => 'PLN', 'sort_order' => 1, 'description' => 'Initial strategy and planning phase.'],
            ['name' => 'Design',         'code' => 'DSN', 'sort_order' => 2, 'description' => 'Design and approval stage.'],
            ['name' => 'Execution',      'code' => 'EXE', 'sort_order' => 3, 'description' => 'Work is being executed and tracked.'],
            ['name' => 'Review',         'code' => 'REV', 'sort_order' => 4, 'description' => 'Internal review and feedback.'],
            ['name' => 'Completion',     'code' => 'CMP', 'sort_order' => 5, 'description' => 'Project wrap-up and client signoff.'],
        ];

        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Seed or create projects before running this seeder.');
            return;
        }

        foreach ($projects as $project) {
            foreach ($defaultPhases as $phaseData) {
                Phase::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'name' => $phaseData['name'],
                    ],
                    [
                        'tenant_id'   => $project->tenant_id ?? null,
                        'code'        => $phaseData['code'],
                        'sort_order'  => $phaseData['sort_order'],
                        'description' => $phaseData['description'],
                    ]
                );
            }
        }

        $this->command->info('✅ Default project phases seeded successfully for all projects.');
        Log::info('ProjectPhaseSeeder completed.', ['projects' => $projects->count()]);
    }
}
