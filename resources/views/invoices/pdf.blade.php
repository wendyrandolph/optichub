@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Collection;

    // Tenant-driven colors with Renlo fallbacks
    $primary = $tenant->primary_color ?: '#1F3C66'; // deep blue
    $accent = $tenant->secondary_color ?: '#2563EB'; // mid blue

    $logoPath = $tenant->logo_path ? public_path('storage/' . $tenant->logo_path) : null;

    // ----- CLIENT / BILL TO -----
    $client = $invoice->client;

    $clientName = $client
        ? trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''))
        : $invoice->client_name ?? '—';

    $clientEmail = $client->email ?? null;
    $clientPhone = $client->phone ?? null;

    // ----- DATES & STATUS -----
    $issueDate = $invoice->issue_date ? Carbon::parse($invoice->issue_date)->format('F j, Y') : '—';
    $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->format('F j, Y') : '—';

    $status = strtolower($invoice->status ?? 'draft');

    $statusBg =
        [
            'paid' => '#dcfce7',
            'overdue' => '#fee2e2',
            'sent' => '#dbeafe',
            'draft' => '#e5e7eb',
        ][$status] ?? '#e5e7eb';

    $statusColor =
        [
            'paid' => '#166534',
            'overdue' => '#b91c1c',
            'sent' => '#1d4ed8',
            'draft' => '#374151',
        ][$status] ?? '#374151';

    // Money helper
    $money = fn($v) => '$' . number_format((float) $v, 2);

    // ----- ITEMS -----
    // Prefer the Eloquent relationship: $invoice->items or $invoice->lineItems
    $itemsCollection = $invoice->items ?? ($invoice->lineItems ?? collect());

    if (!$itemsCollection instanceof Collection) {
        $itemsCollection = collect($itemsCollection ?? []);
    }

    // Compute subtotal from items if needed
    $computedTotal = $itemsCollection->reduce(function ($carry, $item) {
        $qty = $item->quantity ?? 0;
        $price = $item->unit_price ?? 0;
        return $carry + $qty * $price;
    }, 0.0);

    $subtotal = $invoice->subtotal_resolved ?? $computedTotal;
    $taxTotal = $invoice->tax_total ?? 0;
    $invoiceTotal = $invoice->total_amount ?? $subtotal + $taxTotal;

    // Tax breakdown array (always safe)
    $taxBreakdown = $invoice->tax_breakdown_resolved ?? [];

    // “Jazzy” customer id starting at 1001
    $rawClientId = $client->id ?? ($invoice->contact_id ?? null);
    $customerId = $rawClientId ? 'CUST-' . str_pad(1000 + (int) $rawClientId, 4, '0', STR_PAD_LEFT) : '—';

    $taglineDefault = 'Thoughtful tools that remove tech stress and help service businesses move forward with clarity.';
    $invoiceFooterDefault =
        'Thank you for your business. For questions, please contact ' .
        ($tenant->support_email ?: 'your account manager') .
        '.';

    $brandTagline = $tenant->brand_tagline ?: $taglineDefault;
    $invoiceFooter = $tenant->invoice_footer ?: $invoiceFooterDefault;
