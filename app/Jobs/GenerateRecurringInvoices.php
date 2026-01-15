<?php

namespace App\Jobs;

use App\Mail\InvoiceReminderMailable;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\RecurringInvoice;
use App\Services\InvoiceTotalsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class GenerateRecurringInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $due = RecurringInvoice::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        $totals = app(InvoiceTotalsService::class);

        foreach ($due as $recurring) {
            $invoice = Invoice::create([
                'tenant_id' => $recurring->tenant_id,
                'contact_id' => $recurring->contact_id,
                'project_id' => $recurring->project_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) $recurring->due_days)->toDateString(),
                'status' => $recurring->auto_send ? 'sent' : 'draft',
                'notes' => $recurring->notes,
            ]);

            $items = collect($recurring->line_items ?? []);
            foreach ($items as $idx => $item) {
                InvoiceLineItem::create([
                    'tenant_id' => $recurring->tenant_id,
                    'invoice_id' => $invoice->id,
                    'position' => (int) ($item['position'] ?? ($idx + 1)),
                    'description' => $item['description'] ?? 'Service',
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'line_total' => (float) ($item['line_total'] ?? 0),
                    'is_taxable' => (bool) ($item['is_taxable'] ?? true),
                ]);
            }

            $invoice->load(['lineItems', 'client']);
            $invoice = $totals->compute($invoice);
            $invoice->balance_due = $invoice->total ?? 0;
            if ($recurring->auto_send) {
                $invoice->sent_at = now();
            }
            $invoice->save();

            if ($recurring->auto_send && $invoice->client?->email) {
                Mail::to($invoice->client->email)->queue(
                    new InvoiceReminderMailable($invoice, $invoice->client, 'new')
                );
            }

            $recurring->last_run_at = now();
            $recurring->next_run_at = $this->nextRunAt(
                $recurring->frequency,
                (int) $recurring->interval,
                $recurring->day_of_week,
                $recurring->day_of_month
            );
            $recurring->save();
        }
    }

    private function nextRunAt(string $frequency, int $interval, ?int $dayOfWeek, ?int $dayOfMonth): Carbon
    {
        $now = now();

        return match ($frequency) {
            'weekly' => $now->copy()->startOfWeek()->addDays($dayOfWeek ?? 1)->addWeeks($interval),
            'yearly' => $now->copy()->startOfYear()->addYears($interval),
            default => $now->copy()->startOfMonth()->addMonths($interval)->day($dayOfMonth ?? 1),
        };
    }
}
