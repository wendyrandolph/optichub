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
      // Check the task's project's tenant_id against the logged-in user's tenant_id.
      // Assumes Task -> belongsTo -> Project, and Project has a 'tenant_id' column.
      if ($task->project->tenant_id === $user->tenant_id) {
        return Response::allow();
      }

      // Note: You can add an extra check here for a 'provider' role if they should see everything.
      // if ($user->isProvider()) { return Response::allow(); }

      return Response::deny('You do not belong to the organization that owns this task.');
    }

    // ----------------------------------------------------------------------

    // 2. Authorization for Clients (Your original focus)

    // Ensure the client user actually has a linked client_id.
    if (!$user->client_id) {
      return Response::deny('Client user is not associated with a client account.');
    }

    // Check if the project linked to the task belongs to the logged-in client.
    // Assumes Task -> belongsTo -> Project, and Project has a 'client_id' column.
    if ($task->project->client_id === $user->client_id) {
      return Response::allow();
    }

    return Response::deny('The task is not associated with any of your projects.');
  }

  // You would add other methods like create(), update(), delete() following similar logic.
}
