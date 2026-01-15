@php
    $plan = $plan ?? null;
    $selectedClientId = $selectedClientId ?? null;
    $itemRows = old('items');

    if ($itemRows === null) {
        $itemRows = $plan?->items?->sortBy('sort_order')->map(function ($item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ];
        })->values()->toArray();
    }

    if (empty($itemRows)) {
        $itemRows = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
    }

    $cadenceUnit = old('cadence_unit', $plan->cadence_unit ?? 'monthly');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
        <select id="client_id" name="client_id" class="oh-select h-10 w-full">
            <option value="">Select client</option>
            @foreach ($clients as $client)
                @php
                    $label = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: $client->name ?? 'Client #' . $client->id;
                @endphp
                <option value="{{ $client->id }}" @selected(old('client_id', $plan->client_id ?? $selectedClientId) == $client->id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('client_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="company_id">Client company</label>
        <select id="company_id" name="company_id" class="oh-select h-10 w-full">
            <option value="">(Unassigned)</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id', $plan->company_id ?? null) == $company->id)>
                    {{ $company->company_name ?? 'Company #' . $company->id }}
                </option>
            @endforeach
        </select>
        @error('company_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="title">Plan title</label>
        <input id="title" name="title" class="oh-input h-10" required
            value="{{ old('title', $plan->title ?? '') }}">
        @error('title')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="status">Status</label>
        <select id="status" name="status" class="oh-select h-10">
            <option value="active" @selected(old('status', $plan->status ?? 'active') === 'active')>Active</option>
            <option value="paused" @selected(old('status', $plan->status ?? 'active') === 'paused')>Paused</option>
        </select>
        @error('status')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="service_location_id">Service location</label>
        <select id="service_location_id" name="service_location_id" class="oh-select h-10 w-full">
            <option value="">(Unassigned)</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected(old('service_location_id', $plan->service_location_id ?? null) == $location->id)>
                    {{ $location->label ?: $location->address_line1 }}
                </option>
            @endforeach
        </select>
        @error('service_location_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="cadence_unit">Cadence</label>
        <select id="cadence_unit" name="cadence_unit" class="oh-select h-10">
            <option value="weekly" @selected($cadenceUnit === 'weekly')>Weekly</option>
            <option value="monthly" @selected($cadenceUnit === 'monthly')>Monthly</option>
            <option value="quarterly" @selected($cadenceUnit === 'quarterly')>Quarterly</option>
            <option value="yearly" @selected($cadenceUnit === 'yearly')>Yearly</option>
        </select>
        @error('cadence_unit')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="cadence_interval">Interval</label>
        <input id="cadence_interval" name="cadence_interval" type="number" min="1" max="12"
            class="oh-input h-10" value="{{ old('cadence_interval', $plan->cadence_interval ?? 1) }}">
        <p class="text-xs text-text-subtle">Every N weeks/months/quarters/years.</p>
        @error('cadence_interval')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="cadence_weekday">Weekday (weekly)</label>
        <select id="cadence_weekday" name="cadence_weekday" class="oh-select h-10">
            <option value="">Use start date</option>
            @foreach (['0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday'] as $val => $label)
                <option value="{{ $val }}" @selected((string) old('cadence_weekday', $plan->cadence_weekday ?? '') === (string) $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('cadence_weekday')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="cadence_month_day">Day of month</label>
        <input id="cadence_month_day" name="cadence_month_day" type="number" min="1" max="28"
            class="oh-input h-10" value="{{ old('cadence_month_day', $plan->cadence_month_day ?? '') }}">
        <p class="text-xs text-text-subtle">For monthly/quarterly/yearly plans (1-28).</p>
        @error('cadence_month_day')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="starts_on">Start date</label>
        <input id="starts_on" name="starts_on" type="date" class="oh-input h-10"
            value="{{ old('starts_on', optional($plan?->starts_on)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
        @error('starts_on')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="notes">Notes</label>
        <textarea id="notes" name="notes" class="oh-input min-h-[110px]" rows="3">{{ old('notes', $plan->notes ?? '') }}</textarea>
        @error('notes')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 space-y-3">
    <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-text-base">Line items</div>
        <button type="button" class="oh-btn oh-btn--secondary" id="service-plan-add-line-item">
            <i class="fa-solid fa-plus text-[12px] mr-1"></i> Add line item
        </button>
    </div>
    <div class="overflow-hidden rounded-xl border border-border-default/70">
        <table class="min-w-full text-sm" id="service-plan-line-items-table">
            <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                <tr>
                    <th class="px-3 py-2 text-left font-medium">Item</th>
                    <th class="px-3 py-2 text-right font-medium w-24">Qty</th>
                    <th class="px-3 py-2 text-right font-medium w-32">Rate</th>
                    <th class="px-3 py-2 text-right font-medium w-16"></th>
                </tr>
            </thead>
            <tbody id="service-plan-line-items-body">
                @foreach ($itemRows as $index => $row)
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
                        <td class="px-3 py-2 align-top text-right">
                            <button type="button" class="oh-icon-btn service-plan-remove-line">
                                <i class="fa-regular fa-trash-can text-[12px]"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-text-subtle">Leave a row blank to remove it.</p>
</div>

<template id="service-plan-line-item-template">
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
        <td class="px-3 py-2 align-top text-right">
            <button type="button" class="oh-icon-btn service-plan-remove-line">
                <i class="fa-regular fa-trash-can text-[12px]"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('service-plan-line-items-body');
            const tmpl = document.getElementById('service-plan-line-item-template')?.innerHTML;
            const addBtn = document.getElementById('service-plan-add-line-item');

            if (!body || !tmpl || !addBtn) return;

            let idx = body.querySelectorAll('tr').length;

            const addRow = () => {
                const html = tmpl.replaceAll('__IDX__', `items[${idx}]`);
                const row = document.createElement('tbody');
                row.innerHTML = html;
                body.appendChild(row.firstElementChild);
                idx += 1;
            };

            addBtn.addEventListener('click', addRow);
            body.addEventListener('click', (event) => {
                if (event.target.closest('.service-plan-remove-line')) {
                    event.target.closest('tr')?.remove();
                }
            });
        });
    </script>
@endpush
