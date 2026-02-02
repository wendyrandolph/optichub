<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
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

        $channel = ChatChannel::firstOrCreate(
            ['tenant_id' => $project->tenant_id, 'type' => 'project', 'project_id' => $project->id],
            ['name' => $project->project_name]
        );

        $messages = ChatMessage::query()
            ->where('channel_id', $channel->id)
            ->with('user:id,first_name,last_name,role,email')
            ->orderBy('created_at')
            ->get();

        ChatRead::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        if ($guard === 'client') {
            return redirect()->route('portal.projects.show', $project->id);
        }

        return redirect()->route('projects.show', $project->id);
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

        $channel = ChatChannel::firstOrCreate(
            ['tenant_id' => $project->tenant_id, 'type' => 'project', 'project_id' => $project->id],
            ['name' => $project->project_name]
        );

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        ChatRead::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return back()->with('status', 'Message sent.');
    }

    public function update(Request $request, Tenant $tenant, Project $project, ChatMessage $message)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        $tenantId = $tenant->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
            abort_unless((int)$message->user_id === (int)$user->id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        $channel = ChatChannel::where('tenant_id', $project->tenant_id)
            ->where('type', 'project')
            ->where('project_id', $project->id)
            ->first();
        abort_unless($channel && (int)$message->channel_id === (int)$channel->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $data['body']]);

        return back()->with('status', 'Message updated.');
    }

    public function destroy(Tenant $tenant, Project $project, ChatMessage $message)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);

        $tenantId = $tenant->id ?? $user->tenant_id ?? $project->tenant_id;

        if ($guard === 'client') {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
            abort_unless((int)$message->user_id === (int)$user->id, 403);
        } elseif (!empty($user->tenant_id)) {
            abort_unless((int)$project->tenant_id === (int)$tenantId, 404);
        }

        $channel = ChatChannel::where('tenant_id', $project->tenant_id)
            ->where('type', 'project')
            ->where('project_id', $project->id)
            ->first();
        abort_unless($channel && (int)$message->channel_id === (int)$channel->id, 404);

        $message->delete();

        return back()->with('status', 'Message deleted.');
    }
}
