<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);

        $tickets = SupportTicket::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('tenant.support-tickets-index', [
            'tenant' => $tenant,
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);

        return view('tenant.support-tickets-create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);

        $data = $request->validate([
            'category' => ['required', 'in:bug,question,feature'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
        ]);

        return DB::transaction(function () use ($tenant, $user, $data, $request) {
            $ticket = SupportTicket::create([
                'tenant_id' => $tenant->id,
                'workspace_type' => $tenant->workspace_type,
                'created_by_user_id' => $user->id,
                'category' => $data['category'],
                'status' => 'received',
                'subject' => $data['subject'],
                'body' => $data['body'],
            ]);

            $message = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_type' => 'tenant',
                'sender_user_id' => $user->id,
                'user_id' => $user->id,
                'body' => $data['body'],
                'is_internal' => false,
            ]);

            $files = $request->file('attachments', []);
            foreach ($files as $file) {
                $path = $file->store('support_attachments', 'public');
                SupportAttachment::create([
                    'support_ticket_id' => $ticket->id,
                    'support_ticket_message_id' => $message->id,
                    'file_path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            return redirect()
                ->route('tenant.support.tickets.show', ['tenant' => $tenant->id, 'ticket' => $ticket->id])
                ->with('success', 'Ticket submitted.');
        });
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $ticket->tenant_id === (int) $tenant->id, 404);

        $ticket->load(['messages' => function ($query) {
            $query->where('is_internal', false)->orderBy('created_at');
        }, 'messages.author', 'attachments']);

        return view('tenant.support-tickets-show', [
            'tenant' => $tenant,
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $ticket->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
        ]);

        return DB::transaction(function () use ($ticket, $tenant, $user, $data, $request) {
            $message = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_type' => 'tenant',
                'sender_user_id' => $user->id,
                'user_id' => $user->id,
                'body' => $data['body'],
                'is_internal' => false,
            ]);

            $files = $request->file('attachments', []);
            foreach ($files as $file) {
                $path = $file->store('support_attachments', 'public');
                SupportAttachment::create([
                    'support_ticket_id' => $ticket->id,
                    'support_ticket_message_id' => $message->id,
                    'file_path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            $ticket->touch();

            return back()->with('success', 'Reply sent.');
        });
    }

    public function resolve(Request $request, SupportTicket $ticket)
    {
        $tenant = $request->route('tenant');
        $user = $request->user();

        abort_unless($user && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless((int) $ticket->tenant_id === (int) $tenant->id, 404);

        $ticket->status = 'resolved';
        $ticket->closed_at = now();
        $ticket->save();

        return back()->with('success', 'Ticket marked as resolved.');
    }
}
