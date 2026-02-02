@extends('layouts.app')

@section('title', 'Client Companies')

@section('content')
    @php
        // Resolve tenant id from route or from the $tenant passed in
        $tenantModel = $tenant ?? request()->route('tenant');
        $tenantId = $tenantModel instanceof \App\Models\Tenant ? $tenantModel->id : (int) $tenantModel;

        $k = $kpis ?? [
            'total' => 0,
            'with_site' => 0,
            'with_phone' => 0,
            'with_contacts' => 0,
            'without_contacts' => 0,
        ];

        $q = $q ?? request('q', '');
        $filter = $filter ?? request('filter', '');
    @endphp

    <div class="oh-page space-y-6">
        {{-- Header --}}
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Clients</p>
                <h1 class="text-2xl font-semibold text-text-base">Client Companies</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Keep track of the companies you’re working with and the people inside them.
                </p>
            </div>

            {{-- Add Company --}}
            <a href="{{ route('tenant.companies.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">
                <i class="fa-solid fa-plus text-xs mr-1"></i> Add Company
            </a>
        </header>

        {{-- Quick filter cards --}}
        @php
            $cards = [
                ['label' => 'All companies', 'count' => $k['total'] ?? 0, 'filter' => ''],
                ['label' => 'Has website', 'count' => $k['with_site'] ?? 0, 'filter' => 'has_website'],
                ['label' => 'Has phone', 'count' => $k['with_phone'] ?? 0, 'filter' => 'has_phone'],
                ['label' => 'Has contacts', 'count' => $k['with_contacts'] ?? 0, 'filter' => 'has_contacts'],
                ['label' => 'No contacts', 'count' => $k['without_contacts'] ?? 0, 'filter' => 'no_contacts'],
            ];
        @endphp

        <section class="oh-card">
            <form id="company-filters-form" method="GET"
                action="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}" class="grid gap-3">
                <input type="hidden" name="filter" id="company-filter-value" value="{{ $filter ?? '' }}">

                {{-- FILTERS ROW (responsive) --}}
                <div class="grid gap-2">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">
                        Filters
                    </div>

                    {{-- Mobile + Tablet: chips/tabs row (single line scroll) --}}
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden" role="tablist" aria-label="Company filters">
                        @foreach ($cards as $card)
                            @php
                                $value = $card['filter'] ?? '';
                                $isActive = ($filter ?? '') === $value;
                            @endphp

                            <button type="button"
                                class="inline-flex items-center gap-2 whitespace-nowrap transition oh-pill {{ $isActive ? 'oh-pill--info' : 'oh-pill--muted' }}"
                                data-filter-value="{{ $value }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                                <span>{{ $card['label'] }}</span>
                                <span
                                    class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-2 rounded-full text-[11px] bg-surface-muted text-text-subtle">
                                    {{ $card['count'] ?? 0 }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Desktop: KPI cards --}}
                    <div class="hidden lg:grid grid-cols-5 gap-3" role="tablist" aria-label="Company filters">
                        @foreach ($cards as $card)
                            @php
                                $value = $card['filter'] ?? '';
                                $isActive = ($filter ?? '') === $value;
                            @endphp

                            <button type="button"
                                class="rounded-xl p-3 border transition flex flex-col justify-between min-h-[72px] text-left
                            {{ $isActive
                                ? 'border-brand-primary/45 ring-1 ring-brand-primary/25 bg-surface-muted'
                                : 'border-border-default/70 bg-surface-card hover:border-border-default' }}"
                                data-filter-value="{{ $value }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                                <div
                                    class="text-xs font-semibold uppercase tracking-wide {{ $isActive ? 'text-text-base' : 'text-text-subtle' }}">
                                    {{ $card['label'] }}
                                </div>
                                <div class="mt-1 text-2xl font-semibold text-text-base tabular-nums text-right">
                                    {{ $card['count'] ?? 0 }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- SEARCH ROW --}}
                <div class="grid gap-2">
                    <label for="q" class="text-[11px] font-semibold uppercase tracking-wider text-text-subtle">
                        Search
                    </label>

                    <input id="q" name="q" value="{{ $q ?? '' }}"
                        placeholder="Search by company, industry, or website…" class="oh-input w-full h-10" />
                </div>



                {{-- Mobile quick clear (optional) --}}
                @if (($q ?? '') !== '' || ($filter ?? '') !== '')
                    <div class="lg:hidden pt-1">
                        <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}"
                            class="text-xs text-text-subtle hover:text-text-base">
                            Clear search & filters
                        </a>
                    </div>
                @endif




                {{-- Apply/reset --}}
                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="oh-btn oh-btn--primary">
                        Apply
                    </button>

                    @if (($q ?? '') !== '' || ($filter ?? '') !== '')
                        <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </section>


        {{-- Mobile cards --}}
        <div class="md:hidden grid gap-3 grid-cols-1 sm:grid-cols-2">
            @foreach ($companies as $company)
                @php
                    $name = $company->company_name ?? '—';
                    $ind = $company->industry ?? '—';
                    $site = $company->website;
                    $count = $company->contacts_count ?? 0;
                    $projects = $company->active_projects_count ?? 0;
                    $showUrl = route('tenant.companies.show', ['tenant' => $tenantId, 'company' => $company->id]);
                    $editUrl = route('tenant.companies.edit', ['tenant' => $tenantId, 'company' => $company->id]);
                @endphp
                <article class="oh-card p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <a href="{{ $showUrl }}"
                                class="font-semibold text-text-base hover:text-brand-primary">{{ $name }}</a>
                            <div class="text-xs text-text-subtle">{{ $ind ?: '—' }}</div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ $showUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="View"
                                aria-label="View">
                                <i class="fa-solid fa-circle-info text-[12px]"></i>
                            </a>
                            <a href="{{ $editUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="Edit"
                                aria-label="Edit">
                                <i class="fa-solid fa-pen text-[12px]"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="oh-pill">Contacts: {{ $count }}</span>
                        <span class="oh-pill">Active projects: {{ $projects }}</span>
                        @if ($site)
                            <a class="oh-pill oh-pill--muted truncate max-w-[180px]" href="{{ $site }}"
                                target="_blank" rel="noopener">
                                <i class="fa-regular fa-link text-[10px]"></i>
                                {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $site), 24) }}
                            </a>
                        @else
                            <span class="oh-pill oh-pill--muted">No website</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden xl:block oh-card p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-muted/60">
                        <tr class="text-left text-text-subtle border-b border-border-default/60">
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Company</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Industry</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px]">Website</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px] text-center">Contacts</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px] text-center">Active Projects</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wide text-[11px] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/60">
                        @forelse ($companies as $company)
                            @php
                                $name = $company->company_name ?? '—';
                                $ind = $company->industry ?? '—';
                                $site = $company->website;
                                $count = $company->contacts_count ?? 0;
                                $projects = $company->active_projects_count ?? 0;
                                $showUrl = route('tenant.companies.show', [
                                    'tenant' => $tenantId,
                                    'company' => $company->id,
                                ]);
                                $editUrl = route('tenant.companies.edit', [
                                    'tenant' => $tenantId,
                                    'company' => $company->id,
                                ]);
                            @endphp
                            <tr class="group hover:bg-surface-accent/40 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="font-semibold text-text-base">
                                        <a href="{{ $showUrl }}"
                                            class="hover:text-brand-primary">{{ $name }}</a>
                                    </div>
                                    <div class="text-[12px] text-text-subtle truncate">
                                        {{ $company->address ?? 'No address on file' }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-text-base">{{ $ind }}</td>
                                <td class="px-6 py-3">
                                    @if ($site)
                                        <a href="{{ $site }}" target="_blank" rel="noopener"
                                            class="oh-link text-sm truncate inline-flex items-center gap-2">
                                            <i class="fa-regular fa-link text-[11px] text-text-subtle"></i>
                                            {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $site), 32) }}
                                        </a>
                                    @else
                                        <span class="text-text-subtle text-sm">No website</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ $showUrl }}#contacts"
                                        class="oh-pill oh-pill--muted inline-flex justify-center">
                                        {{ $count }}
                                        {{ \Illuminate\Support\Str::plural('contact', $count) }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @php
                                        $projectsUrl = Route::has('tenant.projects.index')
                                            ? route('tenant.projects.index', [
                                                'tenant' => $tenantId,
                                                'company' => $company->id,
                                            ])
                                            : null;
                                    @endphp
                                    @if ($projects > 0 && $projectsUrl)
                                        <a href="{{ $projectsUrl }}"
                                            class="oh-pill oh-pill--accent inline-flex justify-center">
                                            {{ $projects }} active
                                        </a>
                                    @else
                                        <span class="oh-pill oh-pill--muted">
                                            {{ $projects > 0 ? $projects . ' active' : 'No active work' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ $showUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="View"
                                            aria-label="View">
                                            <i class="fa-solid fa-eye text-[12px]"></i>
                                        </a>
                                        <a href="{{ $editUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="Edit"
                                            aria-label="Edit">
                                            <i class="fa-solid fa-pen text-[12px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-text-subtle">
                                    No client companies found yet. Start by adding your first company above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards for md-xl --}}
        <div class="hidden md:grid gap-3 xl:hidden grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($companies as $company)
                @php
                    $name = $company->company_name ?? '—';
                    $ind = $company->industry ?? '—';
                    $site = $company->website;
                    $count = $company->contacts_count ?? 0;
                    $projects = $company->active_projects_count ?? 0;
                    $showUrl = route('tenant.companies.show', ['tenant' => $tenantId, 'company' => $company->id]);
                    $editUrl = route('tenant.companies.edit', ['tenant' => $tenantId, 'company' => $company->id]);
                @endphp
                <article class="oh-card p-4 space-y-3 relative">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ $showUrl }}"
                                class="font-semibold text-text-base hover:text-brand-primary">{{ $name }}</a>
                            <div class="text-xs text-text-subtle">{{ $ind ?: '—' }}</div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ $showUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="View"
                                aria-label="View">
                                <i class="fa-solid fa-eye text-[12px]"></i>
                            </a>
                            <a href="{{ $editUrl }}" class="oh-icon-btn oh-tooltip" data-tooltip="Edit"
                                aria-label="Edit">
                                <i class="fa-solid fa-pen text-[12px]"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="oh-pill">Contacts: {{ $count }}</span>
                        <span class="oh-pill">Active projects: {{ $projects }}</span>
                        @if ($site)
                            <a class="oh-pill oh-pill--muted truncate max-w-[180px]" href="{{ $site }}"
                                target="_blank" rel="noopener">
                                <i class="fa-regular fa-link text-[10px]"></i>
                                {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $site), 24) }}
                            </a>
                        @else
                            <span class="oh-pill oh-pill--muted">No website</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if (method_exists($companies, 'links'))
            @php $pager = $companies->appends(['q' => $q, 'filter' => $filter]); @endphp
            @if ($pager->hasPages())
                <div class="px-4 py-3 border-t border-border-default/60 text-sm text-text-subtle space-y-3">
                    <div>
                        {{ $pager->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('company-filters-form');
            if (!form) return;

            const filterInput = document.getElementById('company-filter-value');
            const chips = Array.from(document.querySelectorAll('[data-filter-value]'));
            const search = document.getElementById('q');

            const isLgUp = () => window.matchMedia('(min-width: 1024px)').matches;

            const submit = () => form.submit();

            // clicking a filter:
            // - always set hidden filter
            // - on <lg: auto-submit
            // - on lg+: do NOT auto-submit (let Apply control it)
            chips.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (filterInput) filterInput.value = btn.dataset.filterValue ?? '';
                    if (!isLgUp()) submit();
                });
            });

            // search input:
            // - on <lg: debounce auto-submit
            // - on lg+: do nothing (Apply button)
            let timer;
            if (search) {
                search.addEventListener('input', () => {
                    if (isLgUp()) return;
                    clearTimeout(timer);
                    timer = setTimeout(() => submit(), 300);
                });
            }
        })();
    </script>
@endpush
