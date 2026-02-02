@extends('layouts.app')

@section('title', 'Registered Users')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
                <h1 class="text-2xl font-semibold text-text-base">Registered Users</h1>
                <p class="text-sm text-text-subtle">Team members and client accounts for {{ $tenant->name }}.</p>
            </div>
            <a href="{{ route('tenant.settings.index', ['tenant' => $tenant->id]) }}" class="oh-btn">
                <i class="fa-solid fa-arrow-left text-[11px] mr-2"></i>
                Back to settings
            </a>
        </div>

        <form method="GET" class="oh-card p-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1">
                <label class="sr-only" for="q">Search users</label>
                <input id="q" name="q" value="{{ $search }}" class="oh-input h-10 w-full"
                    placeholder="Search name or email...">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="oh-btn oh-btn--primary h-10">Search</button>
                @if ($search)
                    <a href="{{ route('tenant.settings.users.index', ['tenant' => $tenant->id]) }}" class="oh-btn h-10">Reset</a>
                @endif
            </div>
        </form>

        <div class="oh-card p-5 space-y-6">
            @php
                $teamUsers = ($tenant->users ?? collect())->filter(function ($user) {
                    return ($user->role ?? '') !== 'client';
                });
            @endphp
            <div class="space-y-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Team members</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-text-subtle">
                            <tr>
                                <th class="text-left py-2 pr-4 font-medium">Name</th>
                                <th class="text-left py-2 pr-4 font-medium">Email</th>
                                <th class="text-left py-2 pr-4 font-medium">Role</th>
                                <th class="text-left py-2 pr-4 font-medium">Last login</th>
                                <th class="text-left py-2 pr-4 font-medium">Status</th>
                                <th class="text-right py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($teamUsers as $user)
                                @php
                                    $lastLogin = $lastLogins[$user->id] ?? null;
                                    $status = $teamStatuses[$user->id] ?? 'active';
                                    $statusLabel = ucfirst($status);
                                    $statusPill = $status === 'inactive' ? 'oh-pill' : 'oh-pill oh-pill--success';
                                @endphp
                                <tr>
                                    <td class="py-3 pr-4 font-semibold text-text-base">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </td>
                                    <td class="py-3 pr-4 text-text-subtle">{{ $user->email }}</td>
                                    <td class="py-3 pr-4 text-text-subtle">{{ ucfirst($user->role ?? 'member') }}</td>
                                    <td class="py-3 pr-4 text-text-subtle">
                                        {{ $lastLogin ? \Illuminate\Support\Carbon::parse($lastLogin)->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="{{ $statusPill }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            @if (!empty($teamMemberIds[$user->id]))
                                                <a href="{{ route('tenant.team-members.show', ['tenant' => $tenant->id, 'team_member' => $teamMemberIds[$user->id]]) }}"
                                                    class="oh-btn h-8 text-xs">View</a>
                                            @else
                                                <span class="text-xs text-text-subtle">No profile</span>
                                            @endif
                                            <a href="mailto:{{ $user->email }}" class="oh-btn h-8 text-xs">Email</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-text-subtle">No team members found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Client portal users</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-text-subtle">
                            <tr>
                                <th class="text-left py-2 pr-4 font-medium">Name</th>
                                <th class="text-left py-2 pr-4 font-medium">Email</th>
                                <th class="text-left py-2 pr-4 font-medium">Role</th>
                                <th class="text-left py-2 pr-4 font-medium">Last login</th>
                                <th class="text-left py-2 pr-4 font-medium">Status</th>
                                <th class="text-right py-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($tenant->clients as $contact)
                                @php
                                    $clientUser = $contact->userAccount;
                                    $lastLogin = $clientUser ? ($lastLogins[$clientUser->id] ?? null) : null;
                                    $statusLabel = ucfirst($contact->status ?? 'active');
                                @endphp
                                <tr>
                                    <td class="py-3 pr-4 font-semibold text-text-base">
                                        {{ $contact->firstName }} {{ $contact->lastName }}
                                    </td>
                                    <td class="py-3 pr-4 text-text-subtle">{{ $contact->email }}</td>
                                    <td class="py-3 pr-4 text-text-subtle">Client</td>
                                    <td class="py-3 pr-4 text-text-subtle">
                                        {{ $lastLogin ? \Illuminate\Support\Carbon::parse($lastLogin)->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="oh-pill">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id]) }}"
                                                class="oh-btn h-8 text-xs">View</a>
                                            <a href="mailto:{{ $contact->email }}" class="oh-btn h-8 text-xs">Email</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-text-subtle">No client users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
