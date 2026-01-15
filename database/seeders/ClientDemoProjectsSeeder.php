<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Phase;
use App\Models\Task;
use App\Models\Upload;

class ClientDemoProjectsSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Pick a contact + tenant to attach everything to (prefer the Renlo test tenant if present)
        $tenant = \App\Models\Tenant::where('name', 'Renlo Test Tenant')->first() ?: \App\Models\Tenant::first();
        if (! $tenant) {
            $this->command->warn('ClientDemoProjectsSeeder: no tenants found.');
            return;
        }

        $contact = Contact::where('tenant_id', $tenant->id)->with('tenant')->first();

        if (! $contact) {
            $this->command->warn('ClientDemoProjectsSeeder: no contacts found. Create a contact first.');
            return;
        }

        $tenantId  = $tenant->id;
        $contactId = $contact->id;

        $fullName = $contact->name
            ?? trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));

        $this->command->info("Seeding demo projects for contact #{$contactId} ({$fullName})");

        // 2) Project definitions
        $projectsData = [
            [
                'name'        => 'Website Redesign & Launch',
                'status'      => 'open',
                'description' => 'Full redesign of marketing site with updated branding, mobile-first layout, and lead capture funnel.',
                'start_date'  => now()->subWeeks(3),
                'end_date'    => now()->addWeeks(3),
            ],
            [
                'name'        => 'Conference Landing Page & Registration',
                'status'      => 'open',
                'description' => 'Landing page and registration flow for upcoming industry conference.',
                'start_date'  => now()->subWeek(),
                'end_date'    => now()->addWeeks(2),
            ],
        ];

        foreach ($projectsData as $projectData) {
            $slug = Str::slug($projectData['name']) . '-demo';
            $existing = Project::where('tenant_id', $tenantId)->where('slug', $slug)->first();
            if ($existing) {
                $this->command->warn("Skipping seed: project slug '{$slug}' already exists for tenant #{$tenantId}");
                $project = $existing;
            } else {
            // Create project via relationship so contact_id is handled correctly
            $project = $contact->projects()->create([
                'tenant_id'    => $tenantId,
                'project_name' => $projectData['name'],
                'status'       => $projectData['status'],
                'description'  => $projectData['description'],
                'start_date'   => $projectData['start_date'],
                'end_date'     => $projectData['end_date'],
                'slug'         => $slug,
            ]);
            }

            $this->seedPhases($tenantId, $project);
            $this->seedTasks($tenantId, $project, $contactId);
            $this->seedUploads($tenantId, $project, $contact);
        }
    }

    protected function seedPhases(int $tenantId, Project $project): void
    {
        // Match your project_phases schema: name, code, sort_order, description
        $phases = [
            [
                'name'        => 'Discovery & Strategy',
                'code'        => 'DISC',
                'sort_order'  => 1,
                'description' => 'Understanding goals, audience, and requirements for this project.',
            ],
            [
                'name'        => 'Design & Content',
                'code'        => 'DESN',
                'sort_order'  => 2,
                'description' => 'Wireframes, mockups, and content gathering for key pages.',
            ],
            [
                'name'        => 'Build & Integration',
                'code'        => 'BLD',
                'sort_order'  => 3,
                'description' => 'Implementing designs, integrating tools, and setting up the environment.',
            ],
            [
                'name'        => 'Launch & QA',
                'code'        => 'LAUN',
                'sort_order'  => 4,
                'description' => 'Final checks, client review, and go-live.',
            ],
        ];

        foreach ($phases as $phaseData) {
            Phase::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'name'       => $phaseData['name'],
                ],
                [
                    'tenant_id'   => $tenantId,
                    'code'        => $phaseData['code'],
                    'sort_order'  => $phaseData['sort_order'],
                    'description' => $phaseData['description'],
                ]
            );
        }
    }

    protected function seedTasks(int $tenantId, Project $project, int $contactId): void
    {
        $startDate = $project->start_date ? \Carbon\Carbon::parse($project->start_date) : null;
        // Uses your tasks schema + new client_visible / requires_approval
        $tasks = [
            [
                'title'            => 'Internal kickoff – align on goals',
                'status'           => 'in-progress',
                'priority'         => 'high',
                'assign_type'      => 'user',
                'client_visible'   => false,
                'requires_approval' => false,
                'due_offset'       => -5,
            ],
            [
                'title'            => 'Client: complete project intake form',
                'status'           => 'todo',
                'priority'         => 'medium',
                'assign_type'      => 'client',
                'client_visible'   => true,
                'requires_approval' => false,
                'due_offset'       => -3,
            ],
            [
                'title'            => 'Client: review homepage concept',
                'status'           => 'todo',
                'priority'         => 'medium',
                'assign_type'      => 'client',
                'client_visible'   => true,
                'requires_approval' => true,
                'due_offset'       => 0,
            ],
            [
                'title'            => 'Set up staging environment',
                'status'           => 'todo',
                'priority'         => 'medium',
                'assign_type'      => 'user',
                'client_visible'   => false,
                'requires_approval' => false,
                'due_offset'       => 2,
            ],
            [
                'title'            => 'Client: final approval before launch',
                'status'           => 'todo',
                'priority'         => 'high',
                'assign_type'      => 'client',
                'client_visible'   => true,
                'requires_approval' => true,
                'due_offset'       => 7,
            ],
        ];

        foreach ($tasks as $t) {
            Task::create([
                'tenant_id'        => $tenantId,
                'project_id'       => $project->id,
                'phase_id'         => null,
                'contact_id'       => $contactId,
                'user_id'          => null,
                'title'            => $t['title'],
                'description'      => null,
                'due_date'         => $startDate
                    ? $startDate->copy()->addDays($t['due_offset'])
                    : now()->addDays($t['due_offset']),
                'status'           => $t['status'],      // matches your tasks.status
                'priority'         => $t['priority'],    // matches your tasks.priority
                'assign_type'      => $t['assign_type'], // 'user' or 'client'
                'assign_id'        => null,
                'client_visible'   => $t['client_visible'],
                'requires_approval' => $t['requires_approval'],
            ]);
        }
    }

    protected function seedUploads(int $tenantId, Project $project, Contact $contact): void
    {
        $demoFiles = [
            ['name' => 'OpticHub-Initial-Strategy-Brief.pdf', 'path' => 'demo/strategy-brief.pdf'],
            ['name' => 'Homepage-Wireframes-v1.pdf',          'path' => 'demo/homepage-wireframes-v1.pdf'],
            ['name' => 'Brand-Guidelines.pdf',                'path' => 'demo/brand-guidelines.pdf'],
        ];

        foreach ($demoFiles as $file) {
            $storedName = Str::random(12) . '-' . $file['name'];

            $contact->uploads()->create([
                'tenant_id'     => $tenantId,
                'project_id'    => $project->id,
                'task_id'       => null,
                'path'          => $file['path'],
                'original_name' => $file['name'],
                'stored_name'   => $storedName,
                'mime_type'     => 'application/pdf',
                'size'          => 1024 * 200,
                'client_visible' => true,
            ]);
        }
    }
}
