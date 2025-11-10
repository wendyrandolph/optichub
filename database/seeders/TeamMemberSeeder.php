<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\User;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $user = User::first();

        if (!$tenant || !$user) {
            $this->command->warn("⚠️  No tenant or user found. Please run TenantSeeder and UserSeeder first.");
            return;
        }

        $data = [
            [
                'tenant_id' => $tenant->id,
                'firstName' => 'Samantha',
                'lastName' => 'Reed',
                'email' => 'samantha.reed@example.com',
                'phone' => '555-0101',
                'role' => 'admin',
                'title' => 'Project Manager',
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'firstName' => 'Carlos',
                'lastName' => 'Nguyen',
                'email' => 'carlos.nguyen@example.com',
                'phone' => '555-0123',
                'role' => 'employee',
                'title' => 'Senior Developer',
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'firstName' => 'Ava',
                'lastName' => 'Johnson',
                'email' => 'ava.johnson@example.com',
                'phone' => '555-0137',
                'role' => 'contractor',
                'title' => 'UX Designer',
                'status' => 'inactive',
            ],
        ];

        foreach ($data as $row) {
            TeamMember::updateOrCreate(
                ['email' => $row['email']],
                $row
            );
        }

        $this->command->info("✅ Seeded " . count($data) . " team members for tenant #{$tenant->id}");
    }
}
