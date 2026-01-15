@php
    $isTrades = request()->routeIs('tenant.trades.invoices.*');
    $layout = $layout ?? ($isTrades ? 'layouts.trades' : 'layouts.app');
    $section = $section ?? ($isTrades ? 'trades-content' : 'content');
    $routePrefix = $routePrefix ?? ($isTrades ? 'tenant.trades.invoices' : 'tenant.invoices');
@endphp

@extends($layout)

@section('title', 'Edit Invoice')

@php
    $tenantParam = $tenant ?? request()->route('tenant');
    $isSent = (bool) $invoice->sent_at;
    $statusValue = old('status', $invoice->status ?? 'draft');
    $statusPill = match ($statusValue) {
        'paid' => 'oh-pill oh-pill--success',
        'overdue' => 'oh-pill oh-pill--danger',
        'sent' => 'oh-pill oh-pill--info',
        default => 'oh-pill',
    };
    $clientName = optional($invoice->client)->full_name ?? trim(($invoice->client->firstName ?? '') . ' ' . ($invoice->client->lastName ?? '')) ?? '—';
@endphp

@section($section)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header card --}}
        <div class="oh-card p-5 border border-border-default bg-surface-card shadow-card space-y-3">
            <div class="flex items-center justify-between">
                <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantParam, 'invoice' => $invoice]) }}"
                    class="oh-link-underline inline-flex self-start items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                    <i class="fa-solid fa-arrow-left text-[10px] mr-2"></i> Back to invoice
                </a>
                @if ($isSent)
                    <span class="oh-pill oh-pill--warning text-[11px]">
                        Sent — edits may notify client
                    </span>
                @endif
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] uppercase tracking-wider text-text-subtle">Billing</p>
                    <h1 class="text-2xl font-semibold text-text-base">Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</h1>
                    <p class="text-sm text-text-subtle">Client: {{ $clientName }}</p>
                </div>
                <span class="{{ $statusPill }} text-[11px] px-3 py-1">{{ ucfirst($statusValue) }}</span>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route($routePrefix . '.update', ['tenant' => $tenantParam, 'invoice' => $invoice]) }}" method="POST"
            class="oh-card border border-border-default/70 shadow-card"
            @if ($isSent) onsubmit="return confirm('This invoice has been sent. Save changes?');" @endif>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 p-6">
                {{-- Left column --}}
                <div class="xl:col-span-2 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Client</span>
                            <select name="contact_id" class="oh-select h-10">
                                @foreach ($clients as $client)
                                    @php $name = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')); @endphp
                                    <option value="{{ $client->id }}"
                                        @selected(old('contact_id', $invoice->contact_id) == $client->id)>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Invoice number</span>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}"
                                class="oh-input h-10">
                            @error('invoice_number')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Issue date</span>
                            <input type="date" name="issue_date"
                                value="{{ old('issue_date', optional($invoice->issue_date)->toDateString()) }}"
                                class="oh-input h-10">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Due date</span>
                            <input type="date" name="due_date"
                                value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}"
                                class="oh-input h-10">
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Status</span>
                        <select name="status" class="oh-select h-10">
                            @php $st = $statusValue; @endphp
                            <option value="draft" @selected($st === 'draft')>Draft</option>
                            <option value="sent" @selected($st === 'sent')>Sent</option>
                            <option value="paid" @selected($st === 'paid')>Paid</option>
                            <option value="overdue" @selected($st === 'overdue')>Overdue</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </label>

                    {{-- Line items --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-base">Line items</h3>
                            <button type="button" class="oh-btn oh-btn--secondary" id="add-line-item">
                                <i class="fa-solid fa-plus text-[12px] mr-1"></i> Add line item
                            </button>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-border-default/70">
                            <table class="min-w-full text-sm" id="line-items-table">
                                <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">Item</th>
                                        <th class="px-3 py-2 text-right font-medium w-24">Qty</th>
                                        <th class="px-3 py-2 text-right font-medium w-32">Rate</th>
                                        <th class="px-3 py-2 text-right font-medium w-32">Total</th>
                                        <th class="px-3 py-2 text-right font-medium w-16"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items-body">
                                    @foreach ($lineItems as $idx => $item)
                                        <tr class="border-t border-border-default/60">
                                            @php $locked = in_array($item->source_type, ['time_entry','time_entry_group']); @endphp
                                            <td class="px-3 py-2 align-top">
                                                <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $idx }}][position]" value="{{ $idx }}" class="position-input">
                                                <input type="hidden" name="items[{{ $idx }}][source_type]" value="{{ $item->source_type }}">
                                                <input type="hidden" name="items[{{ $idx }}][source_id]" value="{{ $item->source_id }}">
                                                <input type="text" name="items[{{ $idx }}][name]" value="{{ old('items.' . $idx . '.name', $item->name) }}" placeholder="Item name"
                                                    class="oh-input h-9 w-full mb-1" @disabled($locked)>
                                                <textarea name="items[{{ $idx }}][description]" rows="2" placeholder="Description (optional)"
                                                    class="oh-input w-full text-xs" @disabled($locked)>{{ old('items.' . $idx . '.description', $item->description) }}</textarea>
                                                @if ($item->source_type === 'time_entry')
                                                    <span class="oh-pill oh-pill--info text-[11px] mt-1 inline-flex items-center gap-1">Time entry</span>
                                                @elseif ($item->source_type === 'time_entry_group')
                                                    <span class="oh-pill text-[11px] mt-1 inline-flex items-center gap-1">Time group</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][quantity]" value="{{ old('items.' . $idx . '.quantity', $item->quantity) }}"
                                                    class="oh-input h-9 w-full text-right item-qty" @disabled($locked)>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_price]" value="{{ old('items.' . $idx . '.unit_price', $item->unit_price) }}"
                                                    class="oh-input h-9 w-full text-right item-rate" @disabled($locked)>
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                <div class="item-total text-sm font-semibold text-text-base">—</div>
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                <button type="button" class="oh-icon-btn remove-line" @disabled($locked)>
                                                    <i class="fa-regular fa-trash-can text-[12px]"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="space-y-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Notes (optional)</span>
                        <textarea name="notes" rows="5" class="oh-input">{{ old('notes', $invoice->notes) }}</textarea>
                    </label>
                    <div class="rounded-lg border border-border-default/70 bg-surface-card/80 p-3 text-xs text-text-subtle space-y-1">
                        <div><strong>Draft:</strong> internal until sent.</div>
                        <div><strong>Sent:</strong> shared with client; edits may notify them.</div>
                        <div><strong>Paid:</strong> marked complete; balance closed.</div>
                        <div><strong>Overdue:</strong> due date passed; follow up.</div>
                    </div>

                    {{-- Totals --}}
                    <div class="rounded-xl border border-border-default/80 bg-surface-card/80 p-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Subtotal</span>
                            <span id="subtotal-display" class="font-semibold text-text-base">$0.00</span>
                        </div>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Tax rate (%)</span>
                            <input type="number" step="0.01" min="0" name="tax_rate" value="{{ old('tax_rate', $invoice->tax_rate) }}"
                                class="oh-input h-10 tax-rate">
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Discount type</span>
                                @php $dt = old('discount_type', $invoice->discount_type ?? 'none'); @endphp
                                <select name="discount_type" class="oh-select h-10 discount-type">
                                    <option value="none" @selected($dt === 'none')>None</option>
                                    <option value="percent" @selected($dt === 'percent')>Percent</option>
                                    <option value="fixed" @selected($dt === 'fixed')>Fixed</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Discount value</span>
                                <input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $invoice->discount_value) }}"
                                    class="oh-input h-10 discount-value">
                            </label>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Tax total</span>
                            <span id="tax-display" class="text-text-base">$0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span id="total-display">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap justify-end gap-3 px-6 py-4 border-t border-border-default/70 bg-surface-accent">
                <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantParam, 'invoice' => $invoice]) }}" class="oh-btn">
                    Cancel
                </a>
                @if ($statusValue === 'draft')
                    <button type="submit" class="oh-btn">Send invoice</button>
                @endif
                <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
            </div>
        </form>
    </div>

    <template id="line-item-template">
        <tr class="border-t border-border-default/60">
            <td class="px-3 py-2 align-top">
                <input type="hidden" name="__IDX__[id]" value="">
                <input type="hidden" name="__IDX__[position]" value="__POS__" class="position-input">
                <input type="text" name="__IDX__[name]" placeholder="Item name" class="oh-input h-9 w-full mb-1">
                <textarea name="__IDX__[description]" rows="2" placeholder="Description (optional)" class="oh-input w-full text-xs"></textarea>
            </td>
            <td class="px-3 py-2 align-top">
                <input type="number" step="0.01" min="0" name="__IDX__[quantity]" value="1" class="oh-input h-9 w-full text-right item-qty">
            </td>
            <td class="px-3 py-2 align-top">
                <input type="number" step="0.01" min="0" name="__IDX__[unit_price]" value="0" class="oh-input h-9 w-full text-right item-rate">
            </td>
            <td class="px-3 py-2 align-top text-right">
                <div class="item-total text-sm font-semibold text-text-base">—</div>
            </td>
            <td class="px-3 py-2 align-top text-right">
                <button type="button" class="oh-icon-btn remove-line">
                    <i class="fa-regular fa-trash-can text-[12px]"></i>
                </button>
            </td>
        </tr>
    </template>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const body = document.getElementById('line-items-body');
                const tmpl = document.getElementById('line-item-template').innerHTML;
                const addBtn = document.getElementById('add-line-item');
                const taxInput = document.querySelector('.tax-rate');
                const discountType = document.querySelector('.discount-type');
                const discountValue = document.querySelector('.discount-value');
                const subtotalEl = document.getElementById('subtotal-display');
                const taxEl = document.getElementById('tax-display');
                const totalEl = document.getElementById('total-display');

                let idx = body.querySelectorAll('tr').length;

                function format(n) {
                    return `$${Number(n || 0).toFixed(2)}`;
                }

                function renumber() {
                    body.querySelectorAll('tr').forEach((row, i) => {
                        row.querySelectorAll('input, textarea').forEach(input => {
                            input.name = input.name.replace(/items\\[[0-9]+\\]/, `items[${i}]`);
                            const pos = row.querySelector('.position-input');
                            if (pos) pos.value = i;
                        });
                    });
                }

                function recalc() {
                    let subtotal = 0;
                    body.querySelectorAll('tr').forEach(row => {
                        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
                        const rate = parseFloat(row.querySelector('.item-rate')?.value || 0);
                        const lineTotal = qty * rate;
                        subtotal += lineTotal;
                        const totalCell = row.querySelector('.item-total');
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
                    taxEl.textContent = format(taxTotal);
                    totalEl.textContent = format(total);
                }

                function addRow(data = {}) {
                    let rowHtml = tmpl.replace(/__IDX__/g, `items[${idx}]`).replace(/__POS__/g, idx);
                    const temp = document.createElement('tbody');
                    temp.innerHTML = rowHtml.trim();
                    const row = temp.firstChild;
                    // apply data
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
                }

                addBtn?.addEventListener('click', () => addRow());

                body.addEventListener('click', (e) => {
                    if (e.target.closest('.remove-line')) {
                        e.preventDefault();
                        const row = e.target.closest('tr');
                        row?.remove();
                        renumber();
                        recalc();
                    }
                });

                body.addEventListener('input', (e) => {
                    if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-rate') || e.target === taxInput || e.target === discountType || e.target === discountValue) {
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
@endsection
