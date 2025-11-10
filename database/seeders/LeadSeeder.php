<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LeadSeeder extends Seeder
{
  public function run(): void
  {
    // -------------------------------------------
    // Base lookups
    // -------------------------------------------
    $tenants = Tenant::all();

    if ($tenants->isEmpty()) {
      $this->command->warn('No tenants found — skipping lead seeding.');
      return;
    }

    $statuses = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];
    $sources  = ['web', 'referral', 'ads', 'email', 'event', 'other'];

    // -------------------------------------------
    // For each tenant, seed 15–40 leads
    // -------------------------------------------
    foreach ($tenants as $tenant) {
      $owners = User::where('tenant_id', $tenant->id)->pluck('id')->all();

      $leadCount = rand(15, 40);
      $faker = \Faker\Factory::create();

      for ($i = 0; $i < $leadCount; $i++) {
        $firstName = $faker->firstName();
        $lastName  = $faker->lastName();
        $email     = Str::lower($firstName . '.' . $lastName . '@example.com');

        Lead::create([
          'tenant_id'  => $tenant->id,
          'name'       => $firstName . ' ' . $lastName,
          'email'      => $email,
          'phone'      => $faker->phoneNumber(),
          'source'     => Arr::random($sources),
          'status'     => Arr::random($statuses),
          'owner_id'   => !empty($owners) ? Arr::random($owners) : null,
          'notes'      => $faker->optional()->sentence(rand(6, 15)),
          'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
          'updated_at' => now(),
        ]);
      }

      $this->command->info("Seeded {$leadCount} leads for tenant #{$tenant->id}");
    }
  }
}
