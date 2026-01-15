<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\ClientCompany;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;

class TradesTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        $tenant = Tenant::firstOrCreate(
            ['id' => 2],
            [
                'type' => 'provider',
                'name' => 'Renlo Trades Demo',
                'industry' => 'Trades/Services',
                'location' => 'Denver, CO',
                'website' => 'https://renlo.test',
                'phone' => '555-201-8899',
                'trial_status' => 'active',
                'subscription_status' => 'trialing',
                'workspace_type' => 'trades',
                'trades_recurring_enabled' => true,
                'trades_work_type' => 'both',
            ]
        );

        app(TenantProvisioner::class)->applyTradesDefaults($tenant);

        $admin = User::firstOrCreate(
            ['email' => 'trades.admin@renlo.test'],
            [
                'tenant_id' => $tenant->id,
                'username' => 'trades-admin',
                'first_name' => 'Trades',
                'last_name' => 'Admin',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'is_beta' => true,
                'must_change_password' => false,
            ]
        );

        $company = ClientCompany::firstOrCreate(
            ['tenant_id' => $tenant->id, 'company_name' => 'Summit Property Group'],
            ['client_status' => 'active']
        );

        $contact = Contact::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'client@trades.test'],
            [
                'client_company_id' => $company->id,
                'firstName' => 'Jordan',
                'lastName' => 'Lee',
                'status' => 'active',
                'phone' => '555-555-0100',
            ]
        );

        User::firstOrCreate(
            ['email' => 'client@trades.test'],
            [
                'tenant_id' => $tenant->id,
                'username' => Str::slug($contact->firstName . $contact->lastName) . '-client',
                'first_name' => $contact->firstName,
                'last_name' => $contact->lastName,
                'password' => bcrypt('password123'),
                'role' => 'client',
                'contact_id' => $contact->id,
                'is_beta' => false,
                'must_change_password' => false,
            ]
        );

        $techEmail = 'tech1@trades.test';
        $baseUsername = 'trades-tech';
        $username = $baseUsername;
        if (User::where('username', $username)->where('email', '!=', $techEmail)->exists()) {
            $username = $baseUsername . '-' . $tenant->id;
        }

        User::updateOrCreate(
            ['email' => $techEmail],
            [
                'tenant_id' => $tenant->id,
                'username' => $username,
                'first_name' => 'Taylor',
                'last_name' => 'Tech',
                'password' => bcrypt('password123'),
                'role' => 'tech',
                'is_beta' => false,
                'must_change_password' => false,
            ]
        );
    }
}
