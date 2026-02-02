<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\SupportAttachment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SupportTicket::class);
        $actor = auth('admin')->user() ?? auth()->user();
        $filters = [
            'status' => $request->query('status', 'received'),
            'category' => $request->query('category', ''),
            'priority' => $request->query('priority', ''),
            'workspace_type' => $request->query('workspace_type', ''),
            'created_by' => $request->query('created_by', ''),
            'tenant_id' => $request->query('tenant_id', ''),
        ];

        $query = SupportTicket::query()
            ->with(['creator', 'tenant'])
            ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
            ->when($filters['category'], fn($q) => $q->where('category', $filters['category']))
            ->when($filters['priority'], fn($q) => $q->where('priority', $filters['priority']))
            ->when($filters['workspace_type'], fn($q) => $q->where('workspace_type', $filters['workspace_type']))
            ->when($filters['created_by'], fn($q) => $q->where('created_by_user_id', $filters['created_by']))
            ->when($filters['tenant_id'], fn($q) => $q->where('tenant_id', $filters['tenant_id']))
            ->orderByDesc('created_at');

        $tickets = $query->paginate(20)->withQueryString();

        $creators = User::query()
            ->whereIn('role', ['provider', 'super_admin', 'superadmin', 'provider_employee', 'admin'])
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.support.support-index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'creators' => $creators,
            'tenants' => $tenants,
            'actor' => $actor,
        ]);
    }

    public function create()
    {
        $this->authorize('create', SupportTicket::class);
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);
        return view('admin.support.support-create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SupportTicket::class);
        $actor = auth('admin')->user() ?? auth()->user();

        $data = $request->validate([
            'category' => ['required', 'in:bug,question,feature'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
            'context_route' => ['nullable', 'string'],
            'context_url' => ['nullable', 'string'],
            'context_viewport' => ['nullable', 'string'],
        ]);

        $context = [
            'route_name' => $data['context_route'] ?? $request->route()?->getName(),
            'url' => $data['context_url'] ?? $request->headers->get('referer') ?? $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'viewport' => $data['context_viewport'] ?? null,
            'app_version' => config('app.version'),
            'ip' => $request->ip(),
        ];

        $workspaceType = null;
        if (!empty($data['tenant_id'])) {
            $workspaceType = Tenant::query()->whereKey($data['tenant_id'])->value('workspace_type');
        }

        $ticket = SupportTicket::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'workspace_type' => $workspaceType,
            'created_by_user_id' => $actor->id,
            'category' => $data['category'],
            'status' => 'received',
            'priority' => $data['priority'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'context' => $context,
        ]);

        $files = $request->file('attachments', []);
        foreach ($files as $file) {
            $path = $file->store('support_attachments', 'public');
            SupportAttachment::create([
                'support_ticket_id' => $ticket->id,
                'file_path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'provider',
            'sender_admin_id' => $actor->id,
            'sender_user_id' => $actor->id,
            'user_id' => $actor->id,
            'body' => $ticket->body,
            'is_internal' => false,
        ]);

        return redirect()->route('admin.support.show', $ticket)
            ->with('success', 'Ticket created.');
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['creator', 'tenant', 'messages.author', 'messages.adminAuthor', 'attachments']);

        return view('admin.support.support-show', [
            'ticket' => $ticket,
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);
        $data = $request->validate([
            'status' => ['required', 'in:received,in_progress,waiting_on_customer,resolved'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'assigned_admin_id' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->status = $data['status'];
        $ticket->priority = $data['priority'] ?? $ticket->priority;
        $ticket->assigned_admin_id = $data['assigned_admin_id'] ?? $ticket->assigned_admin_id;
        $ticket->closed_at = $data['status'] === 'resolved' ? now() : null;
        $ticket->save();

        return back()->with('success', 'Ticket updated.');
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);
        $actor = auth('admin')->user() ?? auth()->user();

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'provider',
            'sender_admin_id' => $actor->id,
            'sender_user_id' => $actor->id,
            'user_id' => $actor->id,
            'body' => $data['body'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        if (empty($data['is_internal'])) {
            $ticket->last_public_reply_at = now();
            $ticket->status = $ticket->status === 'received' ? 'in_progress' : $ticket->status;
            $ticket->save();

        }

        return back()->with('success', 'Message added.');
    }
}
