@php
    $routePrefix = $routePrefix ?? 'tenant.team-members';
    $tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);
    $teamMember = $team_member ?? null;
    $mode = $mode ?? ($teamMember ? 'edit' : 'create');
    $isExisting = $mode === 'edit';
    $status = old('status', $teamMember->status ?? 'active');
    $roleValue = old('role', $teamMember->role ?? 'employee');
    $currentUser = auth('admin')->user() ?? auth()->user();
    $canSetColor = in_array(
        strtolower((string) ($currentUser->role ?? '')),
        ['admin', 'owner', 'super_admin', 'superadmin', 'provider'],
        true,
    );
    $colorValue = old('color_hex', $teamMember->color_hex ?? '');
    $isTrades = ($tenant?->workspace_type ?? null) === 'trades';
    $hiredAt = $isTrades ? old('hired_at', optional($teamMember?->user)->hired_at?->format('Y-m-d')) : null;
    $formAction = $isExisting
        ? route($routePrefix . '.update', ['tenant' => $tenantParam, 'team_member' => $teamMember?->id])
        : route($routePrefix . '.store', ['tenant' => $tenantParam]);
    $roleOptions = $roles ?? ['admin', 'employee', 'contractor'];
@endphp


<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Page header --}}
    <div class="space-y-1">
        <p class="text-[11px] uppercase tracking-wider text-text-subtle">Team</p>
        <h1 class="text-2xl font-semibold text-text-base">
            {{ $isExisting ? 'Edit Team Member' : 'New Team Member' }}
        </h1>
        <p class="text-sm text-text-subtle">Manage access and responsibilities for this workspace.</p>
        @if ($isExisting)
            <p class="text-xs text-text-subtle">This user already has access. Changing email will resend an invite.</p>
        @endif
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-300/70 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
            <strong class="font-medium">Please fix the following:</strong>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}"
        class="oh-card p-6 md:p-7 border border-border-default/70 shadow-card space-y-6">
        @csrf
        @if ($isExisting)
            @method('PUT')
        @endif

        {{-- Basic Information --}}
        <div class="space-y-3">
            <div>
                <h2 class="text-base font-semibold text-text-base">Basic Information</h2>
                <p class="text-sm text-text-subtle">Personal details used for communication.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">First Name</span>
                    <input type="text" name="first_name" required
                        value="{{ old('first_name', $teamMember->firstName ?? '') }}"
                        class="oh-input h-10 @error('first_name') ring-2 ring-rose-300 @enderror">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Last Name</span>
                    <input type="text" name="last_name" required
                        value="{{ old('last_name', $teamMember->lastName ?? '') }}"
                        class="oh-input h-10 @error('last_name') ring-2 ring-rose-300 @enderror">
                </label>
            </div>
            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Email</span>
                <input type="email" name="email" required value="{{ old('email', $teamMember->email ?? '') }}"
                    class="oh-input h-10 @error('email') ring-2 ring-rose-300 @enderror">
                <span class="text-xs text-text-subtle">An invite will be sent to this email if the user does not already
                    have access.</span>
            </label>
            @if ($isTrades)
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Hire date (optional)</span>
                    <input type="date" name="hired_at" value="{{ $hiredAt }}" class="oh-input h-10">
                    <span class="text-xs text-text-subtle">Used for tenure-based PTO accruals.</span>
                </label>
            @endif
        </div>

        {{-- Role & Access --}}
        <div class="space-y-3 pt-4 border-t border-border-default/70">
            <div>
                <h2 class="text-base font-semibold text-text-base">Role & Access</h2>
                <p class="text-sm text-text-subtle">Set their access level and responsibilities.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Role</span>
                    <select name="role" class="oh-select h-10">
                        @foreach ($roleOptions as $roleOption)
                            @php
                                $roleLabel = str_replace('_', ' ', $roleOption);
                                $roleLabel = ucwords($roleLabel);
                            @endphp
                            <option value="{{ $roleOption }}" @selected($roleValue === $roleOption)>
                                {{ $roleLabel }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-xs text-text-subtle">
                        Controls the access level and responsibilities for this team member.
                    </span>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Title (optional)</span>
                    <input type="text" name="title" value="{{ old('title', $teamMember->title ?? '') }}"
                        class="oh-input h-10">
                </label>
            </div>
            @if ($canSetColor)
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Member color</span>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="color" value="{{ $colorValue ?: '#1F3C66' }}"
                            class="h-10 w-12 rounded-lg border border-border-default bg-white"
                            oninput="document.getElementById('color_hex').value = this.value">
                        <input id="color_hex" type="text" name="color_hex" value="{{ $colorValue }}"
                            placeholder="#1F3C66"
                            class="oh-input h-10 w-36 @error('color_hex') ring-2 ring-rose-300 @enderror">
                    </div>
                    <span class="text-xs text-text-subtle">Used for lead owners and scheduling views. Must be unique per
                        team member.</span>
                    @error('color_hex')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>
            @endif
            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Phone (optional)</span>
                <input type="text" name="phone" value="{{ old('phone', $teamMember->phone ?? '') }}"
                    class="oh-input h-10">
            </label>
        </div>

        {{-- Account Status --}}
        <div class="space-y-3 pt-4 border-t border-border-default/70">
            <div>
                <h2 class="text-base font-semibold text-text-base">Account Status</h2>
                <p class="text-sm text-text-subtle">Control login access for this member.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @php
                    $statuses = [
                        'invited' => 'Invited',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ];
                @endphp
                @foreach ($statuses as $value => $label)
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="{{ $value }}" class="sr-only"
                            @checked($status === $value)>
                        <span
                            class="oh-pill {{ $status === $value ? 'oh-pill--info' : 'oh-pill--muted' }} cursor-pointer">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-text-subtle">
                Invited = email sent and awaiting login · Active = full access · Inactive = access revoked.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-border-default/70">
            <a href="{{ route($routePrefix . '.index', ['tenant' => $tenantParam]) }}" class="oh-btn">
                Cancel
            </a>
            <button type="submit" class="oh-btn oh-btn--primary">
                Save Changes
            </button>
        </div>
    </form>
</div>
