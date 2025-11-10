<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevAdminSeeder extends Seeder
{
  public function run(): void
  {
    $tenant = Tenant::firstOrCreate(
      ['name' => 'Causey Web Solutions'],
      [
        'type'     => 'provider',
        'industry' => 'Web Development',
        'location' => 'Santaquin, Utah',
        'website'  => 'https://causeywebsolutions.com',
        'phone'    => '385-274-6528',
      ]
    );

    User::firstOrCreate(
      ['email' => 'admin@example.com'],
      [
        'tenant_id'            => $tenant->id,
        'username'             => 'admin',
        'first_name'           => 'Admin',
        'last_name'            => 'User',
        'password'             => Hash::make('secret123'),
        'role'                 => 'admin',
        'is_beta'              => 0,
        'must_change_password' => 0,
      ]
    );
  }
}
