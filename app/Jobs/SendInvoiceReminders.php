<?php

namespace App\Jobs;

use App\Mail\InvoiceReminderMailable;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->startOfDay();
        $dates = [
            $today->copy()->addDays(3)->toDateString(),
            $today->toDateString(),
            $today->copy()->subDays(7)->toDateString(),
        ];

        $invoices = Invoice::query()
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->whereNotNull('due_date')
            ->whereIn('due_date', $dates)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_reminder_at')
                  ->orWhereDate('last_reminder_at', '<', $today->toDateString());
            })
            ->with('client')
            ->get();

        foreach ($invoices as $invoice) {
            $client = $invoice->client;
            if (!$client || empty($client->email)) {
                continue;
            }

            Mail::to($client->email)->queue(
                new InvoiceReminderMailable($invoice, $client, 'reminder')
            );

            $invoice->last_reminder_at = now();
            $invoice->save();
        }
    }
}
