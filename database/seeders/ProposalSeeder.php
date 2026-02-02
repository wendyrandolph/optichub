<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\Project;
use App\Models\Client;
use App\Models\Proposal;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->orderBy('id')->take(2)->get();

        foreach ($tenants as $index => $tenant) {
            $project = Project::query()->where('tenant_id', $tenant->id)->first();
            $client = Client::query()->where('tenant_id', $tenant->id)->first();

            if (! $project || ! $client) {
                continue;
            }

            $label = $index === 0 ? 'Provider demo proposal' : 'Tenant demo proposal';

            Proposal::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'title' => $label,
                ],
                [
                    'project_id' => $project->id,
                    'client_id' => $client->id,
                    'status' => 'draft',
                    'content' => [
                        'goals' => 'Outline project goals and success criteria.',
                        'objectives' => 'Confirm scope, deliverables, and review steps.',
                        'investment' => 'Fixed fee with milestone payments.',
                        'timeline' => 'Kickoff next week, delivery within 4 weeks.',
                    ],
                ]
            );

            Proposal::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'title' => $label . ' (sent)',
                ],
                [
                    'project_id' => $project->id,
                    'client_id' => $client->id,
                    'status' => 'sent',
                    'unique_share_token' => Str::random(32),
                    'sent_at' => now()->subDays(2),
                    'content' => [
                        'goals' => 'Share the plan, pricing, and next steps.',
                        'objectives' => 'Align on milestones and approvals.',
                        'investment' => 'Deposit + two milestones.',
                        'timeline' => 'First delivery in 10 business days.',
                    ],
                ]
            );
        }
    }
}
