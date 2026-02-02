<?php

namespace App\Jobs;

use App\Models\OutboundEmail;
use App\Mail\ProposalSentMailable;
use App\Mail\InvoiceSentMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTrackedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $outboundEmailId)
    {
    }

    public function handle(): void
    {
        $outbound = OutboundEmail::find($this->outboundEmailId);
        if (!$outbound) {
            return;
        }

        if ($outbound->status === 'sent') {
            return;
        }

        try {
            $mailable = $this->resolveMailable($outbound);
            if (!$mailable) {
                $outbound->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => 'Unsupported email type.',
                ]);
                return;
            }

            Mail::to($outbound->to_email, $outbound->to_name)->send($mailable);

            $outbound->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $outbound->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => mb_strimwidth($e->getMessage(), 0, 2000),
            ]);

            throw $e;
        }
    }

    private function resolveMailable(OutboundEmail $outbound): ?object
    {
        $meta = $outbound->meta ?? [];
        $type = $outbound->type;

        if ($type === 'proposal_sent' && !empty($meta['proposal_id'])) {
            $proposal = \App\Models\Proposal::find($meta['proposal_id']);
            return $proposal ? new ProposalSentMailable($proposal) : null;
        }

        if ($type === 'invoice_sent' && !empty($meta['invoice_id'])) {
            $invoice = \App\Models\Invoice::find($meta['invoice_id']);
            return $invoice ? new InvoiceSentMailable($invoice) : null;
        }

        return null;
    }
}
