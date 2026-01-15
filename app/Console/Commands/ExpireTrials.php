<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

class ExpireTrials extends Command
{
  protected $signature = 'optic:expire-trials';

  protected $description = 'Expire tenants whose trials have ended';

  public function handle(): int
  {
    // If the table doesn't exist (e.g. during migrate:fresh), bail out
    if (! Schema::hasTable('tenants')) {
      $this->warn('Tenants table does not exist yet. Skipping trial expiry.');
      return 0;
    }

    $now = now();

    $affected = Tenant::query()
      ->where('subscription_status', 'trialing')
      ->whereNotNull('trial_ends_at')
      ->where('trial_ends_at', '<', $now)
      ->update([
        'subscription_status' => 'expired', // or 'canceled' / 'past_due' if your enum demands
        'updated_at'          => $now,
      ]);

    $this->info("Expired {$affected} trial(s).");

    return 0;
  }
}
