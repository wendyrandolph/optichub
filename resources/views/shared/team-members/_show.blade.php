@php
    use Carbon\Carbon;
    $routePrefix = $routePrefix ?? 'tenant.team-members';
    $tenantId = $tenant->id ?? (request()->route('tenant') ?? optional(auth()->user())->tenant_id);
    $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;

    $name =
        $team_member->full_name ??
        trim(
            ($team_member->first_name ?? ($team_member->firstName ?? '')) .
                ' ' .
                ($team_member->last_name ?? ($team_member->lastName ?? '')),
        );
    $email = $team_member->email ?? null;
    $phone = $team_member->phone_formatted ?? ($team_member->phone ?? null);
    $role = ucfirst($team_member->role ?? 'Member');
    $status = strtolower($team_member->status ?? 'active');
    $lastLogin = $stats['last_login'] ?? null;
    $inviteState = $invite_state ?? ($lastLogin ? 'Joined' : 'Pending');

    $initials = function ($text) {
        $parts = preg_split('/\s+/', trim((string) $text));
        $a = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
        $b = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
        return trim($a . $b) ?: 'TM';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header --}}
    <div class="oh-card p-5 border border-border-default bg-surface-card shadow-card">
        <div class="flex flex-col gap-4">
            <a href="{{ route($routePrefix . '.index', ['tenant' => $tenantId]) }}"
                class="oh-link-underline inline-flex self-start items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left text-[10px] mr-1.5"></i>
                Back to team
            </a>

            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div
                        class="h-12 w-12 rounded-xl bg-[rgba(var(--brand-primary)/.14)] text-[rgb(var(--brand-primary))] ring-1 ring-[rgba(var(--brand-primary)/.28)] grid place-items-center text-sm font-semibold">
                        {{ $initials($name ?: $email ?? '') }}
                    </div>
                    <div class="min-w-0 space-y-2">
                        <h1 class="text-2xl font-semibold text-text-base leading-tight">{{ $name ?: 'Team member' }}
                        </h1>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="oh-pill oh-pill--muted text-[11px]">{{ $role }}</span>
                            <span
                                class="oh-pill {{ $status === 'active' ? 'oh-pill--success' : 'oh-pill--muted' }} text-[11px]">
                                {{ ucfirst($status) }}
                            </span>
                            <span class="oh-pill oh-pill--muted text-[11px]">
                                {{ $inviteState }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}"
                        class="oh-btn oh-btn--primary">
                        <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                        Edit member
                    </a>
                    @if (in_array($inviteState, ['Invited', 'Pending', '—']))
                        <form method="POST"
                            action="{{ route($routePrefix . '.resend-invite', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--secondary"
                                onclick="return confirm('Resend invite to this member?');">
                                <i class="fa-regular fa-paper-plane text-[12px]"></i>
                                Resend invite
                            </button>
                        </form>
                    @endif
                    @if ($inviteState === 'Joined')
                        <form method="POST"
                            action="{{ route($routePrefix . '.send-reset', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--secondary"
                                onclick="return confirm('Send password reset to this member?');">
                                <i class="fa-solid fa-key text-[12px]"></i>
                                Reset password
                            </button>
                        </form>
                    @endif
                    @if ($status === 'active')
                        <form method="POST"
                            action="{{ route($routePrefix . '.deactivate', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--secondary"
                                onclick="return confirm('Deactivate this team member?');">
                                <i class="fa-solid fa-user-slash text-[12px]"></i>
                                Deactivate
                            </button>
                        </form>
                    @else
                        <form method="POST"
                            action="{{ route($routePrefix . '.reactivate', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--secondary"
                                onclick="return confirm('Reactivate this team member?');">
                                <i class="fa-solid fa-user-check text-[12px]"></i>
                                Reactivate
                            </button>
                        </form>
                    @endif
                    <form method="POST"
                        action="{{ route($routePrefix . '.remove', ['tenant' => $tenantId, 'team_member' => $team_member->id]) }}">
                        @csrf
                        <button type="submit" class="oh-btn oh-btn--danger"
                            onclick="return confirm('Remove access? They will be unable to log in.');">
                            <i class="fa-solid fa-ban text-[12px]"></i>
                            Remove access
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="oh-card p-4 border border-border-default">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Open projects</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ $stats['open_projects'] ?? 0 }}</div>
        </div>
        <div class="oh-card p-4 border border-border-default">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Open tasks</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ $stats['open_tasks'] ?? 0 }}</div>
        </div>
        <div class="oh-card p-4 border border-border-default">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Opportunities owned</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ $stats['opps_owned'] ?? 0 }}</div>
        </div>
        <div class="oh-card p-4 border border-border-default">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Last login</div>
            <div class="text-sm mt-1">
                {{ $lastLogin ? Carbon::parse($lastLogin)->diffForHumans() : '—' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        {{-- Left column --}}
        <div class="space-y-5 xl:col-span-2">
            <div class="oh-card p-5 border border-border-default">
                <nav class="tabs__nav flex flex-wrap gap-2 mb-4 text-xs font-medium">
                    <button class="px-3 py-1 rounded-full bg-surface-accent text-text-base is-active"
                        data-tab="activity">Activity</button>
                    <button class="px-3 py-1 rounded-full hover:bg-surface-accent text-text-subtle"
                        data-tab="assignments">Assignments</button>
                    <button class="px-3 py-1 rounded-full hover:bg-surface-accent text-text-subtle"
                        data-tab="access">Access</button>
                </nav>

                {{-- Activity --}}
                <div class="tabs__panel is-active" id="tab-activity">
                    <p class="text-sm text-text-subtle">Activity coming soon.</p>
                </div>

                {{-- Assignments --}}
                <div class="tabs__panel hidden" id="tab-assignments">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-text-base">Projects</div>
                            <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId, 'owner' => $team_member->id]) }}"
                                class="oh-link text-xs">View all</a>
                        </div>
                        @forelse ($projects ?? [] as $project)
                            @php
                                $projStatus = strtolower((string) ($project->status ?? 'open'));
                                $projPill = in_array($projStatus, ['completed', 'closed', 'cancelled'])
                                    ? 'oh-pill'
                                    : 'oh-pill oh-pill--info';
                            @endphp
                            <div class="flex items-center justify-between text-sm py-1">
                                <div class="truncate">
                                    <a href="{{ Route::has('tenant.projects.show') ? route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $project->id]) : '#' }}"
                                        class="font-semibold text-text-base hover:text-brand-primary truncate">
                                        {{ $project->project_name ?? 'Project' }}
                                    </a>
                                    <div class="text-xs text-text-subtle">Updated
                                        {{ $project->updated_at?->diffForHumans() ?? '—' }}</div>
                                </div>
                                <span
                                    class="oh-pill {{ $projPill }} text-[11px] ml-3">{{ ucfirst($projStatus) }}</span>
                            </div>
                        @empty
                            <p class="text-text-subtle">No projects assigned.</p>
                        @endforelse

                        <div class="flex items-center justify-between pt-2">
                            <div class="font-semibold text-text-base">Tasks</div>
                            <a href="{{ route('tenant.tasks.index', ['tenant' => $tenantId, 'assignee' => $team_member->id]) }}"
                                class="oh-link text-xs">View all</a>
                        </div>
                        @forelse ($tasks ?? [] as $task)
                            @php
                                $taskStatus = strtolower((string) ($task->status ?? 'todo'));
                                $taskPill = $taskStatus === 'completed' ? 'oh-pill' : 'oh-pill oh-pill--info';
                            @endphp
                            <div class="flex items-center justify-between text-sm py-1">
                                <div class="truncate">
                                    <a href="{{ Route::has('tenant.tasks.edit') ? route('tenant.tasks.edit', ['tenant' => $tenantId, 'task' => $task->id]) : '#' }}"
                                        class="font-semibold text-text-base hover:text-brand-primary truncate">
                                        {{ $task->title ?? 'Task' }}
                                    </a>
                                    <div class="text-xs text-text-subtle">Due
                                        {{ $task->due_date?->format('M j, Y') ?? '—' }}</div>
                                </div>
                                <span
                                    class="oh-pill {{ $taskPill }} text-[11px] ml-3">{{ str_replace('_', ' ', ucfirst($taskStatus)) }}</span>
                            </div>
                        @empty
                            <p class="text-text-subtle">No tasks assigned.</p>
                        @endforelse

                        <div class="flex items-center justify-between pt-2">
                            <div class="font-semibold text-text-base">Opportunities</div>
                            <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId, 'owner' => $team_member->id]) }}"
                                class="oh-link text-xs">View all</a>
                        </div>
                        @forelse ($opportunities ?? [] as $opp)
                            <div class="flex items-center justify-between text-sm py-1">
                                <div class="truncate">
                                    <a href="{{ Route::has('tenant.opportunities.show') ? route('tenant.opportunities.show', ['tenant' => $tenantId, 'opportunity' => $opp->id]) : '#' }}"
                                        class="font-semibold text-text-base hover:text-brand-primary truncate">
                                        {{ $opp->title ?? 'Opportunity' }}
                                    </a>
                                    <div class="text-xs text-text-subtle">Stage: {{ ucfirst($opp->stage ?? '—') }}
                                    </div>
                                </div>
                                <span
                                    class="oh-pill oh-pill--muted text-[11px] ml-3">{{ $opp->expected_close_date?->format('M j') ?? '—' }}</span>
                            </div>
                        @empty
                            <p class="text-text-subtle">No owned opportunities.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Access --}}
                <div class="tabs__panel hidden" id="tab-access">
                    <h4 class="text-sm font-semibold text-text-base mb-2">Permissions ({{ $role }})</h4>
                    <ul class="list-disc list-inside text-sm text-text-subtle space-y-1">
                        <li>Access limited to assigned projects and tasks.</li>
                        <li>Can manage own profile and time entries (if enabled).</li>
                        <li>Contact records are visible as needed.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right rail --}}
        <aside class="space-y-4 xl:col-span-1">
            <div class="oh-card p-5 border border-border-default">
                <h3 class="text-sm font-semibold text-text-base mb-3">Member details</h3>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Email</dt>
                        <dd class="text-right">
                            @if ($email)
                                <a href="mailto:{{ $email }}"
                                    class="text-brand-primary hover:text-brand-secondary break-all">{{ $email }}</a>
                            @else
                                <span class="text-text-subtle">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Phone</dt>
                        <dd class="text-right">{{ $phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Title</dt>
                        <dd class="text-right">{{ $team_member->title ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Role</dt>
                        <dd class="text-right">{{ $role }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Status</dt>
                        <dd class="text-right">{{ ucfirst($status) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Created</dt>
                        <dd class="text-right">{{ $team_member->created_at?->format('M j, Y g:ia') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-subtle">Updated</dt>
                        <dd class="text-right">{{ $team_member->updated_at?->format('M j, Y g:ia') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="oh-card p-5 border border-border-default">
                <h3 class="text-sm font-semibold text-text-base mb-3">Notes</h3>
                <p class="text-sm text-text-subtle">Internal notes coming soon.</p>
            </div>

            <div class="oh-card p-5 border border-border-default">
                <h3 class="text-sm font-semibold text-text-base mb-3">Security</h3>
                <ul class="text-sm text-text-subtle space-y-1">
                    <li>Last login: {{ $lastLogin ? Carbon::parse($lastLogin)->format('M j, Y g:ia') : '—' }}</li>
                    <li>MFA: —</li>
                    <li>Password reset: —</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navBtns = document.querySelectorAll('.tabs__nav button');
        const panels = document.querySelectorAll('.tabs__panel');

        navBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tab;
                navBtns.forEach(b => {
                    b.classList.remove('is-active');
                    b.classList.remove('bg-surface-accent');
                    b.classList.add('text-text-subtle');
                });
                panels.forEach(p => p.classList.add('hidden'));
                btn.classList.add('is-active');
                btn.classList.add('bg-surface-accent');
                btn.classList.remove('text-text-subtle');
                const panel = document.getElementById('tab-' + target);
                if (panel) panel.classList.remove('hidden');
            });
        });
    });
</script>
