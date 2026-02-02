<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplatePhase;
use App\Models\ProjectTemplateTask;

class ProjectTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            $this->seedForTenant($tenant);
        }
    }

    public function seedForTenant(Tenant $tenant): void
    {
        $hasTemplates = ProjectTemplate::where('tenant_id', $tenant->id)->exists();
        if ($hasTemplates) {
            return;
        }

        $workspaceType = $tenant->workspace_type ?? 'creative';
        if ($workspaceType === 'trades') {
            $this->seedTradesTemplates($tenant->id);
        } else {
            $this->seedCreativeTemplates($tenant->id);
        }
    }

    protected function seedCreativeTemplates(int $tenantId): void
    {
        $template = ProjectTemplate::create([
            'tenant_id' => $tenantId,
            'workspace_type' => 'creative',
            'name' => 'Brand & Web Launch',
            'description' => 'Kickoff through delivery with review points built in.',
            'is_active' => true,
        ]);

        $phases = $this->createPhases($tenantId, $template->id, [
            ['name' => 'Discovery', 'sort_order' => 1],
            ['name' => 'Design', 'sort_order' => 2],
            ['name' => 'Review', 'sort_order' => 3],
            ['name' => 'Delivery', 'sort_order' => 4],
        ]);

        $this->createTasks($tenantId, $template->id, [
            ['title' => 'Kickoff call + goals', 'phase' => 'Discovery', 'sort_order' => 1, 'due_offset_days' => 1],
            ['title' => 'Gather assets + references', 'phase' => 'Discovery', 'sort_order' => 2, 'due_offset_days' => 3],
            ['title' => 'Design concepts (round 1)', 'phase' => 'Design', 'sort_order' => 3, 'due_offset_days' => 7],
            ['title' => 'Client feedback review', 'phase' => 'Review', 'sort_order' => 4, 'due_offset_days' => 10],
            ['title' => 'Final files + handoff', 'phase' => 'Delivery', 'sort_order' => 5, 'due_offset_days' => 14],
        ], $phases);

        $templateTwo = ProjectTemplate::create([
            'tenant_id' => $tenantId,
            'workspace_type' => 'creative',
            'name' => 'Retainer Monthly Cycle',
            'description' => 'Recurring monthly tasks with approvals.',
            'is_active' => true,
        ]);

        $phasesTwo = $this->createPhases($tenantId, $templateTwo->id, [
            ['name' => 'Plan', 'sort_order' => 1],
            ['name' => 'Produce', 'sort_order' => 2],
            ['name' => 'Approve', 'sort_order' => 3],
            ['name' => 'Deliver', 'sort_order' => 4],
        ]);

        $this->createTasks($tenantId, $templateTwo->id, [
            ['title' => 'Monthly priorities', 'phase' => 'Plan', 'sort_order' => 1, 'due_offset_days' => 1],
            ['title' => 'Draft work items', 'phase' => 'Produce', 'sort_order' => 2, 'due_offset_days' => 5],
            ['title' => 'Client review notes', 'phase' => 'Approve', 'sort_order' => 3, 'due_offset_days' => 8],
            ['title' => 'Final deliverables', 'phase' => 'Deliver', 'sort_order' => 4, 'due_offset_days' => 12],
        ], $phasesTwo);
    }

    protected function seedTradesTemplates(int $tenantId): void
    {
        $template = ProjectTemplate::create([
            'tenant_id' => $tenantId,
            'workspace_type' => 'trades',
            'name' => 'Service Call',
            'description' => 'From intake to invoice for field work.',
            'is_active' => true,
        ]);

        $phases = $this->createPhases($tenantId, $template->id, [
            ['name' => 'Intake', 'sort_order' => 1],
            ['name' => 'On Site', 'sort_order' => 2],
            ['name' => 'Wrap Up', 'sort_order' => 3],
        ]);

        $this->createTasks($tenantId, $template->id, [
            ['title' => 'Confirm scope + schedule', 'phase' => 'Intake', 'sort_order' => 1, 'due_offset_days' => 1],
            ['title' => 'On-site work complete', 'phase' => 'On Site', 'sort_order' => 2, 'due_offset_days' => 2],
            ['title' => 'Photos + notes', 'phase' => 'Wrap Up', 'sort_order' => 3, 'due_offset_days' => 2],
            ['title' => 'Send invoice', 'phase' => 'Wrap Up', 'sort_order' => 4, 'due_offset_days' => 3],
        ], $phases);

        $templateTwo = ProjectTemplate::create([
            'tenant_id' => $tenantId,
            'workspace_type' => 'trades',
            'name' => 'Install / Repair',
            'description' => 'Multi-step job with customer approvals.',
            'is_active' => true,
        ]);

        $phasesTwo = $this->createPhases($tenantId, $templateTwo->id, [
            ['name' => 'Prep', 'sort_order' => 1],
            ['name' => 'Install', 'sort_order' => 2],
            ['name' => 'Verify', 'sort_order' => 3],
            ['name' => 'Closeout', 'sort_order' => 4],
        ]);

        $this->createTasks($tenantId, $templateTwo->id, [
            ['title' => 'Confirm materials', 'phase' => 'Prep', 'sort_order' => 1, 'due_offset_days' => 1],
            ['title' => 'Install work complete', 'phase' => 'Install', 'sort_order' => 2, 'due_offset_days' => 3],
            ['title' => 'Customer walkthrough', 'phase' => 'Verify', 'sort_order' => 3, 'due_offset_days' => 4],
            ['title' => 'Final invoice sent', 'phase' => 'Closeout', 'sort_order' => 4, 'due_offset_days' => 5],
        ], $phasesTwo);
    }

    protected function createPhases(int $tenantId, int $templateId, array $phases): array
    {
        $map = [];

        foreach ($phases as $phase) {
            $record = ProjectTemplatePhase::create([
                'tenant_id' => $tenantId,
                'project_template_id' => $templateId,
                'name' => $phase['name'],
                'sort_order' => $phase['sort_order'] ?? 0,
            ]);
            $map[$phase['name']] = $record->id;
        }

        return $map;
    }

    protected function createTasks(int $tenantId, int $templateId, array $tasks, array $phaseMap): void
    {
        foreach ($tasks as $task) {
            ProjectTemplateTask::create([
                'tenant_id' => $tenantId,
                'project_template_id' => $templateId,
                'project_template_phase_id' => $task['phase'] ? ($phaseMap[$task['phase']] ?? null) : null,
                'title' => $task['title'],
                'description' => $task['description'] ?? null,
                'sort_order' => $task['sort_order'] ?? 0,
                'due_offset_days' => $task['due_offset_days'] ?? null,
            ]);
        }
    }
}
