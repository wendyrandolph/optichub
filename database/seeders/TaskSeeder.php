<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Task;
use App\Models\Contact;
use App\Models\Tenant;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Grab a tenant + a contact so we can assign client-visible tasks (prefer the Renlo test tenant)
        $tenant = Tenant::where('name', 'Renlo Test Tenant')->first() ?: Tenant::first();
        if (! $tenant) {
            $this->command->warn('TaskSeeder: no tenants found.');
            return;
        }

        $contact = Contact::where('tenant_id', $tenant->id)->first();
        if (! $contact) {
            $this->command->warn('TaskSeeder: no contacts found for tenant ' . $tenant->id);
            return;
        }

        // Use any existing projects for this tenant
        $projects = Project::where('tenant_id', $tenant->id)->get();
        if ($projects->isEmpty()) {
            $this->command->warn('TaskSeeder: no projects found for tenant ' . $tenant->id);
            return;
        }

        $this->command->info("TaskSeeder: seeding tasks for tenant #{$tenant->id} and contact #{$contact->id}");

        foreach ($projects as $project) {
            $this->seedTasksForProject($tenant->id, $project, $contact->id);
        }
    }

    protected function seedTasksForProject(int $tenantId, Project $project, int $contactId): void
    {
        $startDate = $project->start_date ? Carbon::parse($project->start_date) : null;

        // A mix of internal + client-facing tasks
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
                'phase_id'         => null,              // hook up later if you use phases
                'contact_id'       => $contactId,
                'user_id'          => null,              // can be set to a team member if you have one
                'title'            => $t['title'],
                'description'      => null,
                'due_date'         => $startDate
                    ? $startDate->copy()->addDays($t['due_offset'])
                    : now()->addDays($t['due_offset']),
                'status'           => $t['status'],      // e.g. 'todo', 'open', etc.
                'priority'         => $t['priority'],    // e.g. 'low', 'medium', 'high'
                'assign_type'      => $t['assign_type'], // 'user' or 'client'
                'assign_id'        => null,
                'client_visible'   => $t['client_visible'],
                'requires_approval' => $t['requires_approval'],
            ]);
        }
    }
}
