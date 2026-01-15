<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceTotalsService
{
    public function compute(Invoice $invoice): Invoice
    {
        $items = $invoice->lineItems ?? collect();
        $subtotal = $items->sum(function ($item) {
            $qty = (float) $item->quantity;
            $rate = (float) $item->unit_price;
            $lineTotal = round($qty * $rate, 2);
            $item->line_total = $lineTotal;
            return $lineTotal;
        });

        $taxRate = $invoice->tax_rate ?? null;
        $taxTotal = $taxRate ? round($subtotal * ((float) $taxRate / 100), 2) : 0;

        $discount = 0;
        $discountType = $invoice->discount_type ?? 'none';
        $discountValue = (float) ($invoice->discount_value ?? 0);
        $base = $subtotal + $taxTotal;
        if ($discountType === 'percent') {
            $discount = round($base * ($discountValue / 100), 2);
        } elseif ($discountType === 'fixed') {
            $discount = $discountValue;
        }

        $total = max(0, round($base - $discount, 2));

        $invoice->subtotal = $subtotal;
        $invoice->tax_total = $taxTotal;
        $invoice->total = $total;

        return $invoice;
    }
}
