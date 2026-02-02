<?php

namespace App\Http\Controllers;

use App\Models\OutboundEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailLogController extends Controller
{
    public function index(Request $request, $tenant): View
    {
        $tenantModel = $tenant instanceof \App\Models\Tenant ? $tenant : \App\Models\Tenant::find($tenant);
        $tenantId = $tenantModel?->getKey() ?? (int) $tenant;
        $user = Auth::user();
        abort_unless($user && (int) $user->tenant_id === $tenantId, 403);

        $query = OutboundEmail::query()->where('tenant_id', $tenantId);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('to_email', 'like', '%' . $search . '%');
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $logs = $query->latest('queued_at')->paginate(25)->withQueryString();

        return view('emails.outbound-log', [
            'logs' => $logs,
            'tenantId' => $tenantId,
            'tenant' => $tenantModel,
        ]);
    }

    public function ignore(Request $request, $tenant, $log)
    {
        abort(404);
    }

    public function linkContact(Request $request, $tenant, $log)
    {
        abort(404);
    }
}
