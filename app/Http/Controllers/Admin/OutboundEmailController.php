<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTrackedEmail;
use App\Models\OutboundEmail;
use Illuminate\Http\Request;

class OutboundEmailController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && (method_exists($admin, 'isProviderAdmin') && $admin->isProviderAdmin()), 403);

        $query = OutboundEmail::query()->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($tenantId = $request->input('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->input('from'), $request->input('to')]);
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.outbound-emails-index', [
            'logs' => $logs,
        ]);
    }

    public function retry(OutboundEmail $outboundEmail)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && (method_exists($admin, 'isProviderAdmin') && $admin->isProviderAdmin()), 403);

        if ($outboundEmail->status !== 'failed') {
            return back()->with('error', 'Only failed emails can be retried.');
        }

        $outboundEmail->update([
            'status' => 'queued',
            'error' => null,
        ]);

        $mailableData = [];
        if ($outboundEmail->related_type && $outboundEmail->related_id) {
            $model = $outboundEmail->related_type::find($outboundEmail->related_id);
            if ($model) {
                $mailableData = [$model];
            }
        }

        if ($outboundEmail->mailable_type === \App\Mail\SupportTicketReplyMail::class) {
            $ticket = $outboundEmail->related_type
                ? $outboundEmail->related_type::find($outboundEmail->related_id)
                : null;
            if ($ticket) {
                $latestReply = $ticket->messages()
                    ->where('sender_type', 'provider')
                    ->where('is_internal', false)
                    ->latest()
                    ->value('body') ?? '';
                $mailableData = [$ticket, $latestReply];
            }
        }

        if ($outboundEmail->mailable_type === \App\Mail\SupportTicketCreatedMail::class) {
            $ticket = $outboundEmail->related_type
                ? $outboundEmail->related_type::find($outboundEmail->related_id)
                : null;
            if ($ticket) {
                $mailableData = [$ticket, ''];
            }
        }

        SendTrackedEmail::dispatch(
            $outboundEmail->id,
            $outboundEmail->mailable_type,
            $mailableData,
            $outboundEmail->to_email
        );

        return back()->with('success', 'Email re-queued.');
    }
}
