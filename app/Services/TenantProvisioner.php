<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantRole;

class TenantProvisioner
{
    public function applyTradesDefaults(Tenant $tenant): void
    {
        $roles = ['Admin', 'Dispatcher', 'Tech', 'Lead Tech'];

        foreach ($roles as $role) {
            TenantRole::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => $role,
            ]);
        }

        $updates = [];
        if ($tenant->pricing_visible_to_techs === null) {
            $updates['pricing_visible_to_techs'] = false;
        }
        if ($tenant->reminders_enabled === null) {
            $updates['reminders_enabled'] = true;
        }
        if ($tenant->trades_recurring_enabled === null) {
            $updates['trades_recurring_enabled'] = false;
        }
        if ($tenant->trades_work_type === null) {
            $updates['trades_work_type'] = 'both';
        }

        if (!empty($updates)) {
            $tenant->update($updates);
        }
    }
}
