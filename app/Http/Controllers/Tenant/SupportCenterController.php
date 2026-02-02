<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\KbCategory;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportCenterController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->route('tenant');
        $workspaceType = $tenant?->workspace_type ?? 'creative';

        $categories = KbCategory::query()
            ->with(['articles' => function ($query) use ($workspaceType) {
                $query->where('is_published', true)
                    ->whereIn('audience', ['all', $workspaceType])
                    ->orderBy('sort_order')
                    ->limit(5);
            }])
            ->orderBy('sort_order')
            ->get();

        $tickets = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('tenant.support-index', [
            'tenant' => $tenant,
            'categories' => $categories,
            'tickets' => $tickets,
        ]);
    }
}
