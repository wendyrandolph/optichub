<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
  use HandlesAuthorization;

  /**
   * Determine whether the user can view the model.
   * This method handles both Team Members (Admins) and Clients.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Task  $task
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function view(User $user, Task $task): Response|bool
  {
    // 1. Authorization for Team Members (Admins/Managers)
    // This is the simplest check: does the task belong to the user's organization?
    if ($user->role !== 'client') {
      $projectTenantId = $task->project?->tenant_id;
      if ($projectTenantId && (int) $projectTenantId === (int) $user->tenant_id) {
        return Response::allow();
      }

      // Note: You can add an extra check here for a 'provider' role if they should see everything.
      // if ($user->isProvider()) { return Response::allow(); }

      return Response::deny('You do not belong to the organization that owns this task.');
    }

    // ----------------------------------------------------------------------

    // 2. Authorization for Clients (Your original focus)

    // Ensure the client user actually has a linked contact_id.
    if (!$user->contact_id) {
      return Response::deny('Client user is not associated with a client account.');
    }

    $contactId = (int) $user->contact_id;
    $assignedDirectly = (int) ($task->contact_id ?? 0) === $contactId
      || (($task->assign_type ?? '') === 'client' && (int) ($task->assign_id ?? 0) === $contactId);

    if ($assignedDirectly) {
      return Response::allow();
    }

    $project = $task->project;
    if ($project && (int) ($project->contact_id ?? 0) === $contactId) {
      return Response::allow();
    }

    $clientCompanyId = $user->contact?->client_company_id;
    if ($project && $clientCompanyId && (int) ($project->client_company_id ?? 0) === (int) $clientCompanyId) {
      return Response::allow();
    }

    return Response::deny('The task is not associated with any of your projects.');
  }

  // You would add other methods like create(), update(), delete() following similar logic.
}
