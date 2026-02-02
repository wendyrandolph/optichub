<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SupportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereIn('role', ['provider', 'super_admin', 'superadmin'])
            ->update(['can_manage_support' => true]);
    }
}
