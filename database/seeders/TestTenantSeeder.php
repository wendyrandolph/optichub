<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\ClientCompany;
use App\Models\Contact;
use Carbon\Carbon;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create or fetch a test tenant
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Renlo Test Tenant'],
            [
                'type'                => 'saas_tenant',
                'tax_enabled'         => 0,
                'tax_inclusive'       => 0,
                'default_currency'    => 'USD',
                'default_uses_phases' => 0,
                'subscription_status' => 'trialing',
                'trial_status'        => 'active',
                'primary_color'       => '#1C2E70',
                'secondary_color'     => '#172554',
                'accent_color'        => '#8FAF9A',
            ]
        );

        // Seed a handful of client companies with contacts
        $companies = [
            ['name' => 'Maple Studio', 'email' => 'hello@maplestudio.test'],
            ['name' => 'Brightline Creative', 'email' => 'hi@brightline.test'],
            ['name' => 'Northwind Services', 'email' => 'contact@northwind.test'],
        ];

        foreach ($companies as $companyData) {
            $company = ClientCompany::create([
                'tenant_id'     => $tenant->id,
                'company_name'  => $companyData['name'] ?? $companyData['company_name'] ?? null,
                'phone'         => '555-0100',
                'client_status' => 'active',
            ]);

            // Two contacts per company
            Contact::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => 'owner@' . Str::slug($companyData['name']) . '.test'],
                [
                    'client_company_id' => $company->id,
                    'firstName'         => 'Alex',
                    'lastName'          => 'Owner',
                    'phone'             => '555-0200',
                    'status'            => 'active',
                ]
            );

            Contact::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => 'ops@' . Str::slug($companyData['name']) . '.test'],
                [
                    'client_company_id' => $company->id,
                    'firstName'         => 'Jamie',
                    'lastName'          => 'Ops',
                    'phone'             => '555-0300',
                    'status'            => 'active',
                ]
            );
        }

        $this->command?->info('Test tenant, companies, and contacts seeded.');
    }
}
