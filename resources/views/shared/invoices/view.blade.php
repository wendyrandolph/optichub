@php
    $status = strtolower($invoice->status ?? 'draft');
    $pill = match ($status) {
        'paid' => 'oh-pill oh-pill--success',
        'overdue' => 'oh-pill oh-pill--danger',
        'sent' => 'oh-pill oh-pill--info',
        default => 'oh-pill',
    };
    $clientName = $invoice->client ? trim(($invoice->client->firstName ?? '') . ' ' . ($invoice->client->lastName ?? '')) : '—';
    $dueDate = $invoice->due_date ?? null;
    $issuedDate = $invoice->issue_date ?? null;
    $sentAt = $invoice->sent_at ?? null;
    $createdAt = $invoice->created_at ?? null;
    $updatedAt = $invoice->updated_at ?? null;
    $balanceDue = $invoice->balance_due ?? $invoice->total ?? $invoice->total_amount ?? 0;
    $daysOverdue = null;
    if ($status === 'overdue' && $dueDate) {
        $daysOverdue = \Illuminate\Support\Carbon::parse($dueDate)->diffInDays(now());
    }
    $lastPayment = $invoice->payments?->sortByDesc('created_at')->first();
    $clientEmail = $invoice->client->email ?? null;
    $clientPhone = $invoice->client->phone_formatted ?? $invoice->client->phone ?? null;
    $mockPaymentsEnabled = (bool) config('provider.enable_mock_payments');
    $actor = auth('admin')->user() ?? auth()->user();
    $isProviderAdmin = $actor?->isProviderAdmin() ?? false;
