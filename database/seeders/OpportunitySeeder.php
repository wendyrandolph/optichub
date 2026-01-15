<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ClientCompany;
use App\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->command?->warn('No tenants found. Create a tenant first, then rerun.');
            return;
        }

        foreach ($tenants as $tenant) {
            $users = User::query()
                ->where('tenant_id', $tenant->id)
                ->get();

            // Leads (preferred) for this tenant
            $leads = Lead::query()
                ->where('tenant_id', $tenant->id)
                ->with('company:id,company_name')
                ->get();
            // Companies fallback
            $orgs = ClientCompany::query()
                ->where('tenant_id', $tenant->id)
                ->get();

            $count = 18; // per tenant (tweak as desired)

            Opportunity::factory()
                ->count($count)
                ->make()
                ->each(function ($opp) use ($tenant, $users, $orgs, $leads) {

                    $opp->tenant_id = $tenant->id;

                    // created_by / owner_id
                    if ($users->isNotEmpty()) {
                        $creator = $users->random();
                        $opp->created_by = $creator->id;

                        if (Schema::hasColumn('opportunities', 'owner_id')) {
                            $opp->owner_id = $users->random()->id;
                        }
                    }

                    // prefer linking to an existing lead (and its company)
                    if ($leads->isNotEmpty()) {
                        $lead = $leads->random();
                        $opp->lead_id = $lead->id;
                        $opp->company_id = $lead->company_id ?: null;
                    } else {
                        $opp->lead_id = null;
                        $opp->company_id = $orgs->isNotEmpty() ? $orgs->random()->id : null;
                    }

                    // Make overdue signal consistent if you use flagged_overdue_at
                    if (Schema::hasColumn('opportunities', 'next_followup_at')) {
                        if ($opp->next_followup_at && Carbon::parse($opp->next_followup_at)->isPast()) {
                            $opp->flagged_overdue_at = Carbon::now()->subHours(rand(1, 72));
                        }
                    }

                    // Make won/lost “feel real”
                    if (in_array(strtolower($opp->stage), ['won', 'lost'], true)) {
                        if (Schema::hasColumn('opportunities', 'expected_close_date')) {
                            $opp->expected_close_date = Carbon::today()->subDays(rand(1, 60));
                        }
                        if (strtolower($opp->stage) === 'won') {
                            $opp->probability = 100;
                        } else {
                            $opp->probability = 0;
                            $opp->lost_reason = $opp->lost_reason ?: 'Went with another vendor';
                        }

                        // Usually no future followup once closed
                        if (Schema::hasColumn('opportunities', 'next_followup_at')) {
                            $opp->next_followup_at = null;
                        }
                    }

                    $opp->save();
                });
        }

        $this->command?->info('Opportunities seeded successfully.');
    }
}
