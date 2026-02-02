@php
    $routePrefix = $routePrefix ?? 'tenant.team-members';
    $routeTenant = request()->route('tenant');
    $tenantParam =
        $tenant->id ??
        ($routeTenant instanceof \App\Models\Tenant
            ? $routeTenant->id
            : $routeTenant ?? (auth()->user()->tenant_id ?? null));
    $searchVal = $search ?? request('q');
    $roleVal = $roleFilter ?? request('role', 'all');
    $statusVal = $statusFilter ?? request('status', 'all');
    $sortVal = $sort ?? request('sort', '');
    $initials = function ($text) {
        $parts = preg_split('/\s+/', trim((string) $text));
        $a = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
        $b = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
        return trim($a . $b) ?: 'TM';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Team Members</h1>
            <p class="text-sm text-text-subtle mt-1">
                Manage who has access to this workspace and what they’re allowed to do.
            </p>
        </div>

        @if ($tenantParam)
            <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantParam]) }}"
                class="oh-btn oh-btn--primary">
                <i class="fa-solid fa-plus text-[11px]"></i>
                Add team member
            </a>
        @endif
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route($routePrefix . '.index', ['tenant' => $tenantParam]) }}"
        class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4 flex flex-col gap-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-3">
            <div class="flex-1">
                <input name="q" value="{{ $searchVal }}" class="oh-input"
                    placeholder="Search name, email, role...">
            </div>
            <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                <select name="role" class="oh-select">
                    <option value="all" @selected($roleVal === 'all')>All roles</option>
                    <option value="admin" @selected($roleVal === 'admin')>Admin</option>
                    <option value="employee" @selected($roleVal === 'employee')>Employee</option>
                    <option value="contractor" @selected($roleVal === 'contractor')>Contractor</option>
                </select>
                <select name="status" class="oh-select">
                    <option value="all" @selected($statusVal === 'all')>All status</option>
                    <option value="active" @selected($statusVal === 'active')>Active</option>
                    <option value="inactive" @selected($statusVal === 'inactive')>Inactive</option>
                </select>
                <select name="sort" class="oh-select min-w-[180px]">
                    <option value="" @selected($sortVal === '')>Sort: Recently added</option>
                    <option value="name_asc" @selected($sortVal === 'name_asc')>Name A–Z</option>
                    <option value="role" @selected($sortVal === 'role')>Role</option>
                </select>
                <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                @if (request()->hasAny(['q', 'role', 'status', 'sort']) &&
                        ($searchVal || $roleVal !== 'all' || $statusVal !== 'all' || $sortVal))
                    <a href="{{ route($routePrefix . '.index', ['tenant' => $tenantParam]) }}"
                        class="oh-btn oh-btn--secondary">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Mobile cards --}}
    <div class="md:hidden space-y-3">
        @forelse ($members as $m)
            @php
                $name = $m->full_name ?? trim(($m->firstName ?? $m->first_name ?? '') . ' ' . ($m->lastName ?? $m->last_name ?? ''));
                $role = ucfirst($m->role ?? 'User');
                $status = strtolower($m->status ?? 'active');
                $isOwner = data_get($m, 'is_owner') ?? (data_get($m, 'owner') ?? false);
                $lastLogin = $m->last_login_at ?? ($m->lastLoginAt ?? null);
                $invitedAt = $m->invited_at ?? ($m->invitedAt ?? null);
            @endphp
            <article class="oh-card p-4 border border-border-default space-y-3">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 rounded-full ring-1 ring-[rgba(var(--border)/.6)] grid place-items-center text-[11px] font-bold shrink-0"
                        style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary));">
                        {{ $initials($name ?: $m->email ?? '') }}
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-text-base truncate">{{ $name ?: '—' }}</span>
                            @if ($isOwner)
                                <span class="oh-pill oh-pill--muted text-[10px]">Owner</span>
                            @endif
                        </div>
                        @if (!empty($m->user_name))
                            <div class="text-xs text-text-subtle truncate">{{ $m->user_name }}</div>
                        @endif
                        <div class="text-sm">
                            @if ($m->email)
                                <a href="mailto:{{ $m->email }}"
                                    class="text-brand-primary hover:text-brand-secondary">{{ $m->email }}</a>
                            @else
                                <span class="text-text-subtle">—</span>
                            @endif
                        </div>
                        <div class="text-xs text-text-subtle">{{ $m->phone_formatted ?? '—' }}</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="oh-pill oh-pill--muted text-[11px]">{{ $role }}</span>
                    <div class="flex flex-wrap gap-1">
                        <span
                            class="oh-pill {{ $status === 'active' ? 'oh-pill--success' : 'oh-pill--muted' }} text-[11px]">
                            {{ ucfirst($status) }}
                        </span>
                        @if (!$lastLogin && $invitedAt)
                            <span class="oh-pill oh-pill--muted text-[11px]">Invited</span>
                        @endif
                    </div>
                    <span class="text-xs text-text-subtle">
                        @if ($lastLogin)
                            Last login {{ \Illuminate\Support\Carbon::parse($lastLogin)->diffForHumans() }}
                        @elseif ($invitedAt)
                            Invited {{ \Illuminate\Support\Carbon::parse($invitedAt)->diffForHumans() }}
                        @else
                            No login activity yet
                        @endif
                    </span>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                        class="oh-icon-btn oh-tooltip" data-tooltip="View">
                        <i class="fa-regular fa-eye text-[12px]"></i>
                    </a>
                    @if ($tenantParam)
                        <a href="{{ route($routePrefix . '.edit', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                            class="oh-icon-btn oh-tooltip" data-tooltip="Edit">
                            <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                        </a>
                    @endif
                    <form method="POST"
                        action="{{ route($routePrefix . '.destroy', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                        onsubmit="return confirm('Delete this team member?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-icon-btn oh-tooltip" data-tooltip="Delete">
                            <i class="fa-regular fa-trash-can text-[12px] text-rose-500"></i>
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="oh-card p-6 text-center text-sm text-text-subtle">
                <p>No team members yet.</p>
                @if ($tenantParam)
                    <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantParam]) }}"
                        class="oh-btn oh-btn--primary mt-2">
                        <i class="fa-solid fa-plus text-[11px]"></i>
                        Add your first team member
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Table card (desktop) --}}
    <div class="oh-card border border-border-default bg-surface-card shadow-card overflow-hidden hidden md:block">
        <div class="overflow-x-auto overflow-y-auto max-h-[800px]">
            <table class="oh-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Login</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $m)
                        @php
                            $name = $m->full_name ?? trim(($m->firstName ?? $m->first_name ?? '') . ' ' . ($m->lastName ?? $m->last_name ?? ''));
                            $role = ucfirst($m->role ?? 'User');
                            $status = strtolower($m->status ?? 'active');
                            $isOwner = data_get($m, 'is_owner') ?? (data_get($m, 'owner') ?? false);
                            $lastLogin = $m->last_login_at ?? ($m->lastLoginAt ?? null);
                            $invitedAt = $m->invited_at ?? ($m->invitedAt ?? null);
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-10 w-10 rounded-full ring-1 ring-[rgba(var(--border)/.6)] grid place-items-center text-[11px] font-bold shrink-0"
                                        style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary));">
                                        {{ $initials($name ?: $m->email ?? '') }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-text-base truncate">{{ $name ?: '—' }}</div>
                                        <div class="flex items-center gap-2 text-xs text-text-subtle truncate">
                                            @if (!empty($m->user_name))
                                                <span>{{ $m->user_name }}</span>
                                            @endif
                                            @if ($isOwner)
                                                <span class="oh-pill oh-pill--muted text-[10px]">Owner</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-text-subtle">{{ $m->title ?? '—' }}</td>
                            <td class="text-text-subtle">
                                <div class="flex flex-col">
                                    @if ($m->email)
                                        <a href="mailto:{{ $m->email }}"
                                            class="text-brand-primary hover:text-brand-secondary">{{ $m->email }}</a>
                                    @else
                                        <span class="text-text-subtle">—</span>
                                    @endif
                                    <span class="text-xs text-text-subtle">{{ $m->phone_formatted ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $roleKey = strtolower($m->role ?? 'user');
                                    $rolePill = match ($roleKey) {
                                        'admin' => 'oh-pill oh-pill--primary',
                                        'employee' => 'oh-pill oh-pill--info',
                                        'contractor' => 'oh-pill oh-pill--muted',
                                        default => 'oh-pill oh-pill--muted',
                                    };
                                @endphp
                                <span class="{{ $rolePill }} text-[11px]">{{ $role }}</span>
                            </td>
                            <td>
                                @if ($lastLogin)
                                    <span class="oh-pill oh-pill--muted text-[11px]">Last login:
                                        {{ \Illuminate\Support\Carbon::parse($lastLogin)->diffForHumans() }}</span>
                                @elseif ($invitedAt)
                                    <span class="oh-pill oh-pill--muted text-[11px]">
                                        Invited {{ \Illuminate\Support\Carbon::parse($invitedAt)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="oh-pill oh-pill--muted text-[11px]">No login</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        class="oh-pill {{ $status === 'active' ? 'oh-pill--success' : 'oh-pill--muted' }} text-[11px]">
                                        <span
                                            class="h-1.5 w-1.5 rounded-full {{ $status === 'active' ? 'bg-[rgb(var(--status-success))]' : 'bg-[rgb(var(--border))]' }} inline-block mr-1"></span>
                                        {{ ucfirst($status) }}
                                    </span>
                                    @if (!$lastLogin && $invitedAt)
                                        <span class="oh-pill oh-pill--muted text-[11px]">Invited</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                                        class="oh-icon-btn oh-tooltip" data-tooltip="View">
                                        <i class="fa-regular fa-eye text-[12px]"></i>
                                    </a>
                                    @if ($tenantParam)
                                        <a href="{{ route($routePrefix . '.edit', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                                            class="oh-icon-btn oh-tooltip" data-tooltip="Edit">
                                            <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                                        </a>
                                    @endif
                                    <form method="POST"
                                        action="{{ route($routePrefix . '.destroy', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                                        onsubmit="return confirm('Delete this team member?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="oh-icon-btn oh-tooltip" data-tooltip="Delete">
                                            <i class="fa-regular fa-trash-can text-[12px] text-rose-500"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-text-subtle">
                                <div class="space-y-2">
                                    <p>No team members yet.</p>
                                    @if ($tenantParam)
                                        <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantParam]) }}"
                                            class="oh-btn oh-btn--primary">
                                            <i class="fa-solid fa-plus text-[11px]"></i>
                                            Add your first team member
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if (method_exists($members, 'links'))
            <div
                class="px-4 py-3 border-t border-border-default/60 flex items-center justify-between text-sm text-text-subtle">
                <div>
                    Showing
                    {{ $members->firstItem() ?? 0 }}
                    to
                    {{ $members->lastItem() ?? 0 }}
                    of
                    {{ $members->total() ?? $members->count() }}
                    results
                </div>
                <div>
                    {{ $members->appends(request()->only(['q', 'role', 'status', 'sort']))->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
