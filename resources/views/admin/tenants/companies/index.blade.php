@extends('layouts.app')

@section('title', 'Client Companies · ' . ($tenant->name ?? 'Tenant'))

@section('content')
    @php
        $q = $q ?? request('q', '');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">
                    Tenant: {{ $tenant->name ?? 'Workspace' }}
                </p>
                <h1 class="text-2xl font-semibold text-text-base">Client 2 Companies</h1>
                <p class="text-sm text-text-subtle mt-1">
                    You’re viewing the companies this tenant works with inside their Renlo workspace.
                </p>
            </div>

            <a href="{{ route('admin.tenants.show', $tenant) }}"
                class="inline-flex items-center justify-center h-9 px-3 rounded-lg text-xs font-medium text-text-base
                      bg-surface-card/90 border border-border-default/70 hover:bg-surface-card transition">
                Back to tenant
            </a>
        </header>

        {{-- Filters --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60">
            <form method="GET" action="{{ route('admin.tenants.companies.index', $tenant) }}"
                class="p-4 md:p-5 flex flex-col md:flex-row md:flex-wrap gap-3 md:items-center">

                <div class="flex-1 md:w-[320px]">
                    <label for="q" class="sr-only">Search</label>
                    <input id="q" name="q" value="{{ $q }}"
                        placeholder="Search by company, industry, or website…"
                        class="w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                                  border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div class="flex flex-wrap gap-2 md:ml-auto">
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
                                   bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        <i class="fa-solid fa-filter mr-2 text-xs"></i> Filter
                    </button>

                    @if ($q)
                        <a href="{{ route('admin.tenants.companies.index', $tenant) }}"
                            class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm
                                  bg-surface-card/60 hover:bg-surface-card/90 text-text-base border border-border-default/70">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-card">
                    <tr class="text-left text-text-subtle">
                        <th class="px-6 py-3 font-medium">Company</th>
                        <th class="px-6 py-3 font-medium">Industry</th>
                        <th class="px-6 py-3 font-medium">Website</th>
                        <th class="px-6 py-3 font-medium text-center">Contacts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60">
                    @forelse ($companies as $company)
                        @php
                            $name = $company->company_name ?? '—';
                            $ind = $company->industry ?? '—';
                            $site = $company->website;
                            $count = $company->contacts_count ?? 0;
                        @endphp
                        <tr class="hover:bg-surface-accent/30">
                            <td class="px-6 py-3 text-text-base">
                                <span class="font-medium">{{ $name }}</span>
                                @if ($company->address)
                                    <div class="text-[11px] text-text-subtle mt-0.5">
                                        {{ $company->address }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-text-base">{{ $ind }}</td>
                            <td class="px-6 py-3">
                                @if ($site)
                                    <a href="{{ $site }}" target="_blank" rel="noopener"
                                        class="text-blue-700 hover:underline">
                                        {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $site), 32) }}
                                    </a>
                                @else
                                    <span class="text-text-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center text-text-base">
                                {{ $count }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-text-subtle">
                                No client companies found for this tenant yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if (method_exists($companies, 'links'))
                <div class="px-4 py-3 border-t border-border-default/60">
                    {{ $companies->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
