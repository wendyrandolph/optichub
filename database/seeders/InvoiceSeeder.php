<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Tenant;
use App\Services\InvoiceTotalsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            return;
        }

        $client = Client::where('tenant_id', $tenant->id)->first();
        if (! $client) {
            $client = Client::create([
                'tenant_id' => $tenant->id,
                'firstName' => 'Seed',
                'lastName' => 'Client',
                'email' => 'seed.client@example.com',
            ]);
        }

        $service = app(InvoiceTotalsService::class);
        $today = Carbon::today();

        // Create 6 invoices with varying statuses
        $configs = [
            ['num' => 'INV-2001', 'status' => 'draft', 'issue' => $today->copy()->subDays(2), 'due' => $today->copy()->addDays(14)],
            ['num' => 'INV-2002', 'status' => 'sent', 'issue' => $today->copy()->subDays(7), 'due' => $today->copy()->addDays(7)],
            ['num' => 'INV-2003', 'status' => 'paid', 'issue' => $today->copy()->subDays(15), 'due' => $today->copy()->subDays(2)],
            ['num' => 'INV-2004', 'status' => 'overdue', 'issue' => $today->copy()->subDays(20), 'due' => $today->copy()->subDays(1)],
            ['num' => 'INV-2005', 'status' => 'sent', 'issue' => $today->copy()->subDays(3), 'due' => $today->copy()->addDays(10)],
            ['num' => 'INV-2006', 'status' => 'draft', 'issue' => $today->copy()->subDays(1), 'due' => $today->copy()->addDays(30)],
        ];

        DB::transaction(function () use ($configs, $tenant, $client, $service) {
            foreach ($configs as $cfg) {
                // make invoice_number idempotent across reruns
                $baseNumber = $cfg['num'];
                $num = $baseNumber;
                $suffix = 1;
                while (Invoice::where('tenant_id', $tenant->id)->where('invoice_number', $num)->exists()) {
                    $num = $baseNumber . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
                    $suffix++;
                }

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'contact_id' => $client->id,
                    'invoice_number' => $num,
                    'issue_date' => $cfg['issue'],
                    'due_date' => $cfg['due'],
                    'status' => $cfg['status'],
                    'notes' => 'Seeded invoice ' . $num,
                    'tax_rate' => rand(0, 1) ? 8.5 : null,
                    'discount_type' => 'none',
                    'discount_value' => 0,
                ]);

                $itemCount = rand(2, 6);
                for ($i = 0; $i < $itemCount; $i++) {
                    $qty = rand(1, 3);
                    $rate = rand(80, 200);
                    InvoiceLineItem::create([
                        'tenant_id' => $tenant->id,
                        'invoice_id' => $invoice->id,
                        'position' => $i,
                        'name' => 'Service ' . ($i + 1),
                        'description' => 'Seeded line item ' . ($i + 1),
                        'quantity' => $qty,
                        'unit_price' => $rate,
                        'line_total' => $qty * $rate,
                        'is_taxable' => true,
                    ]);
                }

                $invoice->load('lineItems');
                $service->compute($invoice);
                $invoice->total_amount = $invoice->total;
                $invoice->balance_due = $invoice->status === 'paid' ? 0 : $invoice->total;
                $invoice->save();
            }
        });
    }
}
