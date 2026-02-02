<?php

// app/Http/Controllers/ClientPortal/ProjectMessagesController.php
namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Client; // your "clients" table model
use App\Models\Project;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectMessagesController extends Controller
{
    public function index()
    {
        return redirect()->route('portal.projects.index');
    }

    public function show(Project $project)
    {
        $user = Auth::guard('client')->user();
        Gate::authorize('portal-view-project', $project);

        $client = Client::where('tenant_id', $user->tenant_id)->where('id', $user->contact_id)->firstOrFail();

        // Ensure the client belongs to this project: allow if they are the contact, same company, or the project has no contact yet
        $projectContact = $project->contact_id
            ? Client::where('tenant_id', $user->tenant_id)->where('id', $project->contact_id)->first()
            : null;

        $isProjectContact = $projectContact && (int) $projectContact->id === (int) $client->id;
        $projectCompanyId = $project->client_company_id ?? $projectContact?->client_company_id;
        $clientCompanyId = $client->client_company_id;

        abort_unless(
            $isProjectContact || ($projectCompanyId && $clientCompanyId && (int) $projectCompanyId === (int) $clientCompanyId),
            403
        );

        return redirect()->to(route('portal.projects.index', ['chat_project' => $project->id]) . '#project-chat-' . $project->id);
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::guard('client')->user();
        Gate::authorize('portal-view-project', $project);

        $client = Client::where('tenant_id', $user->tenant_id)->where('id', $user->contact_id)->firstOrFail();

        $projectContact = $project->contact_id
            ? Client::where('tenant_id', $user->tenant_id)->where('id', $project->contact_id)->first()
            : null;

        $isProjectContact = $projectContact && (int) $projectContact->id === (int) $client->id;
        $projectCompanyId = $project->client_company_id ?? $projectContact?->client_company_id;
        $clientCompanyId = $client->client_company_id;

        abort_unless(
            $isProjectContact || ($projectCompanyId && $clientCompanyId && (int) $projectCompanyId === (int) $clientCompanyId),
            403
        );

        $channel = ChatChannel::firstOrCreate(
            ['tenant_id' => $project->tenant_id, 'type' => 'project', 'project_id' => $project->id],
            ['name' => $project->project_name]
        );

        ChatMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        ChatRead::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return redirect()->to(route('portal.projects.index', ['chat_project' => $project->id]) . '#project-chat-' . $project->id)
            ->with('success', 'Message sent.');
    }
}
