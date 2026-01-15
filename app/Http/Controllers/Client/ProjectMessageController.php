<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Models\ProjectMessage;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectMessageController extends Controller
{
    public function index(?Tenant $tenant = null, Project $project)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        // Only enforce tenant match when the guard carries a tenant_id (clients)
        $tenantId = $tenant?->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        $conversation = ProjectConversation::firstOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $project->tenant_id,
                'company_name' => null,
            ]
        );

        $messages = $conversation->messages()
            ->with(['conversation'])
            ->orderBy('created_at')
            ->get();

        return view('portal.messages.project', compact('project', 'messages'));
    }

    public function store(Request $request, ?Tenant $tenant = null, Project $project)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        $tenantId = $tenant?->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = ProjectConversation::firstOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $project->tenant_id,
                'company_name' => null,
            ]
        );

        ProjectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => $guard === 'client' ? 'client' : 'tenant',
            'sender_id' => $user->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Message sent.');
    }

    public function update(Request $request, Tenant $tenant, Project $project, ProjectMessage $message)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        $tenantId = $tenant->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
            abort_unless($message->sender_type === 'client' && (int)$message->sender_id === (int)$user->id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        abort_unless((int)($message->conversation->project_id ?? 0) === (int)$project->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $data['body']]);
        $message->conversation?->update(['last_message_at' => now()]);

        return back()->with('status', 'Message updated.');
    }

    public function destroy(Tenant $tenant, Project $project, ProjectMessage $message)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        $tenantId = $tenant->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
            abort_unless($message->sender_type === 'client' && (int)$message->sender_id === (int)$user->id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        abort_unless((int)($message->conversation->project_id ?? 0) === (int)$project->id, 404);

        $message->delete();

        return back()->with('status', 'Message deleted.');
    }
}