@endphp


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 0;
            font-family: Montserrat, sans-serif;
            font-size: 12px;
            color: #111827;
            background: #ffffff;
        }

        .page {
            width: 90%;
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 40px;
            letter-spacing: 1.1;
        }

        h1,
        h2,
        h3,
        h4 {
            margin: 0;
            padding: 0;
        }

        p {
            margin: 0 0 4px 0;
        }

        .header {
            margin-bottom: 16px;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-left {
            width: 60%;
        }

        .header-right {
            text-align: right;
        }

        .logo {
            max-height: 48px;
            margin-bottom: 6px;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .brand-tagline {
            font-size: 11px;
            color: #6b7280;
        }

        .invoice-word {
            font-size: 20px;
            font-weight: 700;
            color: {{ $primary }};
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .invoice-meta {
            margin-top: 8px;
            font-size: 11px;
            color: #4b5563;
        }

        .meta-row {
            margin-bottom: 3px;
        }

        .meta-label {
            font-weight: 600;
        }

        .status-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            background: {{ $statusBg }};
            color: {{ $statusColor }};
        }

        .divider-bar {
            margin-top: 10px;
            height: 3px;
            background: {{ $accent }};
        }

        .from-to-row {
            display: table;
            width: 100%;
            margin-top: 14px;
            margin-bottom: 18px;
            font-size: 11px;
        }

        .block-half {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 2px;
        }

        /* Line items table */

        .section-title {
            margin: 6px 0 4px 0;
            font-size: 12px;
            font-weight: 600;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: {{ $primary }};
            color: #ffffff;
            font-size: 11px;
            padding: 7px 6px;
            text-align: left;
        }

        td {
            font-size: 11px;
            padding: 7px 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .totals-wrapper {
            margin-top: 10px;
            width: 260px;
            margin-left: auto;
        }

        .totals-box {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 11px;
        }

        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .totals-label,
        .totals-value {
            display: table-cell;
        }

        .totals-label {
            color: #4b5563;
        }

        .totals-value {
            text-align: right;
        }

        .totals-strong .totals-label,
        .totals-strong .totals-value {
            font-weight: 700;
        }

        .notes {
            margin-top: 14px;
            font-size: 11px;
        }

        .notes-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }

        .footer-strong {
            color: {{ $primary }};
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- HEADER --}}
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    @if ($logoPath && file_exists($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="{{ $tenant->name }} logo">
                    @else
                        <div class="brand-name">{{ $tenant->name }}</div>
                    @endif

                    <div class="brand-tagline">{{ $brandTagline }}</div>
                </div>

                <div class="header-right">
                    <div class="invoice-word">Invoice</div>

                    <div class="invoice-meta">
                        <div class="meta-row">
                            <span class="meta-label">Invoice #:</span>
                            {{ $invoice->invoice_number }}
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Customer ID:</span>
                            {{ $customerId }}
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Issued:</span>
                            {{ $issueDate }}
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Payment Due By:</span>
                            {{ $dueDate }}
                        </div>
                    </div>

                    <span class="status-pill">{{ ucfirst($status) }}</span>
                </div>
            </div>

            <div class="divider-bar"></div>

            <div class="from-to-row">
                <div class="block-half">
                    <div class="label">From</div>
                    <div>{{ $tenant->name }}</div>
                    @if ($tenant->website)
                        <div>{{ $tenant->website }}</div>
                    @endif
                    @if ($tenant->support_email)
                        <div>{{ $tenant->support_email }}</div>
                    @endif
                    @if ($tenant->phone)
                        <div>{{ $tenant->phone }}</div>
                    @endif
                </div>

                <div class="block-half">
                    <div class="label">Bill to</div>
                    <div>{{ $clientName }}</div>
                    @if (optional($invoice->client)->email)
                        <div>{{ $invoice->client->email }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- LINE ITEMS --}}
        <div class="section-title">Line items</div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit price</th>
                    <th class="text-right">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($itemsCollection as $item)
                    @php
                        $desc = $item->description ?? '';
                        $qty = $item->quantity ?? 0;
                        $unit = $item->unit_price ?? 0;
                        $total = $qty * $unit;
                    @endphp
                    <tr>
                        <td>{{ $desc }}</td>
                        <td class="text-right">{{ $qty }}</td>
                        <td class="text-right">{{ $money($unit) }}</td>
                        <td class="text-right">{{ $money($total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No line items.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>

        {{-- TOTALS --}}
        <div class="totals-wrapper">
            <div class="totals-box">
                <div class="totals-row">
                    <div class="totals-label">Subtotal</div>
                    <div class="totals-value">{{ $money($subtotal) }}</div>
                </div>

                @foreach ($taxBreakdown as $tax)
                    <div class="totals-row">
                        <div class="totals-label">{{ $tax['label'] ?? 'Tax' }}</div>
                        <div class="totals-value">{{ $money($tax['amount'] ?? 0) }}</div>
                    </div>
                @endforeach

                @if ($taxTotal > 0 && empty($taxBreakdown))
                    <div class="totals-row">
                        <div class="totals-label">Tax</div>
                        <div class="totals-value">{{ $money($taxTotal) }}</div>
                    </div>
                @endif

                <div class="totals-row totals-strong">
                    <div class="totals-label">Total</div>
                    <div class="totals-value">{{ $money($invoiceTotal) }}</div>
                </div>
            </div>
        </div>

        {{-- NOTES --}}
        @if (!empty($invoice->notes))
            <div class="notes">
                <div class="notes-label">Special notes and instructions</div>
                <div>{{ $invoice->notes }}</div>
            </div>
        @endif

        {{-- FOOTER --}}
        <div class="footer">
            <div class="footer-strong">{{ $tenant->name }}</div>
            <div>{!! nl2br(e($invoiceFooter)) !!}</div>
            @if ($tenant->support_email)
                <div>For questions, contact {{ $tenant->support_email }}.</div>
            @endif
        </div>
    </div>
</body>

</html>
