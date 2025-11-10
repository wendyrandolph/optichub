@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? (auth()->user()->tenant_id ?? null));

        $qSearch = request('search', '');
        $qStatus = request('status', '');
        $qLogin = request('login', '');
        $qSort = request('sort', 'name_asc');

        $initials = function ($first, $last) {
            $f = mb_substr((string) $first, 0, 1);
            $l = mb_substr((string) $last, 0, 1);
            return mb_strtoupper(trim($f . $l)) ?: 'C';
        };
    @endphp

    <div class="max-w-7xl mx-auto p-6 bg-surface-page text-text-base">
        <div class="container-4-col mb-6">
            <h1 class="text-3xl font-semibold text-[var(--text-heading)]">Contacts</h1>

            @if ($tenantId)
                <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantId]) }}"
                    class="btn bg-optic-brand text-white rounded-card shadow-[var(--card-shadow)] hover:brightness-110">
                    <i class="fa-solid fa-plus mr-2"></i> Add Contact
                </a>
            @endif
        </div>

        {{-- Toolbar --}}
        <form
            class="oh-toolbar border border-border-default rounded-card p-4 shadow-[var(--card-shadow-light)] mb-6 bg-surface-accent"
            method="GET"
            action="{{ $tenantId ? route('tenant.contacts.index', ['tenant' => $tenantId]) : url()->current() }}">
            <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_220px_180px_180px_auto] items-center">
                <input id="client-search" name="search" type="search" placeholder="Search name, email, phone…"
                    value="{{ $qSearch }}"
                    class="input oh-input rounded-card bg-surface-accent border border-border-default text-text-base placeholder-text-subtle" />

                <select id="client-status" name="status"
                    class="select oh-select rounded-card bg-surface-accent border border-border-default text-text-base">
                    <option value="" @selected($qStatus === '')>All</option>
                    <option value="active" @selected($qStatus === 'active')>Active</option>
                    <option value="inactive" @selected($qStatus === 'inactive')>Inactive</option>
                </select>

                <select id="client-login" name="login"
                    class="select oh-select rounded-card bg-surface-accent border border-border-default text-text-base">
                    <option value="" @selected($qLogin === '')>All</option>
                    <option value="yes" @selected($qLogin === 'yes')>Login</option>
                    <option value="no" @selected($qLogin === 'no')>No login</option>
                </select>

                <select id="client-sort" name="sort"
                    class="select oh-select rounded-card bg-surface-accent border border-border-default text-text-base">
                    <option value="name_asc" @selected($qSort === 'name_asc')>Name A–Z</option>
                    <option value="name_desc" @selected($qSort === 'name_desc')>Name Z–A</option>
                    <option value="updated" @selected($qSort === 'updated')>Recently updated</option>
                </select>

                <div class="flex gap-2 justify-end">
                    <button class="btn bb-blue-500 text-white btn--sm rounded-card" type="submit">Apply</button>
                    <a class="btn btn--ghost btn--sm rounded-card"
                        href="{{ $tenantId ? route('tenant.contacts.index', ['tenant' => $tenantId]) : url()->current() }}">
                        Reset
                    </a>

                </div>
            </div>
        </form>

        {{-- Cards --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($clients as $client)
                @php
                    $id = $client['id'] ?? $client->id;
                    $firstName = $client['firstName'] ?? ($client->firstName ?? '');
                    $lastName = $client['lastName'] ?? ($client->lastName ?? '');
                    $email = $client['email'] ?? ($client->email ?? '');
                    $phone = $client['phone'] ?? ($client->phone ?? '');
                    $status = $client['status'] ?? ($client->status ?? 'active');
                    $hasLogin = isset($clientsWithLogins)
                        ? in_array($id, (array) $clientsWithLogins, true)
                        : (bool) (isset($client->userAccount) ? $client->userAccount : false);
                @endphp

                <div
                    class="oh-card bg-surface-card border border-border-default rounded-card p-5 shadow-[var(--card-shadow)] hover:shadow-[var(--card-shadow)] hover:-translate-y-[2px] transition">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="avatar-brand">{{ $initials($firstName, $lastName) }}</div>
                            <h3 class="text-lg font-semibold">{{ trim($firstName . ' ' . $lastName) ?: '—' }}</h3>
                        </div>
                        <span class="chip {{ $status === 'active' ? 'chip--status-active' : 'chip--status-inactive' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </div>

                    <p class="text-text-subtle text-sm">Email: {{ $email ?: '—' }}</p>
                    <p class="text-text-subtle text-sm">Phone: {{ $phone ?: '—' }}</p>

                    <div class="flex items-center gap-3 mt-4">
                        <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $id]) }}"
                            class="text-brand-primary hover:text-brand-secondary transition">
                            <i class="fa fa-circle-info text-lg"></i>
                        </a>

                        <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $id]) }}"
                            class="text-[rgb(var(--text-subtle))] hover:text-brand-primary transition">
                            <i class="fa-regular fa-pen-to-square text-lg"></i>
                        </a>

                        <form method="POST"
                            action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantId, 'contact' => $id]) }}"
                            onsubmit="return confirm('Delete this contact?')">
                            @csrf @method('DELETE')
                            <button class="text-status-danger hover:text-red-400 transition" type="submit">
                                <i class="fa-regular fa-trash-can text-lg"></i>
                            </button>
                        </form>

                        @if ($hasLogin)
                            <span class="chip chip--login">Login</span>
                        @else
                            <span class="chip chip--login chip--muted">No login</span>
                        @endif
                    </div>
                </div>
            @empty
                <div
                    class="col-span-full text-center text-text-subtle py-8 border border-dashed border-border-default rounded-card">
                    No contacts found.
                </div>
            @endforelse
        </div>

        @if (method_exists($clients, 'links'))
            <div class="mt-8">
                {{ $clients->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
