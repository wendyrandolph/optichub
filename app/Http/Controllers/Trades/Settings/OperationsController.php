<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    public function index(Tenant $tenant)
    {
        return view('trades.settings.operations', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'trades_recurring_enabled' => ['sometimes', 'nullable', 'boolean'],
            'trades_warranty_scope' => ['sometimes', 'string', Rule::in(['job', 'line_item'])],
            'trades_work_type' => ['sometimes', 'string', Rule::in(['residential', 'commercial', 'both'])],
        ]);

        $updates = [];

        if ($request->has('trades_recurring_enabled')) {
            $updates['trades_recurring_enabled'] = (bool) ($data['trades_recurring_enabled'] ?? false);
        }

        if ($request->filled('trades_warranty_scope')) {
            $updates['trades_warranty_scope'] = $data['trades_warranty_scope'];
        }

        if ($request->filled('trades_work_type')) {
            $updates['trades_work_type'] = $data['trades_work_type'];
        }

        if (!empty($updates)) {
            $tenant->update($updates);
        }

        return back()->with('success', 'Operations settings updated.');
    }
}
