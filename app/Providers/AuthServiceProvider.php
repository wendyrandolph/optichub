<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\Upload;
use App\Models\Client;
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

        \App\Models\Tenant::class => \App\Policies\TenantPolicy::class,
        \App\Models\Opportunity::class => \App\Policies\OpportunityPolicy::class,
        \App\Models\TeamMember::class => \App\Policies\TeamMemberPolicy::class,
        \App\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Models\Report::class => \App\Policies\ReportPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
        \App\Models\ServiceLocation::class => \App\Policies\ServiceLocationPolicy::class,
        \App\Models\TradeJob::class => \App\Policies\TradeJobPolicy::class,
        \App\Models\TradeJobTemplate::class => \App\Policies\TradeJobTemplatePolicy::class,
        \App\Models\TradeAppointment::class => \App\Policies\TradeAppointmentPolicy::class,
        \App\Models\TradeQuote::class => \App\Policies\TradeQuotePolicy::class,
        \App\Models\TradeServicePlan::class => \App\Policies\TradeServicePlanPolicy::class,
        \App\Models\SupportTicket::class => \App\Policies\SupportTicketPolicy::class,
        \App\Models\Proposal::class => \App\Policies\ProposalPolicy::class,
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
        Gate::define('viewLeadInsights', function ($user) {
            $role = strtolower((string)($user->role ?? ''));
            return in_array($role, ['admin', 'super_admin', 'superadmin', 'provider'], true);
        });

        Gate::define('portal-view-project', function ($user, Project $project) {
            if (($user->role ?? '') !== 'client') {
                return false;
            }

            if ((int) $project->tenant_id !== (int) $user->tenant_id) {
                return false;
            }

            if (! $user->contact_id) {
                return false;
            }

            $client = Client::where('tenant_id', $user->tenant_id)
                ->where('id', $user->contact_id)
                ->first();

            if (! $client) {
                return false;
            }

            if ((int) $project->contact_id === (int) $client->id) {
                return true;
            }

            $clientCompanyId = $client->client_company_id;
            if (! $clientCompanyId) {
                return false;
            }

            if ((int) $project->client_company_id === (int) $clientCompanyId) {
                return true;
            }

            $projectContact = Client::where('tenant_id', $client->tenant_id)
                ->where('id', $project->contact_id)
                ->first();

            return $projectContact && (int) $projectContact->client_company_id === (int) $clientCompanyId;
        });

        Gate::define('portal-view-invoice', function ($user, Invoice $invoice) {
            if (($user->role ?? '') !== 'client') {
                return false;
            }

            return (int) $invoice->tenant_id === (int) $user->tenant_id
                && (int) $invoice->contact_id === (int) $user->contact_id;
        });

        Gate::define('portal-view-file', function ($user, Upload $file) {
            if (($user->role ?? '') !== 'client') {
                return false;
            }

            if ((int) $file->tenant_id !== (int) $user->tenant_id) {
                return false;
            }

            if (! $file->client_visible) {
                return false;
            }

            if ((int) $file->contact_id === (int) $user->contact_id) {
                return true;
            }

            return $file->project && (int) $file->project->contact_id === (int) $user->contact_id;
        });

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
