{{-- resources/views/invoices/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
    @php
        // Prefer a passed $tenant model; otherwise fall back to current user's tenant_id
$tenantId = request()->route('tenant') ?? auth()->user()->tenant_id;
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page header --}}
        <div class="mb-6">
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Billing</p>
            <h1 class="text-2xl font-semibold text-text-base">Create Invoice</h1>
            <p class="text-sm text-text-subtle">Issue a new invoice and add line items below.</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-300/60 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.invoices.store', ['tenant' => $tenantId]) }}"
            class="rounded-2xl border border-border-default bg-surface-card shadow-card overflow-hidden">
            @csrf

            {{-- Section: Basics --}}
            <div class="px-6 py-5 border-b border-border-default/70 bg-surface-accent">
                <h2 class="text-base font-semibold text-text-base">Invoice details</h2>
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Client --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Client <span class="text-rose-500">*</span></span>
                        <select id="client_id" name="client_id" required
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                            <option value="">Choose a client…</option>
                            @foreach ($clients as $client)
                                @php
                                    $clientName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''));
                                @endphp
                                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                    {{ $clientName !== '' ? $clientName : "Client #{$client->id}" }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    {{-- Invoice Number --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Invoice Number <span class="text-rose-500">*</span></span>
                        <input id="invoice_number" type="text" name="invoice_number" required
                            value="{{ old('invoice_number') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                        @error('invoice_number')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    {{-- Issue Date --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Issue Date <span class="text-rose-500">*</span></span>
                        <input id="issue_date" type="date" name="issue_date" required value="{{ old('issue_date') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                        @error('issue_date')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    {{-- Due Date --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Due Date <span class="text-rose-500">*</span></span>
                        <input id="due_date" type="date" name="due_date" required value="{{ old('due_date') }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                        @error('due_date')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    {{-- Status --}}
                    <label class="grid gap-1 text-sm md:col-span-2">
                        <span class="text-text-subtle">Status</span>
                        <select id="status" name="status"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                            @foreach (['Draft', 'Sent', 'Paid'] as $status)
                                <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    {{-- Notes --}}
                    <label class="grid gap-1 text-sm md:col-span-2">
                        <span class="text-text-subtle">Notes</span>
                        <textarea id="notes" name="notes" rows="4"
                            class="rounded-lg bg-surface-card text-text-base border border-border-default px-3 py-2">{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>
                </div>
            </div>

            {{-- Section: Line items --}}
            <div class="px-6 py-5 border-t border-border-default/70 bg-surface-accent">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-text-base">Line items</h2>
                    <button type="button" id="add-item-btn"
                        class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium text-white
                       bg-gradient-to-b from-brand-primary to-blue-700 shadow-card hover:brightness-110">
                        <i class="fa-solid fa-plus mr-2"></i> Add item
                    </button>
                </div>
            </div>

            <div class="px-6 pt-4 pb-2">
                <div class="hidden md:grid grid-cols-12 gap-3 text-xs text-text-subtle px-2 mb-2">
                    <div class="col-span-6">Description</div>
                    <div class="col-span-2 text-right">Qty</div>
                    <div class="col-span-2 text-right">Unit Price</div>
                    <div class="col-span-2 text-right">Line Total</div>
                </div>

                @php
                    $items = old('items', [['description' => '', 'quantity' => 1, 'unit_price' => '']]);
                @endphp

                <div id="line-items" data-next-index="{{ max(1, count($items)) }}" class="space-y-2">
                    @foreach ($items as $i => $item)
                        <div class="item-row grid grid-cols-12 gap-3 items-center rounded-lg border border-border-default bg-surface-card px-3 py-3"
                            data-row="{{ $i }}">
                            <input type="text" name="items[{{ $i }}][description]" placeholder="Description"
                                value="{{ $item['description'] ?? '' }}"
                                class="col-span-12 md:col-span-6 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3"
                                required>

                            <input type="number" name="items[{{ $i }}][quantity]" placeholder="Qty"
                                min="1" value="{{ $item['quantity'] ?? 1 }}"
                                class="col-span-6 md:col-span-2 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3 text-right"
                                required>

                            <input type="number" step="0.01" name="items[{{ $i }}][unit_price]"
                                placeholder="Unit Price" value="{{ $item['unit_price'] ?? '' }}"
                                class="col-span-6 md:col-span-2 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3 text-right"
                                required>

                            <div
                                class="col-span-8 md:col-span-1 text-right text-sm tabular-nums text-text-subtle md:col-start-11">
                                <span class="js-line-total">—</span>
                            </div>

                            <button type="button"
                                class="col-span-4 md:col-span-1 justify-self-end md:justify-self-auto
                           inline-flex items-center justify-center h-9 w-9 rounded-md border border-border-default bg-surface-accent text-text-base js-remove-item"
                                title="Remove" aria-label="Remove line item">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="mt-4 flex items-center justify-end">
                    <div class="w-full max-w-sm rounded-xl border border-border-default bg-surface-card px-4 py-3">
                        <dl class="grid grid-cols-2 gap-y-1 text-sm">
                            <dt class="text-text-subtle">Subtotal</dt>
                            <dd class="text-right tabular-nums" id="subtotal">$0.00</dd>

                            {{-- If you add tax later, drop more rows here --}}

                            <dt class="font-semibold text-text-base mt-1">Total</dt>
                            <dd class="text-right font-semibold tabular-nums mt-1" id="grandTotal">$0.00</dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div
                class="px-6 py-4 border-t border-border-default/70 bg-surface-accent flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('tenant.invoices.index', ['tenant' => $tenantId]) }}"
                    class="inline-flex items-center h-10 px-4 rounded-lg text-sm font-medium border border-border-default bg-surface-card text-text-base hover:brightness-110">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center h-10 px-4 rounded-lg text-sm font-medium text-white
                     bg-gradient-to-b from-brand-primary to-blue-700 shadow-card hover:brightness-110">
                    Create Invoice
                </button>
            </div>
        </form>
    </div>

    {{-- Line item template --}}
    <template id="line-item-template">
        <div class="item-row grid grid-cols-12 gap-3 items-center rounded-lg border border-border-default bg-surface-card px-3 py-3"
            data-row="__INDEX__">
            <input type="text" name="items[__INDEX__][description]" placeholder="Description"
                class="col-span-12 md:col-span-6 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3"
                required>

            <input type="number" name="items[__INDEX__][quantity]" placeholder="Qty" min="1" value="1"
                class="col-span-6 md:col-span-2 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3 text-right"
                required>

            <input type="number" step="0.01" name="items[__INDEX__][unit_price]" placeholder="Unit Price"
                class="col-span-6 md:col-span-2 h-10 rounded-md bg-surface-card text-text-base border border-border-default px-3 text-right"
                required>

            <div class="col-span-8 md:col-span-1 text-right text-sm tabular-nums text-text-subtle md:col-start-11">
                <span class="js-line-total">—</span>
            </div>

            <button type="button"
                class="col-span-4 md:col-span-1 justify-self-end md:justify-self-auto inline-flex items-center justify-center h-9 w-9 rounded-md border border-border-default bg-surface-accent text-text-base js-remove-item"
                title="Remove" aria-label="Remove line item">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </template>

    @push('scripts')
        <script>
            function money(n) {
                return '$' + (Number(n || 0).toFixed(2));
            }

            function recalcRow(row) {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value || 0);
                const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value || 0);
                const total = qty * price;
                row.querySelector('.js-line-total').textContent = total ? money(total) : '—';
                return total;
            }

            function recalcAll() {
                let sum = 0;
                document.querySelectorAll('#line-items .item-row').forEach(r => sum += recalcRow(r));
                document.getElementById('subtotal').textContent = money(sum);
                document.getElementById('grandTotal').textContent = money(sum);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const container = document.getElementById('line-items');
                const tmpl = document.getElementById('line-item-template');
                const addBtn = document.getElementById('add-item-btn');

                // Initial calc (for old() values)
                recalcAll();

                // Add item
                addBtn?.addEventListener('click', () => {
                    const next = parseInt(container.dataset.nextIndex || '1', 10);
                    const html = tmpl.innerHTML.replaceAll('__INDEX__', String(next));
                    container.insertAdjacentHTML('beforeend', html);
                    container.dataset.nextIndex = String(next + 1);
                    recalcAll();
                });

                // Remove + live calc
                container?.addEventListener('click', e => {
                    const btn = e.target.closest('.js-remove-item');
                    if (btn) {
                        const row = btn.closest('.item-row');
                        row?.remove();
                        recalcAll();
                    }
                });

                container?.addEventListener('input', e => {
                    const row = e.target.closest('.item-row');
                    if (row) recalcRow(row);
                    recalcAll();
                });
            });
        </script>
    @endpush
@endsection
