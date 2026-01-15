<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ClientCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $stageWeights = [
            'New' => 22,
            'Qualified' => 20,
            'Proposal' => 18,
            'Negotiation' => 12,
            'Won' => 14,
            'Lost' => 14,
        ];

        $stage = $this->weightedPick($stageWeights);

        // Typical value ranges for web/creative services
        $estimated = match ($stage) {
            'New' => $this->faker->randomFloat(2, 500, 3500),
            'Qualified' => $this->faker->randomFloat(2, 1500, 8000),
            'Proposal' => $this->faker->randomFloat(2, 2500, 15000),
            'Negotiation' => $this->faker->randomFloat(2, 3000, 25000),
            'Won' => $this->faker->randomFloat(2, 2000, 30000),
            'Lost' => $this->faker->randomFloat(2, 500, 15000),
            default => $this->faker->randomFloat(2, 500, 10000),
        };

        $probability = match ($stage) {
            'New' => $this->faker->numberBetween(5, 20),
            'Qualified' => $this->faker->numberBetween(20, 45),
            'Proposal' => $this->faker->numberBetween(40, 65),
            'Negotiation' => $this->faker->numberBetween(60, 85),
            'Won' => 100,
            'Lost' => 0,
            default => null,
        };

        // Close date: won/lost tends to be in past; open tends to be in future
        $closeDate = match ($stage) {
            'Won', 'Lost' => Carbon::today()->subDays($this->faker->numberBetween(1, 60)),
            default => Carbon::today()->addDays($this->faker->numberBetween(3, 90)),
        };

        $title = $this->faker->randomElement([
            'Website redesign',
            'Landing page + lead capture',
            'SEO + content sprint',
            'Maintenance plan upgrade',
            'Brand refresh + new site',
            'Paid ads setup + tracking',
            'Website performance + Core Web Vitals',
        ]);

        $description = $this->faker->optional(0.9)->paragraphs(2, true);

        $lostReason = null;
        if ($stage === 'Lost') {
            $lostReason = $this->faker->randomElement([
                'Budget not approved',
                'Went with another vendor',
                'Timeline didn’t work out',
                'Ghosted / no response',
                'Scope changed',
            ]);
        }

        // NOTE: These will be overridden in Seeder to guarantee valid foreign keys per-tenant.
        $base = [
            'tenant_id' => Tenant::query()->value('id') ?? 1,
            'company_id' => ClientCompany::query()->inRandomOrder()->value('id'),
            'title' => $title,
            'stage' => $stage,
            'estimated_value' => $estimated,
            'close_date' => $closeDate,
            'probability' => $probability,
            'description' => $description,
            'created_by' => User::query()->inRandomOrder()->value('id'),
            'lost_reason' => $lostReason,
            'flagged_overdue_at' => null,
        ];

        // Optional Phase 4+ fields (only set if present)
        if (Schema::hasColumn('opportunities', 'owner_id')) {
            $base['owner_id'] = User::query()->inRandomOrder()->value('id');
        }
        if (Schema::hasColumn('opportunities', 'next_step')) {
            $base['next_step'] = $this->faker->randomElement([
                'Send proposal',
                'Follow up via email',
                'Schedule discovery call',
                'Confirm scope + timeline',
                'Get contract signed',
            ]);
        }
        if (Schema::hasColumn('opportunities', 'next_followup_at')) {
            // Some will be overdue, some upcoming
            $base['next_followup_at'] = $this->faker->boolean(25)
                ? Carbon::now()->subDays($this->faker->numberBetween(1, 14))
                : Carbon::now()->addDays($this->faker->numberBetween(1, 21));
        }

        return $base;
    }

    private function weightedPick(array $weights): string
    {
        $sum = array_sum($weights);
        $rand = mt_rand(1, $sum);
        foreach ($weights as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) return $key;
        }
        return array_key_first($weights);
    }
}
