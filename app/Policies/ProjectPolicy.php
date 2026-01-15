<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Allow Provider/SaaS Admins to bypass authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // normalize just in case
        $role = strtolower((string) $user->role);
        $org  = strtolower((string) $user->organization_type);

        if (
            in_array($role, ['admin', 'provider'], true)   // ← include provider
            && in_array($org, ['provider', 'saas_tenant'], true)
        ) {
            return true;
        }
        return null;
    }

    public function create(User $user): bool
    {
        $role = strtolower((string) $user->role);
        $org  = strtolower((string) $user->organization_type);

        return in_array($org, ['provider', 'saas_tenant'], true)
            && in_array($role, ['admin', 'employee', 'provider'], true); // ← include provider
    }

    /**
     * Determine whether the user can view the list of projects (index page).
     *
     * IMPORTANT: This method only receives the $user. It MUST NOT receive a $project model.
     * The framework sends the Project model only to 'view', 'update', 'delete', etc.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // By returning true here, you allow all authenticated users to hit the controller.
        // The TenantScope will ensure they only see their own data.
        return true;
    }

    /**
     * Determine whether the user can view a single project.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Project $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        // Ensure the user's tenant ID matches the project's tenant ID
        return $user->tenant_id === $project->tenant_id;
    }




    /**
     * Determine whether the user can update the project.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Project $project
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        // Check tenant ownership first (redundant if TenantScope is working, but safe)
        if ($user->tenant_id !== $project->tenant_id) {
            return false;
        }

        $role = strtolower((string) $user->role);

        // Admin/owner/provider/staff/employee can update any project in their tenant.
        if (in_array($role, ['admin', 'owner', 'provider', 'staff', 'employee'], true)) {
            return true;
        }

        // Clients cannot update projects.
        return false;
    }

    /**
     * Determine whether the user can delete the project.
     * Deletion is usually reserved for admins or project managers.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Project $project
     * @return bool
     */
    public function delete(User $user, Project $project): bool
    {
        // Check tenant ownership first
        if ($user->tenant_id !== $project->tenant_id) {
            return false;
        }

        $role = strtolower((string) $user->role);

        // Admin/owner/provider/staff can delete.
        if (in_array($role, ['admin', 'owner', 'provider', 'staff'], true)) {
            return true;
        }

        // Allow deletion if the user is the original creator/manager
        return $project->user_id === $user->id;
    }
}