@endphp
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route($routePrefix . '.index', ['tenant' => request()->route('tenant')]) }}"
                class="oh-link-underline inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left text-[10px] mr-2"></i> Back to invoices
            </a>
            <div class="flex flex-wrap items-center gap-2">
                @if ($status === 'draft')
                    @if (Route::has($routePrefix . '.send'))
                        <form action="{{ route($routePrefix . '.send', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            method="POST" onsubmit="return confirm('Send this invoice?');">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--primary">Send invoice</button>
                        </form>
                    @else
                        <button type="button" class="oh-btn oh-btn--primary opacity-70 cursor-not-allowed">Send invoice</button>
                    @endif
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                        class="oh-btn oh-btn--secondary">Edit</a>
                    @if (Route::has($routePrefix . '.pdf'))
                        <a href="{{ route($routePrefix . '.pdf', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            class="oh-btn" target="_blank">Download PDF</a>
                    @endif
                    <button type="button" class="oh-btn" onclick="window.print()">Print</button>
                @elseif (in_array($status, ['sent', 'overdue'], true))
                    @if (Route::has($routePrefix . '.payments.create'))
                        <a href="{{ route($routePrefix . '.payments.create', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            class="oh-btn oh-btn--primary">Record payment</a>
                    @else
                        <button type="button" class="oh-btn oh-btn--primary opacity-70 cursor-not-allowed">Record payment</button>
                    @endif
                    <button type="button" class="oh-btn oh-btn--secondary opacity-70 cursor-not-allowed">Send reminder</button>
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                        class="oh-btn">Edit</a>
                    @if (Route::has($routePrefix . '.pdf'))
                        <a href="{{ route($routePrefix . '.pdf', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            class="oh-btn" target="_blank">Download PDF</a>
                    @endif
                    <button type="button" class="oh-btn" onclick="window.print()">Print</button>
                @else
                    @if (Route::has($routePrefix . '.pdf'))
                        <a href="{{ route($routePrefix . '.pdf', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            class="oh-btn oh-btn--primary" target="_blank">Download PDF</a>
                    @endif
                    <button type="button" class="oh-btn oh-btn--secondary" onclick="window.print()">Print</button>
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                        class="oh-btn">Edit</a>
                @endif
                @if ($isProviderAdmin && $mockPaymentsEnabled && Route::has('tenant.payments.mock.simulate'))
                    <form action="{{ route('tenant.payments.mock.simulate', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                        method="POST" onsubmit="return confirm('Simulate a paid invoice?');">
                        @csrf
                        <button type="submit" class="oh-btn">Simulate paid</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Invoice</p>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold text-text-base">
                        Invoice #{{ $invoice->invoice_number ?? $invoice->id }}
                    </h1>
                    <span class="{{ $pill }} text-[11px] px-3 py-1">{{ ucfirst($status) }}</span>
                </div>
                <p class="text-sm text-text-subtle">Client: {{ $clientName }}</p>
            </div>
        </div>
    </div>

    <div class="oh-card p-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <div class="text-[11px] uppercase tracking-wider text-text-subtle">Balance due</div>
            <div class="text-2xl font-semibold text-text-base">{{ money($balanceDue) }}</div>
            <div class="text-xs text-text-subtle">
                Due {{ $dueDate?->format('M j, Y') ?? '—' }}
            </div>
            @if ($daysOverdue)
                <div class="text-xs text-text-subtle">{{ $daysOverdue }} days overdue</div>
            @endif
        </div>
        <div class="space-y-1 text-sm text-text-subtle">
            <div>Sent {{ $sentAt?->format('M j, Y') ?? '—' }}</div>
            @if ($lastPayment)
                <div>Last payment {{ $lastPayment->created_at?->format('M j, Y') }} • {{ money($lastPayment->amount ?? 0) }}</div>
            @else
                <div>No payments recorded</div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(260px,1fr)]">
        <div class="space-y-4">
            <div class="oh-card p-6 space-y-4">
                <h3 class="text-sm font-semibold text-text-base">Line items</h3>
                <div class="overflow-hidden rounded-xl border border-border-default/70">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Item</th>
                                <th class="px-4 py-2 text-right font-medium w-20">Qty</th>
                                <th class="px-4 py-2 text-right font-medium w-28">Rate</th>
                                <th class="px-4 py-2 text-right font-medium w-28">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($invoice->lineItems as $item)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-semibold text-text-base">{{ $item->name }}</div>
                                        @if ($item->description)
                                            <div class="text-xs text-text-subtle">{{ $item->description }}</div>
                                        @endif
                                        @if ($item->source_type === 'time_entry')
                                            <span class="oh-pill oh-pill--info text-[11px] mt-1 inline-flex items-center gap-1">Time entry</span>
                                        @elseif ($item->source_type === 'time_entry_group')
                                            <span class="oh-pill text-[11px] mt-1 inline-flex items-center gap-1">Time group</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ $item->quantity ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ money($item->unit_price ?? 0) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-text-base">{{ money($item->line_total ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-text-subtle">
                                        No line items yet. Add items to finalize this invoice.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="oh-card p-5 space-y-2 text-sm">
                <div class="text-sm font-semibold text-text-base">Notes</div>
                @if ($invoice->notes)
                    <p class="text-text-subtle">{{ $invoice->notes }}</p>
                @else
                    <p class="text-text-subtle">No notes added yet.</p>
                @endif
            </div>

            <div class="oh-card p-5 text-sm space-y-2">
                <div class="text-sm font-semibold text-text-base">Activity</div>
                <div class="text-text-subtle">Created {{ $createdAt?->format('M j, Y') ?? '—' }}</div>
                <div class="text-text-subtle">Sent {{ $sentAt?->format('M j, Y') ?? '—' }}</div>
                <div class="text-text-subtle">Updated {{ $updatedAt?->format('M j, Y') ?? '—' }}</div>
            </div>
        </div>

            <div class="space-y-4">
                <div class="oh-card p-5 space-y-3 text-sm">
                    <div class="text-sm font-semibold text-text-base">Summary</div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Invoice #</span>
                        <span class="text-text-base font-semibold">{{ $invoice->invoice_number ?? $invoice->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Issued</span>
                        <span class="text-text-base">{{ $issuedDate?->format('M j, Y') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Due</span>
                        <span class="text-text-base">{{ $dueDate?->format('M j, Y') ?? '—' }}</span>
                    </div>
                    @if ($clientEmail || $clientPhone)
                        <div class="pt-2 border-t border-border-default/70 space-y-1">
                            <div class="text-text-subtle">Client contact</div>
                            @if ($clientEmail)
                                <div class="text-text-base">{{ $clientEmail }}</div>
                            @endif
                            @if ($clientPhone)
                                <div class="text-text-base">{{ $clientPhone }}</div>
                            @endif
                        </div>
                    @endif
                    <div class="pt-2 border-t border-border-default/70 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Subtotal</span>
                            <span class="text-text-base font-semibold">{{ money($invoice->subtotal ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Tax ({{ $invoice->tax_rate ?? 0 }}%)</span>
                            <span class="text-text-base">{{ money($invoice->tax_total ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Discount</span>
                            <span class="text-text-base">
                                {{ $invoice->discount_type === 'percent' ? ($invoice->discount_value ?? 0) . '%' : money($invoice->discount_value ?? 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{{ money($invoice->total ?? $invoice->total_amount ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Balance due</span>
                            <span class="text-text-base font-semibold">{{ money($balanceDue) }}</span>
                        </div>
                    </div>
                </div>

                @if ($invoice->stripe_link)
                    <div class="oh-card p-4 bg-surface-accent text-xs text-text-base space-y-2">
                        <p class="font-semibold text-text-base">Payment link</p>
                        <p class="text-text-subtle">You can send this link directly to the client if needed.</p>
                        <a href="{{ $invoice->stripe_link }}" target="_blank" class="oh-btn oh-btn--primary oh-btn--sm">
                            Open Stripe payment page
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
