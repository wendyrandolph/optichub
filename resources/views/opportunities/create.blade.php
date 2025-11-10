@extends('layouts.app')

@section('title', 'Add Opportunity')

@section('content')
    @php
        // Resolve tenant (model or scalar → id)
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        $stages = ['Prospect', 'Proposal Sent', 'Negotiation', 'Contract Signed', 'Lost'];
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- Page Header --}}
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Add New Opportunity</h1>
                <p class="text-sm text-text-subtle mt-1">Record a new potential deal or client engagement.</p>
            </div>
            <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center h-10 px-4 rounded-lg text-sm bg-surface-card/60 hover:bg-surface-card/90 text-text-base transition">
                <i class="fa fa-arrow-left mr-2 text-xs"></i> Back to Opportunities
            </a>
        </header>

        {{-- Flash / Error Messages --}}
        @if (session('success'))
            <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Card --}}
        <section class="rounded-xl bg-surface-card/70 border border-border-default/60 shadow-card p-6 space-y-5">
            <form method="POST" action="{{ route('tenant.opportunities.store', ['tenant' => $tenantId]) }}"
                class="space-y-5" id="oppForm">
                @csrf

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-text-subtle">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" required value="{{ old('title') }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                    border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                {{-- Organization --}}
                <div>
                    <label for="organization_id" class="block text-sm font-medium text-text-subtle">Organization</label>
                    <select id="organization_id" name="organization_id"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                     border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="">— None —</option>
                        @foreach ($organizations as $org)
                            @php
                                $orgId = $org->id ?? $org['id'];
                                $orgName = $org->name ?? ($org['name'] ?? 'Unnamed Org');
                            @endphp
                            <option value="{{ $orgId }}" @selected(old('organization_id') == $orgId)>{{ $orgName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Stage --}}
                <div>
                    <label for="stage" class="block text-sm font-medium text-text-subtle">Stage</label>
                    <select id="stage" name="stage"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                     border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        @foreach ($stages as $s)
                            <option value="{{ $s }}" @selected(old('stage') == $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Money + Probability row --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Estimated Value --}}
                    <div>
                        <label for="estimated_value" class="block text-sm font-medium text-text-subtle">Estimated
                            Value</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle text-xs">$</span>
                            <input type="number" step="0.01" min="0" id="estimated_value" name="estimated_value"
                                value="{{ old('estimated_value') }}"
                                class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base pl-6 pr-3 text-sm
                        border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </div>
                    </div>

                    {{-- Probability --}}
                    <div>
                        <label for="probability" class="block text-sm font-medium text-text-subtle">Probability (%)</label>
                        <div class="relative">
                            <input type="number" step="1" min="0" max="100" id="probability"
                                name="probability" value="{{ old('probability', 50) }}"
                                class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                        border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-subtle text-xs">%</span>
                        </div>
                        <p class="mt-1 text-xs text-text-subtle">How likely you are to win this deal.</p>
                    </div>

                    {{-- Expected Revenue (calculated) --}}
                    <div>
                        <label for="expected_revenue_display" class="block text-sm font-medium text-text-subtle">Expected
                            Revenue</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle text-xs">$</span>
                            <input type="text" id="expected_revenue_display" readonly
                                value="{{ old('expected_revenue') ? number_format((float) old('expected_revenue'), 2) : '' }}"
                                class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base pl-6 pr-3 text-sm
                        border border-border-default focus:outline-none">
                        </div>
                        {{-- Real field posted to server --}}
                        <input type="hidden" id="expected_revenue" name="expected_revenue"
                            value="{{ old('expected_revenue') }}">
                    </div>
                </div>

                {{-- Expected Close Date --}}
                <div>
                    <label for="close_date" class="block text-sm font-medium text-text-subtle">Expected Close Date</label>
                    <input type="date" id="close_date" name="close_date" value="{{ old('close_date') }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                    border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-text-subtle">Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                        class="mt-1 w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm
                       border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">{{ old('notes') }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-3">
                    <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                        class="inline-flex items-center h-10 px-4 rounded-lg text-sm
                bg-surface-card/60 hover:bg-surface-card/90 text-text-base transition">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center h-10 px-5 rounded-lg text-sm font-medium text-white
                     bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        Save Opportunity
                    </button>
                </div>
            </form>
        </section>

        {{-- Auto-calc expected revenue --}}
        @push('scripts')
            <script>
                (function() {
                    const valueEl = document.getElementById('estimated_value');
                    const probEl = document.getElementById('probability');
                    const expDisp = document.getElementById('expected_revenue_display');
                    const expReal = document.getElementById('expected_revenue');

                    function calc() {
                        const v = parseFloat(valueEl?.value || '0');
                        const p = Math.min(100, Math.max(0, parseFloat(probEl?.value || '0')));
                        const est = (isFinite(v) ? v : 0) * (isFinite(p) ? p : 0) / 100;
                        expDisp.value = est ? est.toFixed(2) : '';
                        expReal.value = est ? est.toFixed(2) : '';
                    }

                    valueEl?.addEventListener('input', calc);
                    probEl?.addEventListener('input', calc);
                    calc(); // init on load
                })();
            </script>
        @endpush

        </section>

    </div>
@endsection
