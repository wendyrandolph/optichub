<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradePtoRequest;
use App\Models\TradePtoType;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PtoRequestController extends Controller
{
    public function store(Request $request, Tenant $tenant)
    {
        $user = auth()->user();
        if (!$user || (int) $user->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $data = $request->validate([
            'trade_pto_type_id' => [
                'required',
                Rule::exists('trade_pto_types', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'hours_requested' => ['required', 'numeric', 'min:0.5'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $type = TradePtoType::where('tenant_id', $tenant->id)
            ->where('id', $data['trade_pto_type_id'])
            ->where('is_active', true)
            ->first();

        if (!$type) {
            return back()->with('error_message', 'Selected PTO type is not active.');
        }

        TradePtoRequest::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'trade_pto_type_id' => $data['trade_pto_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'hours_requested' => $data['hours_requested'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('success_message', 'PTO request submitted.');
    }
}
