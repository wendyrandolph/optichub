@php
    $isLocked = isset($quote) && $quote->isLocked();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
        <select id="client_id" name="client_id" class="oh-select h-10 w-full" @disabled($isLocked)>
            <option value="">Select client</option>
            @foreach ($clients as $client)
                @php
                    $label = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: $client->name ?? 'Client #' . $client->id;
                @endphp
                <option value="{{ $client->id }}" @selected(old('client_id', $quote->client_id ?? null) == $client->id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('client_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="company_id">Client company</label>
        <select id="company_id" name="company_id" class="oh-select h-10 w-full" @disabled($isLocked)>
            <option value="">Select company</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id', $quote->company_id ?? null) == $company->id)>
                    {{ $company->company_name ?? 'Company #' . $company->id }}
                </option>
            @endforeach
        </select>
        @error('company_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="title">Quote title</label>
        <input id="title" name="title" class="oh-input h-10" required @disabled($isLocked)
            value="{{ old('title', $quote->title ?? '') }}">
        @error('title')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="notes">Notes</label>
        <textarea id="notes" name="notes" class="oh-input min-h-[110px]" rows="3" @disabled($isLocked)>{{ old('notes', $quote->notes ?? '') }}</textarea>
        @error('notes')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="trade_job_id">Trade job</label>
        <select id="trade_job_id" name="trade_job_id" class="oh-select h-10 w-full" @disabled($isLocked)>
            <option value="">Link to a job (optional)</option>
            @foreach ($jobs as $job)
                <option value="{{ $job->id }}" @selected(old('trade_job_id', $quote->trade_job_id ?? null) == $job->id)>
                    {{ $job->summary }}
                </option>
            @endforeach
        </select>
        @error('trade_job_id')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="expires_at">Expires on</label>
        <input id="expires_at" name="expires_at" type="date" class="oh-input h-10" @disabled($isLocked)
            value="{{ old('expires_at', optional($quote->expires_at ?? null)->format('Y-m-d')) }}">
        @error('expires_at')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 space-y-3">
    <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-text-base">Line items</div>
        <button type="button" class="oh-btn oh-btn--secondary" id="trade-quote-add-line-item" @disabled($isLocked)>
            <i class="fa-solid fa-plus text-[12px] mr-1"></i> Add line item
        </button>
    </div>
    <div class="overflow-hidden rounded-xl border border-border-default/70">
        <table class="min-w-full text-sm" id="trade-quote-line-items-table">
            <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                <tr>
                    <th class="px-3 py-2 text-left font-medium">Item</th>
                    <th class="px-3 py-2 text-right font-medium w-24">Qty</th>
                    <th class="px-3 py-2 text-right font-medium w-32">Rate</th>
                    <th class="px-3 py-2 text-right font-medium w-32">Total</th>
                    <th class="px-3 py-2 text-right font-medium w-16"></th>
                </tr>
            </thead>
            <tbody id="trade-quote-line-items-body">
                @forelse ($itemRows as $index => $row)
                    <tr class="border-t border-border-default/60">
                        <td class="px-3 py-2 align-top">
                            <input type="hidden" name="items[{{ $index }}][position]" value="{{ $index }}" class="position-input">
                            <input type="text" name="items[{{ $index }}][description]" placeholder="Item description"
                                class="oh-input h-9 w-full" @disabled($isLocked)
                                value="{{ $row['description'] ?? '' }}">
                            @error("items.{$index}.description")
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-3 py-2 align-top">
                            <input type="number" step="0.1" min="0" name="items[{{ $index }}][quantity]"
                                class="oh-input h-9 w-full text-right trade-quote-qty" @disabled($isLocked)
                                value="{{ $row['quantity'] ?? 1 }}">
                        </td>
                        <td class="px-3 py-2 align-top">
                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]"
                                class="oh-input h-9 w-full text-right trade-quote-rate" @disabled($isLocked)
                                value="{{ $row['unit_price'] ?? 0 }}">
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="trade-quote-line-total text-sm font-semibold text-text-base">—</div>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <button type="button" class="oh-icon-btn trade-quote-remove-line" @disabled($isLocked)>
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
    <div class="flex flex-col md:flex-row md:justify-end gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:w-[320px]">
            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Tax rate (%)</span>
                <input type="number" step="0.01" min="0" name="tax_rate"
                    class="oh-input h-10 trade-quote-tax-rate" @disabled($isLocked)
                    value="{{ old('tax_rate', $quote->tax_rate ?? 0) }}">
            </label>
            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Discount type</span>
                @php $dt = old('discount_type', $quote->discount_type ?? 'none'); @endphp
                <select name="discount_type" class="oh-select h-10 trade-quote-discount-type" @disabled($isLocked)>
                    <option value="none" @selected($dt === 'none')>None</option>
                    <option value="percent" @selected($dt === 'percent')>Percent</option>
                    <option value="fixed" @selected($dt === 'fixed')>Fixed</option>
                </select>
            </label>
            <label class="grid gap-1 text-sm sm:col-span-2">
                <span class="text-text-subtle">Discount value</span>
                <input type="number" step="0.01" min="0" name="discount_value"
                    class="oh-input h-10 trade-quote-discount-value" @disabled($isLocked)
                    value="{{ old('discount_value', $quote->discount_value ?? 0) }}">
            </label>
        </div>
        <div class="rounded-xl border border-border-default/80 bg-surface-card/80 px-4 py-3 text-sm space-y-1 min-w-[220px]">
            <div class="flex justify-between">
                <span class="text-text-subtle">Subtotal</span>
                <span id="trade-quote-subtotal" class="font-semibold text-text-base">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-subtle">Tax total</span>
                <span id="trade-quote-tax-total" class="text-text-base">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-subtle">Discount</span>
                <span id="trade-quote-discount-total" class="text-text-base">$0.00</span>
            </div>
            <div class="flex justify-between text-base font-semibold">
                <span>Total</span>
                <span id="trade-quote-total">$0.00</span>
            </div>
        </div>
    </div>
    <p class="text-xs text-text-subtle">Leave a row blank to remove it.</p>
</div>

<template id="trade-quote-line-item-template">
    <tr class="border-t border-border-default/60">
        <td class="px-3 py-2 align-top">
            <input type="hidden" name="__IDX__[position]" value="__POS__" class="position-input">
            <input type="text" name="__IDX__[description]" placeholder="Item description" class="oh-input h-9 w-full">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" step="0.1" min="0" name="__IDX__[quantity]" value="1"
                class="oh-input h-9 w-full text-right trade-quote-qty">
        </td>
        <td class="px-3 py-2 align-top">
            <input type="number" step="0.01" min="0" name="__IDX__[unit_price]" value="0"
                class="oh-input h-9 w-full text-right trade-quote-rate">
        </td>
        <td class="px-3 py-2 align-top text-right">
            <div class="trade-quote-line-total text-sm font-semibold text-text-base">—</div>
        </td>
        <td class="px-3 py-2 align-top text-right">
            <button type="button" class="oh-icon-btn trade-quote-remove-line">
                <i class="fa-regular fa-trash-can text-[12px]"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('trade-quote-line-items-body');
            const tmpl = document.getElementById('trade-quote-line-item-template')?.innerHTML;
            const addBtn = document.getElementById('trade-quote-add-line-item');
            const subtotalEl = document.getElementById('trade-quote-subtotal');
            const taxEl = document.getElementById('trade-quote-tax-total');
            const discountEl = document.getElementById('trade-quote-discount-total');
            const totalEl = document.getElementById('trade-quote-total');
            const taxInput = document.querySelector('.trade-quote-tax-rate');
            const discountType = document.querySelector('.trade-quote-discount-type');
            const discountValue = document.querySelector('.trade-quote-discount-value');

            if (!body || !tmpl) return;

            let idx = body.querySelectorAll('tr').length;

            const format = (n) => `$${Number(n || 0).toFixed(2)}`;

            const renumber = () => {
                body.querySelectorAll('tr').forEach((row, i) => {
                    row.querySelectorAll('input, textarea').forEach(input => {
                        input.name = input.name.replace(/items\\[[0-9]+\\]/, `items[${i}]`);
                    });
                    const pos = row.querySelector('.position-input');
                    if (pos) pos.value = i;
                });
            };

            const recalc = () => {
                let subtotal = 0;
                body.querySelectorAll('tr').forEach(row => {
                    const qty = parseFloat(row.querySelector('.trade-quote-qty')?.value || 0);
                    const rate = parseFloat(row.querySelector('.trade-quote-rate')?.value || 0);
                    const lineTotal = qty * rate;
                    subtotal += lineTotal;
                    const totalCell = row.querySelector('.trade-quote-line-total');
                    if (totalCell) totalCell.textContent = format(lineTotal);
                });
                const taxRate = parseFloat(taxInput?.value || 0);
                const discountTypeVal = discountType?.value || 'none';
                const discountVal = parseFloat(discountValue?.value || 0);
                const taxTotal = taxRate ? subtotal * (taxRate / 100) : 0;
                let discount = 0;
                if (discountTypeVal === 'percent') {
                    discount = (subtotal + taxTotal) * (discountVal / 100);
                } else if (discountTypeVal === 'fixed') {
                    discount = discountVal;
                }
                const total = Math.max(0, subtotal + taxTotal - discount);
                subtotalEl.textContent = format(subtotal);
                if (taxEl) taxEl.textContent = format(taxTotal);
                if (discountEl) discountEl.textContent = format(discount);
                totalEl.textContent = format(total);
            };

            const addRow = (data = {}) => {
                let rowHtml = tmpl.replace(/__IDX__/g, `items[${idx}]`).replace(/__POS__/g, idx);
                const temp = document.createElement('tbody');
                temp.innerHTML = rowHtml.trim();
                const row = temp.firstChild;
                row.querySelectorAll('input, textarea').forEach(input => {
                    const key = input.name.match(/\\[(\\w+)\\]$/)?.[1];
                    if (key && data[key] !== undefined) {
                        input.value = data[key];
                    }
                });
                body.appendChild(row);
                idx++;
                renumber();
                recalc();
            };

            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    if (addBtn.disabled) return;
                    addRow();
                });
            }

            body.addEventListener('click', (e) => {
                if (e.target.closest('.trade-quote-remove-line')) {
                    e.preventDefault();
                    const row = e.target.closest('tr');
                    row?.remove();
                    renumber();
                    recalc();
                }
            });

            body.addEventListener('input', (e) => {
                if (e.target.classList.contains('trade-quote-qty') || e.target.classList.contains('trade-quote-rate')) {
                    recalc();
                }
            });
            taxInput?.addEventListener('input', recalc);
            discountType?.addEventListener('change', recalc);
            discountValue?.addEventListener('input', recalc);

            if (body.querySelectorAll('tr').length === 0) {
                addRow();
            } else {
                recalc();
            }
        });
    </script>
@endpush
