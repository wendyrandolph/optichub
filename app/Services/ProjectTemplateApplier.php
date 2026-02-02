<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Phase;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectTemplateApplier
{
    public function apply(ProjectTemplate $template, Project $project, ?int $ownerId = null): void
    {
        DB::transaction(function () use ($template, $project, $ownerId) {
            $phaseMap = [];

            $templatePhases = $template->phases()->get();
            if ($templatePhases->isNotEmpty()) {
                $project->update(['uses_phases' => true]);
            }

            foreach ($templatePhases as $phase) {
                $newPhase = Phase::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'name' => $phase->name,
                    'code' => $phase->code ?? null,
                    'sort_order' => $phase->sort_order ?? 0,
                    'description' => $phase->description,
                ]);

                $phaseMap[$phase->id] = $newPhase->id;
            }

            $startDate = $project->start_date ? Carbon::parse($project->start_date) : null;
            $templateTasks = $template->tasks()->get();

            foreach ($templateTasks as $task) {
                $dueDate = null;
                if ($startDate && $task->due_offset_days !== null) {
                    $dueDate = (clone $startDate)->addDays((int) $task->due_offset_days);
                }

                Task::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'user_id' => $ownerId,
                    'phase_id' => $task->project_template_phase_id
                        ? ($phaseMap[$task->project_template_phase_id] ?? null)
                        : null,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => 'todo',
                    'priority' => 'medium',
                    'due_date' => $dueDate,
                ]);
            }
        });
    }
}
