<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TeamMember;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $actor = auth()->user();

        if (!$actor || $actor->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (!$actor->can_view_registered_users) {
            abort(403, 'Access denied.');
        }
        if ($tenant->registered_users_enabled === false) {
            abort(403, 'Registered users directory disabled for this tenant.');
        }

        $search = trim((string) $request->get('q', ''));

        $tenant->load([
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

        $userIds = $tenant->users?->pluck('id')->merge($tenant->clients?->pluck('userAccount.id') ?? collect())->filter()->unique();

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

        return view('settings.registered-users', [
            'tenant' => $tenant,
            'search' => $search,
            'lastLogins' => $lastLogins,
            'teamStatuses' => $teamStatuses,
            'teamMemberIds' => $teamMemberIds,
        ]);
    }
}
