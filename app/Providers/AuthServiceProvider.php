<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Task::class => TaskPolicy::class,
        Project::class => ProjectPolicy::class,
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
        \App\Models\Tenant::class => \App\Policies\TenantPolicy::class,
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
        \App\Models\Opportunity::class => \App\Policies\OpportunityPolicy::class,
        \App\Models\TeamMember::class => \App\Policies\TeamMemberPolicy::class,
        \App\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Models\Report::class => \App\Policies\ReportPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        \App\Models\Lead::class => \App\Policies\LeadInsightPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // If it returns null, Laravel continues to run the normal authorization checks
        // ----------------------------------------------------
        // ** SUPERADMIN BYPASS: Grant universal authorization **
        // ----------------------------------------------------
        Gate::before(function ($user, $ability) {
            // Check if the user's role is 'super_admin' or 'superadmin'
            if (in_array($user->role ?? null, ['super_admin', 'superadmin'], true)) {
                // By returning 'true', this user bypasses all other Gates and Policies
                return true;
            }
        });
        // ----------------------------------------------------
    }
}
