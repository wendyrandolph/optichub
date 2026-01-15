@extends('layouts.trades')

@section('title', 'New Trade Job')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $warrantyScope = $tenant->trades_warranty_scope ?? 'job';
        $selectedTemplateId = old('template_id', $selectedTemplate?->id);
        $prefillSummary = old('summary', $selectedTemplate?->summary ?? '');
        $prefillType = old('type', $selectedTemplate?->type ?? 'service');
        $prefillStatus = old('status', $selectedTemplate?->default_status ?? 'open');
        $prefillDescription = old('description', $selectedTemplate?->description ?? '');
        $workType = $tenant->trades_work_type ?? 'both';
        $prefillPropertyType = old('property_type', $selectedTemplate?->property_type ?? ($workType === 'both' ? '' : $workType));

        $itemRows = old('items');
        if (empty($itemRows) && $selectedTemplate?->items) {
            $itemRows = $selectedTemplate->items
                ->sortBy('sort_order')
                ->map(fn($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? 0,
                ])->toArray();
        }
        if (!is_array($itemRows)) {
            $itemRows = [];
        }

        $checklistRows = old('checklist');
        if (empty($checklistRows) && $selectedTemplate?->checklistItems) {
            $checklistRows = $selectedTemplate->checklistItems
                ->sortBy('sort_order')
                ->map(fn($item) => [
                    'label' => $item->label,
                    'is_required' => $item->is_required,
                ])->toArray();
        }
        if (!is_array($checklistRows)) {
            $checklistRows = [];
        }
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3">
            <a href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                Back to jobs
            </a>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">New Trade Job</h1>
                <p class="text-sm text-text-subtle mt-1">Log a service or project job for a client.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST" action="{{ route('tenant.trades.jobs.store', ['tenant' => $tenantKey]) }}"
                class="space-y-5">
                @csrf
                <input type="hidden" name="template_id" value="{{ $selectedTemplateId }}">

                @if (!empty($templates) && $templates->count() > 0)
                    <div class="rounded-xl border border-border-default/70 bg-surface-muted/60 px-4 py-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-text-subtle">Job template</div>
                                <div class="text-sm text-text-base">Start from a template if this is a common job.</div>
                            </div>
                            <form method="GET" action="{{ route('tenant.trades.jobs.create', ['tenant' => $tenantKey]) }}"
                                class="flex items-center gap-2">
                                <select name="template" class="oh-select h-9">
                                    <option value="">Select template</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}" @selected((string) $selectedTemplateId === (string) $template->id)>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="oh-btn oh-btn--secondary" type="submit">Apply</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="summary">Job summary</label>
                        <input id="summary" name="summary" class="oh-input h-10" required value="{{ $prefillSummary }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="type">Job type</label>
                        <select id="type" name="type" class="oh-select h-10">
                            <option value="service" @selected($prefillType === 'service')>Service job</option>
                            <option value="project" @selected($prefillType === 'project')>Project job</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="property_type">Property type</label>
                        @if ($workType === 'both')
                            <select id="property_type" name="property_type" class="oh-select h-10">
                                <option value="">Select type</option>
                                <option value="residential" @selected($prefillPropertyType === 'residential')>Residential</option>
                                <option value="commercial" @selected($prefillPropertyType === 'commercial')>Commercial</option>
                            </select>
                        @else
                            <input type="hidden" name="property_type" value="{{ $workType }}">
                            <div class="oh-input h-10 flex items-center text-sm text-text-base">
                                {{ ucfirst($workType) }}
                            </div>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="status">Status</label>
                        <select id="status" name="status" class="oh-select h-10">
                            @foreach (['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)
                                <option value="{{ $value }}" @selected($prefillStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
                        <select id="client_id" name="client_id" class="oh-select h-10">
                            <option value="">Select client</option>
                            @foreach ($clients as $client)
                                @php
                                    $clientLabel =
                                        trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?:
                                        'Client #' . $client->id;
                                @endphp
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedClientId) === (string) $client->id)>
                                    {{ $clientLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="oh-select h-10">
                            <option value="">(Unassigned)</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="service_location_id">Service location</label>
                        <select id="service_location_id" name="service_location_id" class="oh-select h-10">
                            <option value="">(Unassigned)</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('service_location_id', $selectedLocationId) === (string) $location->id)>
                                    {{ $location->label ?: $location->address_line1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-input min-h-[120px]" rows="4">{{ $prefillDescription }}</textarea>
                    </div>

                    @if ($warrantyScope === 'job')
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-text-base" for="warranty_ends_on">Warranty ends</label>
                            <input id="warranty_ends_on" name="warranty_ends_on" type="date" class="oh-input h-10"
                                value="{{ old('warranty_ends_on') }}">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-sm font-medium text-text-base" for="warranty_terms">Warranty terms</label>
                            <input id="warranty_terms" name="warranty_terms" class="oh-input h-10"
                                value="{{ old('warranty_terms') }}" placeholder="e.g. Labor covered for 12 months">
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-text-base">Line items</div>
                        <button type="button" class="oh-btn oh-btn--secondary" id="trade-job-add-line-item">
                            <i class="fa-solid fa-plus text-[12px] mr-1"></i> Add line item
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-border-default/70">
                        <table class="min-w-full text-sm" id="trade-job-line-items-table">
                            <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Item</th>
                                    <th class="px-3 py-2 text-right font-medium w-24">Qty</th>
                                    <th class="px-3 py-2 text-right font-medium w-32">Rate</th>
                                    @if ($warrantyScope === 'line_item')
                                        <th class="px-3 py-2 text-left font-medium w-40">Warranty ends</th>
                                        <th class="px-3 py-2 text-left font-medium">Warranty terms</th>
                                    @endif
                                    <th class="px-3 py-2 text-right font-medium w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="trade-job-line-items-body">
                                @forelse ($itemRows as $index => $row)
                                    <tr class="border-t border-border-default/60">
                                        <td class="px-3 py-2 align-top">
                                            <input type="text" name="items[{{ $index }}][description]" placeholder="Item description"
                                                class="oh-input h-9 w-full" value="{{ $row['description'] ?? '' }}">
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.1" min="0" name="items[{{ $index }}][quantity]"
                                                class="oh-input h-9 w-full text-right" value="{{ $row['quantity'] ?? 1 }}">
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]"
                                                class="oh-input h-9 w-full text-right" value="{{ $row['unit_price'] ?? 0 }}">
                                        </td>
                                        @if ($warrantyScope === 'line_item')
                                            <td class="px-3 py-2 align-top">
                                                <input type="date" name="items[{{ $index }}][warranty_ends_on]" class="oh-input h-9 w-full"
                                                    value="{{ $row['warranty_ends_on'] ?? '' }}">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="text" name="items[{{ $index }}][warranty_terms]" class="oh-input h-9 w-full"
                                                    value="{{ $row['warranty_terms'] ?? '' }}" placeholder="Optional">
                                            </td>
                                        @endif
                                        <td class="px-3 py-2 align-top text-right">
                                            <button type="button" class="oh-icon-btn trade-job-remove-line">
                                                <i class="fa-regular fa-trash-can text-[12px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- JS adds a starter row --}}
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-text-subtle">Leave a row blank to remove it.</p>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-text-base">Checklist</div>
                        <button type="button" class="oh-btn oh-btn--secondary" id="trade-job-add-checklist">
                            <i class="fa-solid fa-plus text-[12px] mr-1"></i> Add item
                        </button>
                    </div>
                    <div class="space-y-2" id="trade-job-checklist-body">
                        @forelse ($checklistRows as $index => $row)
                            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-border-default/60 px-3 py-2">
                                <input type="text" name="checklist[{{ $index }}][label]" class="oh-input h-9 flex-1"
                                    value="{{ $row['label'] ?? '' }}" placeholder="Checklist item">
                                <label class="flex items-center gap-2 text-xs text-text-subtle">
                                    <input type="checkbox" name="checklist[{{ $index }}][is_required]" value="1"
                                        @checked($row['is_required'] ?? true) class="rounded border-border-default text-brand-primary">
                                    Required
                                </label>
                                <button type="button" class="oh-icon-btn trade-job-remove-checklist">
                                    <i class="fa-regular fa-trash-can text-[12px]"></i>
                                </button>
                            </div>
                        @empty
                            {{-- JS adds a starter row --}}
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}" class="oh-btn">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit">Create job</button>
                </div>
            </form>
        </div>
    </div>

    <template id="trade-job-line-item-template">
        <tr class="border-t border-border-default/60">
            <td class="px-3 py-2 align-top">
                <input type="text" name="__IDX__[description]" placeholder="Item description" class="oh-input h-9 w-full">
            </td>
            <td class="px-3 py-2 align-top">
                <input type="number" step="0.1" min="0" name="__IDX__[quantity]" value="1"
                    class="oh-input h-9 w-full text-right">
            </td>
            <td class="px-3 py-2 align-top">
                <input type="number" step="0.01" min="0" name="__IDX__[unit_price]" value="0"
                    class="oh-input h-9 w-full text-right">
            </td>
            @if ($warrantyScope === 'line_item')
                <td class="px-3 py-2 align-top">
                    <input type="date" name="__IDX__[warranty_ends_on]" class="oh-input h-9 w-full">
                </td>
                <td class="px-3 py-2 align-top">
                    <input type="text" name="__IDX__[warranty_terms]" class="oh-input h-9 w-full" placeholder="Optional">
                </td>
            @endif
            <td class="px-3 py-2 align-top text-right">
                <button type="button" class="oh-icon-btn trade-job-remove-line">
                    <i class="fa-regular fa-trash-can text-[12px]"></i>
                </button>
            </td>
        </tr>
    </template>

    <template id="trade-job-checklist-template">
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-border-default/60 px-3 py-2">
            <input type="text" name="__IDX__[label]" class="oh-input h-9 flex-1" placeholder="Checklist item">
            <label class="flex items-center gap-2 text-xs text-text-subtle">
                <input type="checkbox" name="__IDX__[is_required]" value="1"
                    class="rounded border-border-default text-brand-primary" checked>
                Required
            </label>
            <button type="button" class="oh-icon-btn trade-job-remove-checklist">
                <i class="fa-regular fa-trash-can text-[12px]"></i>
            </button>
        </div>
    </template>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lineBody = document.getElementById('trade-job-line-items-body');
                const lineTemplate = document.getElementById('trade-job-line-item-template')?.innerHTML;
                const addLine = document.getElementById('trade-job-add-line-item');

                const checklistBody = document.getElementById('trade-job-checklist-body');
                const checklistTemplate = document.getElementById('trade-job-checklist-template')?.innerHTML;
                const addChecklist = document.getElementById('trade-job-add-checklist');

                if (lineBody && lineTemplate && addLine) {
                    const addRow = () => {
                        const index = lineBody.querySelectorAll('tr').length;
                        const html = lineTemplate.replace(/__IDX__/g, `items[${index}]`);
                        lineBody.insertAdjacentHTML('beforeend', html);
                    };

                    addLine.addEventListener('click', addRow);

                    lineBody.addEventListener('click', (event) => {
                        const button = event.target.closest('.trade-job-remove-line');
                        if (!button) return;
                        button.closest('tr')?.remove();
                    });

                    if (lineBody.querySelectorAll('tr').length === 0) {
                        addRow();
                    }
                }

                if (checklistBody && checklistTemplate && addChecklist) {
                    const addRow = () => {
                        const index = checklistBody.querySelectorAll('[data-checklist-row]').length || checklistBody.children.length;
                        const html = checklistTemplate.replace(/__IDX__/g, `checklist[${index}]`);
                        checklistBody.insertAdjacentHTML('beforeend', html);
                    };

                    addChecklist.addEventListener('click', addRow);

                    checklistBody.addEventListener('click', (event) => {
                        const button = event.target.closest('.trade-job-remove-checklist');
                        if (!button) return;
                        button.closest('div')?.remove();
                    });

                    if (checklistBody.children.length === 0) {
                        addRow();
                    }
                }
            });
        </script>
    @endpush
@endsection
