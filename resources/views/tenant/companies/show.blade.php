@extends('layouts.app')

@section('title', $company->company_name ?? 'Company')

@section('content')
    @php
        $tenantModel = $tenant ?? request()->route('tenant');
        $tenantId = $tenantModel instanceof \App\Models\Tenant ? $tenantModel->id : (int) $tenantModel;

        $contacts = $company->contacts ?? collect();
        $contactsCount = $company->contacts_count ?? $contacts->count();
        $activeProjects = $company->active_projects_count ?? 0;
        $primaryContact = $primaryContact ?? null;
        $latestProjectUpdate = $latestProjectUpdate ?? null;

        $initials = function ($text) {
            $parts = preg_split('/\s+/', trim((string) $text));
            $a = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
            $b = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
            return trim($a . $b) ?: 'C';
        };
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">Clients</p>
                    <h1 class="text-2xl font-semibold text-text-base">{{ $company->company_name ?? 'Company' }}</h1>
                    <p class="text-sm text-text-subtle">
                        Company overview, linked contacts, services, and activity.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 text-sm text-text-subtle">
                    @if ($company->industry)
                        <span class="oh-pill oh-pill--muted text-[11px]">{{ $company->industry }}</span>
                    @endif
                    @if ($company->website)
                        <a href="{{ $company->website }}" target="_blank" rel="noopener"
                            class="oh-pill oh-pill--muted inline-flex items-center gap-2">
                            <i class="fa-solid fa-link text-[11px]"></i>
                            {{ preg_replace('#^https?://#', '', $company->website) }}
                        </a>
                    @endif
                    @if ($company->phone_formatted ?? $company->phone ?? false)
                        <span class="oh-pill oh-pill--muted inline-flex items-center gap-2">
                            <i class="fa-solid fa-phone text-[11px]"></i>
                            {{ $company->phone_formatted ?? $company->phone }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-2 text-xs" aria-hidden="true"></i>
                    View All Companies
                </a>
                @if (Route::has('tenant.companies.services.index'))
                    <a href="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                        class="oh-btn">
                        <i class="fa-solid fa-layer-group mr-2 text-xs" aria-hidden="true"></i>
                        View Services
                    </a>
                @endif
                <a href="{{ route('tenant.companies.edit', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="oh-btn">
                    <i class="fa-solid fa-pen mr-2 text-xs"></i>
                    Edit company
                </a>
            </div>
        </header>

        {{-- Summary cards --}}
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="oh-card">
                <div class="text-xs text-text-subtle mb-1">Contacts</div>
                <div class="text-2xl font-semibold text-text-base">{{ $contactsCount }}</div>
            </div>
            <div class="oh-card">
                <div class="text-xs text-text-subtle mb-1">Active Services</div>
                <div class="text-2xl font-semibold text-text-base">{{ $activeServicesCount ?? 0 }}</div>
            </div>
            <div class="oh-card">
                <div class="text-xs text-text-subtle mb-1">Next Renewal</div>
                <div class="text-sm font-semibold text-text-base">
                    @if (!empty($nextServiceRenewal?->renewal_date))
                        {{ optional($nextServiceRenewal->renewal_date)->format('M j, Y') }}
                    @else
                        —
                    @endif
                </div>
                @if (!empty($nextServiceRenewal?->name))
                    <div class="text-[11px] text-text-subtle mt-1 truncate">
                        {{ $nextServiceRenewal->name }}
                    </div>
                @endif
            </div>
            <div class="oh-card">
                <div class="text-xs text-text-subtle mb-1">Last Activity</div>
                <div class="text-sm font-semibold text-text-base">
                    {{ $latestProjectUpdate ? \Illuminate\Support\Carbon::parse($latestProjectUpdate)->diffForHumans() : '—' }}
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-6">
                {{-- Contacts --}}
                <section class="oh-card p-0">
                    <div class="flex items-center justify-between px-6 pt-4 pb-2">
                        <h2 class="text-sm font-semibold text-text-base">Contacts</h2>
                        <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                            class="oh-btn oh-btn--ghost text-xs">
                            <i class="fa-solid fa-user-plus mr-1.5 text-[10px]"></i>
                            Add contact
                        </a>
                    </div>

                    {{-- Mobile cards --}}
                    <div class="md:hidden grid gap-3 px-4 pb-4">
                        @forelse ($contacts as $contact)
                            @php
                                $fullName = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));
                                $role = $contact->role ?? ($contact->position ?? null);
                            @endphp
                            <article class="oh-card p-3 space-y-2">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full ring-1 ring-[rgb(var(--border)/.6)] grid place-items-center text-[11px] font-bold"
                                        style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary));">
                                        {{ $initials($fullName ?: ($contact->email ?? 'C')) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-text-base truncate">{{ $fullName ?: '—' }}</div>
                                        <div class="text-[12px] text-text-subtle truncate">
                                            {{ $contact->email ?? 'No email' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs text-text-subtle">{{ $role ?: '—' }}</div>
                                <div class="text-xs text-text-subtle">
                                    {{ $contact->phone ?? '—' }}
                                </div>
                                <div class="flex justify-end">
                                    @if (Route::has('tenant.contacts.edit'))
                                        <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                            class="oh-btn oh-btn--primary text-xs">
                                            Edit
                                        </a>
                                    @endif
                                    @if (Route::has('tenant.contacts.destroy'))
                                        <form method="POST" class="inline"
                                            action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                            onsubmit="return confirm('Delete this contact?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="oh-btn text-xs ml-2">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="text-sm text-text-subtle px-2">No contacts yet. Add your first contact to keep everyone organized for this company.</div>
                        @endforelse
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden md:block">
                        <table class="min-w-full text-sm">
                            <thead class="bg-surface-muted/60">
                                <tr class="text-left text-text-subtle">
                                    <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Name</th>
                                    <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Role / Title</th>
                                    <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px] text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-default/60">
                                @forelse ($contacts as $contact)
                                    @php
                                        $fullName = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));
                                        $role = $contact->role ?? ($contact->position ?? null);
                                    @endphp
                                    <tr class="hover:bg-surface-accent/30">
                                        <td class="px-6 py-3 text-text-base">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-full ring-1 ring-[rgb(var(--border)/.6)] grid place-items-center text-[11px] font-bold"
                                                    style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary));">
                                                    {{ $initials($fullName ?: ($contact->email ?? 'C')) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-semibold text-text-base truncate">{{ $fullName ?: '—' }}</div>
                                                    <div class="text-[12px] text-text-subtle truncate flex flex-wrap items-center gap-2">
                                                        <span>{{ $contact->email ?? 'No email' }}</span>
                                                        @if (!empty($contact->phone))
                                                            <span class="whitespace-nowrap text-text-subtle">{{ $contact->phone }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-text-base">
                                            {{ $role ?: '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                                    class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View">
                                                    <i class="fa-solid fa-eye text-[12px]"></i>
                                                </a>
                                                @if (Route::has('tenant.contacts.edit'))
                                                    <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                                        class="oh-icon-btn oh-tooltip" data-tooltip="Edit" aria-label="Edit">
                                                        <i class="fa-solid fa-pen text-[12px]"></i>
                                                    </a>
                                                @endif
                                                @if (Route::has('tenant.contacts.destroy'))
                                                    <form method="POST" class="inline"
                                                        action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                                        onsubmit="return confirm('Delete this contact?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600"
                                                            data-tooltip="Delete" aria-label="Delete">
                                                            <i class="fa-solid fa-trash text-[12px]"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-10 text-center text-text-subtle">No contacts yet. Add your first contact to keep everyone organized for this company.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Services --}}
                @if (!empty($services))
                    <section class="oh-card p-0">
                        <div class="flex items-center justify-between px-6 pt-4 pb-2">
                            <h2 class="text-sm font-semibold text-text-base">Services</h2>
                            @if (Route::has('tenant.companies.services.index'))
                            <a href="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id, 'open' => 'add']) }}"
                                class="oh-btn oh-btn--primary text-xs">
                                    <i class="fa-solid fa-plus mr-1.5 text-[10px]"></i>
                                    Add service
                                </a>
                            @endif
                        </div>
                        <div class="divide-y divide-border-default/60">
                            @forelse ($services as $service)
                                <div class="px-6 py-3 flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="font-semibold text-text-base">{{ $service->name ?? 'Service' }}</div>
                                        <div class="text-xs text-text-subtle">
                                            {{ ucfirst($service->type ?? '') }}
                                            @if ($service->renewal_date)
                                                • Renews {{ \Illuminate\Support\Carbon::parse($service->renewal_date)->format('M j, Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="oh-pill oh-pill--muted">{{ ucfirst($service->status ?? 'active') }}</span>
                                        @if (Route::has('tenant.services.show'))
                                            <a href="{{ route('tenant.services.show', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                                                class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View">
                                                <i class="fa-solid fa-eye text-[12px]"></i>
                                            </a>
                                        @endif
                                        @if (Route::has('tenant.services.destroy'))
                                            <form method="POST"
                                                action="{{ route('tenant.services.destroy', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                                                onsubmit="return confirm('Delete this service?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600"
                                                    data-tooltip="Delete" aria-label="Delete">
                                                    <i class="fa-solid fa-trash text-[12px]"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-6 text-sm text-text-subtle">No services yet.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                {{-- Activity placeholder --}}
                <section class="oh-card">
                    <h2 class="text-sm font-semibold text-text-base mb-2">Recent Activity</h2>
                    <p class="text-sm text-text-subtle">Activity feed coming soon.</p>
                </section>
            </div>

            {{-- Right rail --}}
            <div class="space-y-4">
                <section class="oh-card">
                    <h3 class="text-sm font-semibold text-text-base mb-3">Account Signals</h3>
                    <ul class="text-sm text-text-subtle divide-y divide-border-default/60">
                        @php
                            $pcName = $primaryContact?->full_name ?? data_get($company, 'primaryContact.full_name') ?? 'Not set';
                        @endphp
                        <li class="flex items-center gap-2 py-2 first:pt-0">
                            <span class="oh-pill oh-pill--muted">Primary contact</span>
                            <span>{{ $pcName }}</span>
                        </li>
                        <li class="flex items-center gap-2 py-2">
                            <span class="oh-pill oh-pill--muted">Active projects</span>
                            <span>{{ $activeProjects }}</span>
                        </li>
                        <li class="flex items-center gap-2 py-2">
                            <span class="oh-pill oh-pill--muted">Next renewal</span>
                            <span>
                                @if (!empty($nextServiceRenewal?->renewal_date))
                                    {{ optional($nextServiceRenewal->renewal_date)->format('M j, Y') }}
                                @else
                                    —
                                @endif
                            </span>
                        </li>
                    </ul>
                </section>

                <section class="oh-card">
                    <h3 class="text-sm font-semibold text-text-base mb-3">Resources</h3>
                    <p class="text-sm text-text-subtle">Notes and files are coming soon.</p>
                </section>
            </div>
        </div>

        {{-- Pagination --}}
        @if (method_exists($contacts, 'links'))
            @if ($contacts->hasPages())
                <div class="text-sm text-text-subtle space-y-3">
                    <div>
                        {{ $contacts->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
