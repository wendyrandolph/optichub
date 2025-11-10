<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Example default:
        // \App\Models\User::factory(10)->create();

        // Add your custom seeders here
        // $this->call(UserSeeder::class);
        $this->call([
            TenantOneSampleSeeder::class,
        ]);
        $this->call(TeamMemberSeeder::class);

        $this->call(TenantGatewaySeeder::class);
        $this->call(EmailSeeder::class);
        $this->call(ProjectSeeder::class);
        $this->call(ProjectPhaseSeeder::class);
        $this->call(TaskSeeder::class);
        $this->call(LeadSeeder::class);
    }
}
