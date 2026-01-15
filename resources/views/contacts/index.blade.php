@extends('layouts.app')

@section('title', 'Contacts')

@push('head')
    <style>
        /* Rolodex-style contact cards */
        .contact-card {
            position: relative;
            overflow: visible;
            transition: transform 150ms ease, box-shadow 150ms ease, border-color 150ms ease;
            z-index: 0;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 44px;
            border-bottom-right-radius: 10px;
            background: rgba(var(--brand-primary), 0.12);
        }

        .contact-card[data-status='active']::before {
            background: rgba(var(--status-success), 0.2);
        }

        .contact-card[data-status='inactive']::before {
            background: rgba(var(--border), 0.35);
        }

        .contact-card[data-status='invited']::before {
            background: rgba(var(--brand-primary), 0.18);
        }

        .contact-card:hover {
            box-shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
            transform: translateY(-1px);
            z-index: 5;
        }

        @media (prefers-reduced-motion: reduce) {
            .contact-card {
                transition: none;
                transform: none !important;
                box-shadow: inherit !important;
            }
        }

        /* Ensure tooltips and action buttons aren't clipped */
        .contact-actions .oh-tooltip {
            position: relative;
            overflow: visible;
            z-index: 10;
        }

        .contact-actions .oh-tooltip::after,
        .contact-actions .oh-tooltip::before {
            z-index: 50;
        }

        .contact-pills {
            padding-top: 14px;
            padding-bottom: 14px;
        }
    </style>
