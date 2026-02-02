<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PortalAdminInvoicePreviewSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Renlo Demo Tenant'],
            [
                'subscription_status' => 'active',
                'support_email' => 'billing@renlo.example',
                'primary_color' => '#1F3C66',
                'secondary_color' => '#2563EB',
            ]
        );

        if ($tenant->subscription_status !== 'active') {
            $tenant->subscription_status = 'active';
            $tenant->save();
        }

        $periodEnd = Carbon::now()->addDays(30);

        Subscription::withAnyTenant()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_code' => 'pro',
                'status' => 'active',
                'current_period_end' => $periodEnd,
                'amount' => 199.00,
                'auto_renew' => true,
            ]
        );
    }
}
