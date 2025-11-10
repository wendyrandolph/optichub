<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Task, Project, Tenant, TeamMember, User, Phase};
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Work across all tenants
        $tenants = Tenant::all();
        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Seed tenants first.');
            return;
        }

        foreach ($tenants as $tenant) {
            // Projects for this tenant
            $project = Project::where('tenant_id', $tenant->id)->first();
            if (! $project) {
                $this->command->warn("No project for tenant {$tenant->id}. Seed projects first.");
                continue;
            }

            // IMPORTANT: ignore tenant/global scopes while seeding
            $members = TeamMember::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->get();

            if ($members->isEmpty()) {
                $this->command->warn("No team members for tenant {$tenant->id} (without scopes).");
                continue;
            }

            $admins = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereIn('role', ['admin', 'provider', 'super_admin', 'superadmin'])
                ->get();

            $clients = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'client')
                ->get();

            $adminUser  = $admins->first()  ?? User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
            $clientUser = $clients->first() ?? $adminUser;
            $phaseId = Phase::where('tenant_id', $tenant->id)
                ->where('project_id', $project->id)
                ->value('id');

            $rows[] = [
                'tenant_id'   => $tenant->id,
                'project_id'  => $project->id,
                'phase_id'    => $phaseId,               // if you added it
                'title'       => 'Kickoff Meeting',
                'description' => 'Initial project kickoff...',
                'status'      => 'open',
                'priority'    => 'high',
                'assign_type' => 'admin',                 // if you added these cols
                'assign_id'   => $adminUser->id,
                'user_id'     => $adminUser->id,          // <- point to users.id
                'due_date'    => now()->addDays(3),
            ];
            [
                'tenant_id'   => $tenant->id,
                'project_id'  => $project->id,
                'phase_id'    => $phaseId,
                'title'       => 'Client Content Upload',
                'description' => 'Client to upload website content and assets.',
                'status'      => 'in_progress',
                'priority'    => 'medium',
                'assign_type' => 'client',
                'assign_id'   => $adminUser->id,
                'user_id'     => $adminUser->id,          // <- point to users.id
                'due_date'    => now()->addDays(7),
            ];
            [
                'tenant_id'   => $tenant->id,
                'project_id'  => $project->id,
                'phase_id'    => $phaseId,
                'title'       => 'Design Approval',
                'description' => 'Awaiting client feedback on homepage design.',
                'status'      => 'completed',
                'priority'    => 'low',
                'assign_type' => 'admin',
                'assign_id'   => $adminUser->id,
                'user_id'     => $adminUser->id,          // <- point to users.id
                'due_date'    => now()->subDays(2),
            ];


            foreach ($rows as $data) {
                Task::updateOrCreate(
                    [
                        'tenant_id' => $data['tenant_id'],
                        'project_id' => $data['project_id'],
                        'title'     => $data['title'],
                    ],
                    $data
                );
            }

            $this->command->info("✅ Seeded demo tasks for tenant {$tenant->id} (project {$project->id}).");
        }
    }
}