@endpush

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? (auth()->user()->tenant_id ?? null));

        $contacts = $contacts ?? ($clients ?? collect());
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

    <div class="oh-page space-y-6">
        @if (session('success_message') || session('error_message'))
            <div>
                @if (session('success_message'))
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('success_message') }}
                    </div>
                @endif
                @if (session('error_message'))
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mt-2">
                        {{ session('error_message') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Header --}}
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">People</p>
                <h1 class="text-2xl font-semibold text-text-base">Contacts</h1>
                <p class="text-sm text-text-subtle">People you work with – clients, decision-makers, and collaborators.</p>
            </div>
            @if ($tenantId)
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Add contact
                    </a>
                </div>
            @endif
        </header>

        {{-- Filters --}}
        <section class="oh-card space-y-3">
            <form method="GET"
                action="{{ $tenantId ? route('tenant.contacts.index', ['tenant' => $tenantId]) : url()->current() }}"
                class="grid gap-3 w-full grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 items-end">
                <label class="grid gap-1 text-sm col-span-2 xl:col-span-2">
                    <span class="text-text-subtle">Search</span>
                    <input id="contact-search" name="search" type="search" placeholder="Name, email, company…"
                        value="{{ $qSearch }}" class="oh-input h-10" />
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Status</span>
                    <select id="contact-status" name="status" class="oh-select h-10">
                        <option value="" @selected($qStatus === '')>All</option>
                        <option value="active" @selected($qStatus === 'active')>Active</option>
                        <option value="inactive" @selected($qStatus === 'inactive')>Inactive</option>
                        <option value="invited" @selected($qStatus === 'invited')>Invited</option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Portal access</span>
                    <select id="contact-login" name="login" class="oh-select h-10">
                        <option value="" @selected($qLogin === '')>All</option>
                        <option value="yes" @selected($qLogin === 'yes')>Has login</option>
                        <option value="no" @selected($qLogin === 'no')>No login</option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Sort by</span>
                    <select id="contact-sort" name="sort" class="oh-select h-10">
                        <option value="name_asc" @selected($qSort === 'name_asc')>Name A–Z</option>
                        <option value="name_desc" @selected($qSort === 'name_desc')>Name Z–A</option>
                        <option value="updated" @selected($qSort === 'updated')>Recently updated</option>
                    </select>
                </label>

                <div class="flex flex-row gap-2 justify-end w-full col-span-2 xl:col-span-1 mt-1 mb-1">
                    <button type="submit" class="oh-btn oh-btn--primary w-1/2 sm:w-auto">
                        Apply
                    </button>
                    <a href="{{ $tenantId ? route('tenant.contacts.index', ['tenant' => $tenantId]) : url()->current() }}"
                        class="oh-btn w-1/2 sm:w-auto">Reset</a>
                </div>
            </form>

            {{-- Status chips (matches Leads style) --}}
            <div class="flex flex-wrap gap-2">
                @php
                    $seg = [
                        '' => 'All',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'invited' => 'Invited',
                    ];
                @endphp
                @foreach ($seg as $val => $label)
                    @php $isActive = $qStatus === $val; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['status' => $val ?: null, 'page' => null]) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border border-border-default
                  {{ $isActive ? 'bg-[rgb(var(--brand-primary)/.14)] text-text-base ring-1 ring-[rgb(var(--brand-primary)/.25)]' : 'bg-surface-card text-text-subtle hover:text-text-base' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span>{{ $label }}</span>
                        @if ($isActive)
                            <i class="fa-solid fa-check text-[10px] opacity-70"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Contact cards grid (all breakpoints) --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
            @forelse ($contacts as $contact)
                @php
                    $first = $contact->firstName ?? ($contact->first_name ?? '');
                    $last = $contact->lastName ?? ($contact->last_name ?? '');
                    $email = $contact->email ?? null;
                    $phone = $contact->phone_formatted ?? ($contact->phone ?? null);
                    $status = strtolower((string) ($contact->status ?? 'active'));
                    $hasLogin = (bool) ($contact->has_login ?? ($contact->portal_access ?? false));
                    $invitedAt = $contact->invited_at ?? null;
                    $lastLogin = $contact->last_login_at ?? null;
                    $companyName = $contact->company_name ?? ($contact->client_company_name ?? null);
                    $name = trim($first . ' ' . $last) ?: $email;

                    $statusPill = $status === 'inactive' ? 'oh-pill' : 'oh-pill oh-pill--success';
                    $invited = !$lastLogin && $invitedAt;
                @endphp
                <article class="oh-card contact-card h-full flex flex-col"
                    data-status="{{ $invited ? 'invited' : $status }}">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl grid place-items-center text-xs font-bold"
                            style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary)); border: 1px solid rgba(var(--brand-primary)/.28);">
                            {{ $initials($first, $last) }}
                        </div>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="font-semibold text-text-base truncate">
                                {{ $name }}
                            </div>
                            <div class="text-xs text-text-subtle truncate flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-[10px] text-text-subtle"></i>
                                <span>{{ $email ?: 'No email' }}</span>
                            </div>
                            <div class="text-xs text-text-subtle truncate flex items-center gap-2">
                                <i class="fa-solid fa-phone text-[10px] text-text-subtle"></i>
                                <span>{{ $phone ?: 'No phone' }}</span>
                            </div>
                            @if ($companyName)
                                <div class="text-xs text-text-subtle truncate">{{ $companyName }}</div>
                            @endif
                            <div class="flex flex-wrap gap-3 text-[11px] m-25 contact-pills">
                                <span class="{{ $statusPill }} px-3 py-1">{{ ucfirst($status) }}</span>
                                <span
                                    class="oh-pill oh-pill--muted px-3 py-1">{{ $hasLogin ? 'Portal access' : 'No login' }}</span>
                                @if ($invited)
                                    <span class="oh-pill oh-pill--info px-3 py-1">Invited</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-auto pt-3 border-t border-border-default/60 flex items-center justify-between text-xs text-text-subtle contact-footer">
                        <div></div>
                        <div class="contact-actions inline-flex items-center gap-2">
                            <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View">
                                <i class="fa-solid fa-circle-info text-[12px]"></i>
                            </a>
                            <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                                class="oh-icon-btn oh-tooltip" data-tooltip="Edit" aria-label="Edit">
                                <i class="fa-solid fa-pen text-[12px]"></i>
                            </a>
                            @if (!empty($contact->email))
                                <a href="mailto:{{ $contact->email }}" class="oh-icon-btn oh-tooltip" data-tooltip="Email"
                                    aria-label="Email">
                                    <i class="fa-solid fa-envelope text-[12px]"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="oh-card p-6 text-center text-text-subtle">No contacts found. Try adjusting filters.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($contacts && method_exists($contacts, 'links'))
            @php $pager = $contacts->appends(request()->only(['search', 'status', 'login', 'sort'])); @endphp
            @if ($pager->hasPages())
                <div class="text-sm text-text-subtle space-y-3">
                    <div>Showing {{ $pager->firstItem() ?? 0 }} to {{ $pager->lastItem() ?? $pager->count() }} of
                        {{ method_exists($pager, 'total') ? $pager->total() : $pager->count() }} results</div>
                    <div class="flex items-center justify-between">
                        @if ($pager->onFirstPage())
                            <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                        @else
                            <a href="{{ $pager->previousPageUrl() }}" class="oh-btn">Previous</a>
                        @endif
                        @if ($pager->hasMorePages())
                            <a href="{{ $pager->nextPageUrl() }}" class="oh-btn">Next</a>
                        @else
                            <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
