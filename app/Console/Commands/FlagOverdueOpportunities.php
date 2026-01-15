<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Opportunity;
use App\Services\OpportunityAutomation;
use Illuminate\Support\Carbon;

class FlagOverdueOpportunities extends Command
{
    protected $signature = 'opportunities:flag-overdue';
    protected $description = 'Mark opportunities with past-due follow-ups as overdue (and clear when resolved)';

    public function handle(): int
    {
        $automation = app(OpportunityAutomation::class);
        $now = Carbon::now();

        $overdue = Opportunity::whereNotIn('stage', ['won', 'lost'])
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<', $now)
            ->get();

        foreach ($overdue as $opp) {
            $opp->flagged_overdue_at = $now;
            $opp->saveQuietly();
            $automation->run('opportunity.followup_overdue', $opp);
        }

        // clear flag for those no longer overdue
        Opportunity::whereNotNull('flagged_overdue_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('next_followup_at')
                    ->orWhere('next_followup_at', '>=', $now)
                    ->orWhereIn('stage', ['won', 'lost']);
            })
            ->update(['flagged_overdue_at' => null]);

        $this->info('Overdue opportunities flagged: ' . $overdue->count());
        return Command::SUCCESS;
    }
}
