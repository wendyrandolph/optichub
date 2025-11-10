<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {

        $projects = [
            [
                'tenant_id' => 1,
                'project_name' => 'Website Redesign',
                'description' => 'Full overhaul of the client website.',
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'project_name' => 'Mobile App UI',
                'description' => 'UI/UX for mobile app design system.',
                'status' => 'active',
            ],
        ];

        foreach ($projects as $data) {
            Project::firstOrCreate(
                ['tenant_id' => $data['tenant_id'], 'project_name' => $data['project_name']],
                $data
            );
        }
    }
}
