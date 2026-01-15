<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class TenantHomeController extends Controller
{
    public function index(Tenant $tenant): RedirectResponse
    {
        $workspaceType = $tenant->workspace_type ?? 'creative';
        $user = auth()->user();

        if ($workspaceType === 'trades') {
            if ($user?->isTech()) {
                return redirect()->route('tenant.trades.field.today', ['tenant' => $tenant->id]);
            }

            return redirect()->route('tenant.trades.dashboard', ['tenant' => $tenant->id]);
        }

        return redirect()->route('tenant.dashboards.index', ['tenant' => $tenant->id]);
    }
}
