<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a reusable template for creating common tasks.
 * Uses HasTenantScope to ensure templates are filtered by the current tenant_id.
 */
class TaskTemplate extends Model
{
  use HasFactory, HasTenantScope;

  protected $table = 'task_templates';

  protected $fillable = [
    'tenant_id',
    'title',
    'description',
    // Note: Additional fields like default_priority or default_due_offset 
    // could be added here if needed.
  ];

  /**
   * Demonstrates how to use a template to create a new, organization-scoped Task.
   * This logic would typically live in a Service or Controller, but is included here 
   * for demonstration.
   *
   * @param int $userId The ID of the user to assign the new task to.
   * @param int $projectId The ID of the project the new task belongs to.
   * @return Task The newly created Task model instance.
   */
  public function createTaskFromTemplate(int $userId, int $projectId): Task
  {
    // The current tenant_id is automatically handled by the Task model's HasTenantScope
    $task = Task::create([
      'tenant_id'   => $this->tenant_id,
      'user_id'     => $userId,
      'project_id'  => $projectId,
      'title'       => $this->title,
      'description' => $this->description,
      'status'      => 'todo',
      'priority'    => 'medium',
      'due_date'    => now()->addDays(7), // Example default due date
    ]);

    return $task;
  }
}
