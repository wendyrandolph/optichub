<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', TeamMember::class);

        if ($admin = Auth::guard('admin')->user()) {
            if ((int) $admin->tenant_id === (int) $tenant->id) {
                $hasUserId = Schema::hasColumn('team_members', 'user_id');

                $existing = TeamMember::where('tenant_id', $tenant->id)
                    ->where(function ($q) use ($admin, $hasUserId) {
                        if ($hasUserId) {
                            $q->where('user_id', $admin->id)
                                ->orWhere('email', $admin->email);
                        } else {
                            $q->where('email', $admin->email);
                        }
                    })
                    ->first();

                if ($existing) {
                    if ($hasUserId && empty($existing->user_id)) {
                        $existing->user_id = $admin->id;
                    }
                    $existing->firstName = $existing->firstName ?: ($admin->first_name ?? '');
                    $existing->lastName = $existing->lastName ?: ($admin->last_name ?? '');
                    $existing->role = $existing->role ?: ($admin->role ?? 'member');
                    $existing->status = $existing->status ?: 'active';
                    $existing->save();
                } else {
                    $payload = [
                        'tenant_id' => $tenant->id,
                        'firstName' => $admin->first_name ?? '',
                        'lastName' => $admin->last_name ?? '',
                        'email' => $admin->email,
                        'role' => $admin->role ?? 'member',
                        'status' => 'active',
                    ];
                    if ($hasUserId) {
                        $payload['user_id'] = $admin->id;
                    }
                    TeamMember::create($payload);
                }
            }
        }

        $query = TeamMember::where('tenant_id', $tenant->id);

        $search = request('q');
        $roleFilter = request('role');
        $statusFilter = request('status');
        $sort = request('sort');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                    ->orWhere('lastName', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($roleFilter && $roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $members = match ($sort) {
            'name_asc' => $query->orderBy('firstName')->orderBy('lastName')->paginate(15),
            'role' => $query->orderBy('role')->paginate(15),
            default => $query->latest()->paginate(15),
        };

        return view('trades.team-members.index', compact('tenant', 'members', 'search', 'roleFilter', 'statusFilter', 'sort'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', TeamMember::class);

        $roles = ['admin', 'dispatcher', 'lead_tech', 'tech'];
        $defaultRole = 'tech';

        return view('trades.team-members.create', compact('tenant', 'roles', 'defaultRole'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', TeamMember::class);

        $canManageColors = $this->canManageColors($request);
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:team_members,email|unique:users,email',
            'role' => 'required|string',
            'title' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:25',
            'password' => 'nullable|string|min:8|confirmed',
            'hired_at' => 'nullable|date',
        ];
        if ($canManageColors) {
            $rules['color_hex'] = [
                'nullable',
                'regex:/^#([0-9a-fA-F]{6})$/',
                Rule::unique('team_members', 'color_hex')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ];
        }

        $validated = $request->validate($rules);
        if (!$canManageColors) {
            unset($validated['color_hex']);
        }

        if ($canManageColors && !empty($validated['color_hex'])) {
            $validated['color_hex'] = strtoupper($validated['color_hex']);
            $distinctError = $this->distinctColorError($validated['color_hex'], $tenant->id);
            if ($distinctError) {
                return back()->withErrors(['color_hex' => $distinctError])->withInput();
            }
        }

        $validated['tenant_id'] = $tenant->id;
        $validated['status'] = 'active';

        $password = $validated['password'] ?? null;
        $hasUserId = Schema::hasColumn('team_members', 'user_id');

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['email'],
            'email' => $validated['email'],
            'password' => $password ? bcrypt($password) : \Illuminate\Support\Str::random(12),
            'tenant_id' => $tenant->id,
            'role' => $validated['role'],
            'must_change_password' => $password ? false : true,
            'hired_at' => Schema::hasColumn('users', 'hired_at') ? ($validated['hired_at'] ?? null) : null,
        ]);

        $memberPayload = [
            'tenant_id' => $tenant->id,
            'firstName' => $validated['first_name'],
            'lastName' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'title' => $validated['title'] ?? null,
            'status' => 'active',
        ];
        if ($canManageColors && !empty($validated['color_hex'])) {
            $memberPayload['color_hex'] = $validated['color_hex'];
        }
        if ($hasUserId) {
            $memberPayload['user_id'] = $user->id;
        }

        TeamMember::create($memberPayload);

        return redirect()->route('tenant.trades.team.index', ['tenant' => $tenant->id])
            ->with('status', 'Team member added successfully!');
    }

    public function show(Tenant $tenant, TeamMember $team_member): View
    {
        $this->authorize('view', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $memberUserId = $team_member->user_id ?? null;
        $lastLogin = $memberUserId ? User::find($memberUserId)?->loginActivities()->max('created_at') : null;

        $openProjectStatuses = ['completed', 'cancelled', 'closed'];
        $openProjects = Project::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', $openProjectStatuses)
            ->where(function ($q) use ($memberUserId, $team_member) {
                if ($memberUserId && Schema::hasColumn('projects', 'user_id')) {
                    $q->orWhere('user_id', $memberUserId);
                }
                $q->orWhere('project_manager_id', $team_member->id);
            })
            ->orderByDesc('updated_at')
            ->get();

        $openTasks = Task::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->where(function ($q) use ($memberUserId, $team_member) {
                if ($memberUserId) {
                    $q->orWhere('user_id', $memberUserId);
                }
                $q->orWhere(function ($qq) use ($team_member) {
                    $qq->where('assign_type', 'admin')
                        ->where('assign_id', $team_member->id);
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        $ownedOppsQuery = Opportunity::query()->where('tenant_id', $tenant->id);
        if ($memberUserId) {
            $ownedOppsQuery->where('owner_id', $memberUserId);
        } else {
            $ownedOppsQuery->whereRaw('1 = 0');
        }
        $ownedOpps = $ownedOppsQuery->orderByDesc('updated_at')->get();

        $status = strtolower($team_member->status ?? 'active');
        if ($status === 'inactive') {
            $inviteState = 'Inactive';
        } elseif ($lastLogin) {
            $inviteState = 'Joined';
        } elseif ($team_member->invited_at ?? false) {
            $inviteState = 'Invited';
        } else {
            $inviteState = 'Pending';
        }

        $stats = [
            'open_projects' => $openProjects->count(),
            'open_tasks' => $openTasks->count(),
            'opps_owned' => $ownedOpps->count(),
            'last_login' => $lastLogin,
        ];

        return view('trades.team-members.show', [
            'tenant' => $tenant,
            'team_member' => $team_member,
            'stats' => $stats,
            'invite_state' => $inviteState,
            'projects' => $openProjects->take(5),
            'tasks' => $openTasks->take(5),
            'opportunities' => $ownedOpps->take(5),
        ]);
    }

    public function edit(Tenant $tenant, TeamMember $team_member): View
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $roles = ['admin', 'dispatcher', 'lead_tech', 'tech'];

        return view('trades.team-members.edit', [
            'tenant' => $tenant,
            'team_member' => $team_member,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $canManageColors = $this->canManageColors($request);
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => "required|email|unique:team_members,email,{$team_member->id}",
            'role' => 'required|string',
            'title' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:25',
            'status' => 'required|string|in:active,inactive',
            'hired_at' => 'nullable|date',
        ];
        if ($canManageColors) {
            $rules['color_hex'] = [
                'nullable',
                'regex:/^#([0-9a-fA-F]{6})$/',
                Rule::unique('team_members', 'color_hex')
                    ->ignore($team_member->id)
                    ->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ];
        }

        $validated = $request->validate($rules);
        if (!$canManageColors) {
            unset($validated['color_hex']);
        }

        if ($canManageColors && !empty($validated['color_hex'])) {
            $validated['color_hex'] = strtoupper($validated['color_hex']);
            $distinctError = $this->distinctColorError($validated['color_hex'], $tenant->id, $team_member->id);
            if ($distinctError) {
                return back()->withErrors(['color_hex' => $distinctError])->withInput();
            }
        }

        $team_member->update($validated);

        $user = null;
        if (Schema::hasColumn('team_members', 'user_id') && $team_member->user_id) {
            $user = User::find($team_member->user_id);
        }
        if (!$user) {
            $user = User::where('email', $team_member->email)->first();
        }
        if ($user && Schema::hasColumn('users', 'hired_at') && array_key_exists('hired_at', $validated)) {
            $user->hired_at = $validated['hired_at'];
            $user->save();
        }

        return redirect()->route('tenant.trades.team.index', ['tenant' => $tenant->id])
            ->with('status', 'Team member updated successfully!');
    }

    public function destroy(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('delete', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $team_member->delete();

        return back()->with('status', 'Team member deleted.');
    }

    public function deactivate(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        if ($this->isSelf($team_member)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if ($this->wouldRemoveLastAdmin($tenant, $team_member)) {
            return back()->with('error', 'Cannot deactivate the last active admin/owner for this workspace.');
        }

        $team_member->status = 'inactive';
        $team_member->save();

        return back()->with('status', 'Team member deactivated.');
    }

    public function reactivate(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $team_member->status = 'active';
        $team_member->save();

        return back()->with('status', 'Team member reactivated.');
    }

    public function removeAccess(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('delete', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        if ($this->isSelf($team_member)) {
            return back()->with('error', 'You cannot remove your own access.');
        }
        if ($this->wouldRemoveLastAdmin($tenant, $team_member)) {
            return back()->with('error', 'Cannot remove access for the last active admin/owner.');
        }

        $team_member->status = 'inactive';
        $team_member->save();

        return back()->with('status', 'Access removed. The member can no longer log in.');
    }

    public function resendInvite(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $email = $team_member->email;
        if (!$email) {
            return back()->with('error', 'No email available to send invite.');
        }

        Password::sendResetLink(['email' => $email]);

        return back()->with('status', 'Invite email sent.');
    }

    public function sendPasswordReset(Tenant $tenant, TeamMember $team_member): RedirectResponse
    {
        $this->authorize('update', $team_member);
        abort_unless((int) $team_member->tenant_id === (int) $tenant->id, 404);

        $email = $team_member->email;
        if (!$email) {
            return back()->with('error', 'No email available for reset.');
        }

        Password::sendResetLink(['email' => $email]);

        return back()->with('status', 'Password reset email sent.');
    }

    private function isSelf(TeamMember $team_member): bool
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return false;
        }
        if (Schema::hasColumn('team_members', 'user_id') && $team_member->user_id) {
            return (int) $team_member->user_id === (int) $user->id;
        }
        return strcasecmp($team_member->email, $user->email) === 0;
    }

    private function wouldRemoveLastAdmin(Tenant $tenant, TeamMember $target): bool
    {
        $adminRoles = ['owner', 'admin', 'provider'];
        $isTargetPrivileged = in_array(strtolower((string) $target->role), $adminRoles, true);
        if (!$isTargetPrivileged) {
            return false;
        }

        $activeAdmins = TeamMember::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereIn('role', $adminRoles)
            ->count();

        return $activeAdmins <= 1;
    }

    private function canManageColors(Request $request): bool
    {
        $user = Auth::guard('admin')->user() ?? $request->user();
        if (!$user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['admin', 'owner', 'super_admin', 'superadmin', 'provider'], true);
    }

    private function distinctColorError(string $hex, int $tenantId, ?int $ignoreId = null): ?string
    {
        $colors = TeamMember::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('color_hex')
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('color_hex')
            ->filter()
            ->values();

        if ($colors->count() < 12) {
            $new = $this->hexToRgb($hex);
            foreach ($colors as $existing) {
                $dist = $this->colorDistance($new, $this->hexToRgb($existing));
                if ($dist < 55) {
                    return 'Pick a color that is more distinct from existing team member colors.';
                }
            }
        }

        return null;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function colorDistance(array $a, array $b): float
    {
        return sqrt(
            (($a[0] - $b[0]) ** 2) +
                (($a[1] - $b[1]) ** 2) +
                (($a[2] - $b[2]) ** 2)
        );
    }
}
