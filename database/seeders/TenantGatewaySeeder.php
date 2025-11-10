<?php // database/seeders/TenantGatewaySeeder.php

namespace Database\Seeders;

use App\Models\TenantGatewayConfig;
use Illuminate\Database\Seeder;

class TenantGatewaySeeder extends Seeder
{
  public function run(): void
  {
    TenantGatewayConfig::updateOrCreate(
      ['tenant_id' => 1, 'gateway' => 'stripe'],
      [
        'credentials' => [
          'secret' => env('STRIPE_SECRET_KEY', ''),
          'publishable' => env('STRIPE_PUBLISHABLE_KEY', ''),
          'account_id' => env('STRIPE_ACCOUNT_ID', ''),
          'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
        'test_mode' => true,
        'status' => 'active',
      ]
    );
  }
}
