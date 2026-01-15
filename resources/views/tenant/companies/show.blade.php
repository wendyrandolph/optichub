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
            <div class="space-y-2">
                <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}"
                    class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base oh-link-underline">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to companies
                </a>
                <div>
                    <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">Clients</p>
                    <h1 class="text-2xl font-semibold text-text-base flex items-center gap-2">
                        {{ $company->company_name ?? 'Company' }}
                        @if ($company->industry)
                            <span class="oh-pill oh-pill--muted text-[11px]">{{ $company->industry }}</span>
                        @endif
                    </h1>
                    <p class="text-sm text-text-subtle">
                        Company overview, linked contacts, services, and activity.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 text-sm text-text-subtle">
                    @if ($company->website)
                        <a href="{{ $company->website }}" target="_blank" rel="noopener"
                            class="oh-pill oh-pill--muted inline-flex items-center gap-2">
                            <i class="fa-solid fa-link text-[11px]"></i>
                            {{ preg_replace('#^https?://#', '', $company->website) }}
                        </a>
                    @else
                        <span class="oh-pill oh-pill--muted">No website</span>
                    @endif

                    @if ($company->address)
                        <span class="oh-pill oh-pill--muted inline-flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-[11px]"></i>
                            {{ $company->address }}
                        </span>
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
                <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-user-plus mr-2 text-xs"></i>
                    Add contact
                </a>
                <a href="{{ route('tenant.companies.edit', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="oh-btn">
                    <i class="fa-solid fa-pen-to-square mr-2 text-xs"></i>
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
                                </div>
                            </article>
                        @empty
                            <div class="text-sm text-text-subtle px-2">No contacts yet. Add your first contact to keep everyone organized for this company.</div>
                        @endforelse
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden md:block">
                        <table class="min-w-full text-sm">
                            <thead class="bg-surface-card">
                                <tr class="text-left text-text-subtle">
                                    <th class="px-6 py-3 font-medium">Name</th>
                                    <th class="px-6 py-3 font-medium">Role / Title</th>
                                    <th class="px-6 py-3 font-medium">Email</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
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
                                        <td class="px-6 py-3 text-text-base">
                                            <div class="text-[12px] text-text-subtle">{{ $contact->email ?? '—' }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                                    class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View">
                                                    <i class="fa-solid fa-circle-info text-[12px]"></i>
                                                </a>
                                                @if (Route::has('tenant.contacts.edit'))
                                                    <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                                        class="oh-icon-btn oh-tooltip" data-tooltip="Edit" aria-label="Edit">
                                                        <i class="fa-solid fa-pen text-[12px]"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-text-subtle">No contacts yet. Add your first contact to keep everyone organized for this company.</td>
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
                            @if (Route::has('tenant.services.index'))
                                <a href="{{ route('tenant.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                                    class="oh-btn oh-btn--ghost text-xs">
                                    View all services
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
                                    <span class="oh-pill oh-pill--muted">{{ ucfirst($service->status ?? 'active') }}</span>
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
                    <h3 class="text-sm font-semibold text-text-base mb-3">Notes</h3>
                    <p class="text-sm text-text-subtle">Notes coming soon.</p>
                </section>

                <section class="oh-card">
                    <h3 class="text-sm font-semibold text-text-base mb-3">Files</h3>
                    <p class="text-sm text-text-subtle">Files coming soon.</p>
                </section>
            </div>
        </div>

        {{-- Pagination --}}
        @if (method_exists($contacts, 'links'))
            @if ($contacts->hasPages())
                <div class="text-sm text-text-subtle space-y-3">
                    <div>Showing {{ $contacts->firstItem() }} to {{ $contacts->lastItem() }} of {{ $contacts->total() }} results</div>
                    <div class="flex items-center justify-between">
                        @if ($contacts->onFirstPage())
                            <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                        @else
                            <a href="{{ $contacts->previousPageUrl() }}" class="oh-btn">Previous</a>
                        @endif
                        @if ($contacts->hasMorePages())
                            <a href="{{ $contacts->nextPageUrl() }}" class="oh-btn">Next</a>
                        @else
                            <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
