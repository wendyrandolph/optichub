<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Client;

class TenantOneSampleSeeder extends Seeder
{
  public function run(): void
  {
    // 1) Ensure Tenant #1 exists
    $tenant = Tenant::firstOrCreate(
      ['id' => 1],
      [
        'type' => 'provider',
        'name' => 'Causey Web Solutions',
        'industry' => 'Web Development',
        'location' => 'Santaquin, Utah',
        'website' => 'https://causeywebsolutions.com',
        'phone' => '385-274-6528',
        'trial_status' => 'active',
        'subscription_status' => 'trialing',
      ]
    );

    // 2) Core users (easy test logins)
    $admin = User::firstOrCreate(
      ['email' => 'admin@causey.test'],
      [
        'tenant_id'  => $tenant->id,
        'username'   => 'admin',
        'first_name' => 'Admin',
        'last_name'  => 'User',
        'password'   => bcrypt('password123'),
        'role'       => 'provider', // your LoginController treats provider/admin as admins
        'is_beta'    => false,
        'must_change_password' => false,
      ]
    );

    User::firstOrCreate(
      ['email' => 'manager@causey.test'],
      [
        'tenant_id'  => $tenant->id,
        'username'   => 'manager',
        'first_name' => 'Studio',
        'last_name'  => 'Manager',
        'password'   => bcrypt('password123'),
        'role'       => 'admin',
        'is_beta'    => false,
        'must_change_password' => false,
      ]
    );

    // Employees
    User::factory()->count(2)->employee()->create([
      'tenant_id' => $tenant->id,
    ]);

    // 3) Clients (Tenant 1)
    $clients = Client::factory()->count(12)->create([
      'tenant_id' => $tenant->id,
    ]);

    // 4) One client-portal user for the first client
    if ($client = $clients->first()) {
      User::firstOrCreate(
        ['email' => 'client1@causey.test'],
        [
          'tenant_id'  => $tenant->id,
          'username'   => Str::slug($client->firstName . $client->lastName) . '-client',
          'first_name' => $client->firstName,
          'last_name'  => $client->lastName,
          'password'   => bcrypt('password123'),
          'role'       => 'client',
          'client_id'  => $client->id,
          'is_beta'    => false,
          'must_change_password' => false,
        ]
      );
    }
  }
}
