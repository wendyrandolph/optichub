<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantUserDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('q', ''));
        $tenantFilter = $request->get('tenant');

        $tenantQuery = Tenant::query()
            ->when($tenantFilter, fn($q) => $q->whereKey($tenantFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('users', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('clients', function ($cq) use ($search) {
                        $cq->where('firstName', 'like', "%{$search}%")
                            ->orWhere('lastName', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->orderBy('name');

        $tenants = $tenantQuery->paginate(12)->withQueryString();

        $tenants->load([
            'users' => function ($q) use ($search) {
                if ($search !== '') {
                    $q->where(function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            },
            'clients' => function ($q) use ($search) {
                if ($search !== '') {
                    $q->where(function ($cq) use ($search) {
                        $cq->where('firstName', 'like', "%{$search}%")
                            ->orWhere('lastName', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            },
            'clients.userAccount',
        ]);

        $userIds = $tenants->getCollection()
            ->flatMap(function ($tenant) {
                $tenantUsers = $tenant->users?->pluck('id') ?? collect();
                $clientUsers = $tenant->clients?->pluck('userAccount.id') ?? collect();
                return $tenantUsers->merge($clientUsers);
            })
            ->filter()
            ->unique()
            ->values();

        $lastLogins = $userIds->isEmpty()
            ? collect()
            : ActivityLog::query()
                ->selectRaw('user_id, MAX(created_at) as last_login')
                ->whereIn('user_id', $userIds)
                ->where('action', 'login')
                ->groupBy('user_id')
                ->pluck('last_login', 'user_id');

        $teamStatuses = $userIds->isEmpty()
            ? collect()
            : TeamMember::query()
                ->whereIn('user_id', $userIds)
                ->pluck('status', 'user_id');

        $teamMemberIds = $userIds->isEmpty()
            ? collect()
            : TeamMember::query()
                ->whereIn('user_id', $userIds)
                ->pluck('id', 'user_id');

        return view('admin.tenants.users-index', [
            'tenants' => $tenants,
            'search' => $search,
            'tenantFilter' => $tenantFilter,
            'tenantOptions' => Tenant::orderBy('name')->get(['id', 'name']),
            'lastLogins' => $lastLogins,
            'teamStatuses' => $teamStatuses,
            'teamMemberIds' => $teamMemberIds,
        ]);
    }
}
