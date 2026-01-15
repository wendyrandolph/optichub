<?php

// app/Http/Controllers/ClientPortal/ProjectMessagesController.php
namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Client; // your "clients" table model
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Models\ProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectMessagesController extends Controller
{
    public function index()
    {
        $user = Auth::guard('client')->user();
        $client = Client::where('tenant_id', $user->tenant_id)->where('id', $user->contact_id)->firstOrFail();

        $companyId = $client->client_company_id;

        // Show projects for the client contact or their company.
        $projects = Project::query()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($q) use ($client, $companyId) {
                $q->where('contact_id', $client->id);
                if ($companyId) {
                    $q->orWhere('client_company_id', $companyId)
                        ->orWhereIn('contact_id', function ($sub) use ($companyId) {
                            $sub->select('id')
                                ->from('contacts')
                                ->where('client_company_id', $companyId);
                        });
                }
            })
            ->with(['conversation'])
            ->latest('updated_at')
            ->get();

        return view('portal.messages.index', compact('projects'));
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

        // Create the conversation if missing (client can initiate)
        $companyName = $client->company?->company_name
            ?? $project->company?->company_name
            ?? $projectContact?->company?->company_name;
        $conversation = ProjectConversation::firstOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $project->tenant_id,
                'company_name' => $companyName,
                'last_message_at' => now(),
            ]
        );

        $messages = $conversation->messages()->orderBy('created_at')->get();
        $conversation->update(['public_last_viewed_at' => now()]);

        return view('portal.messages.show', compact('project', 'conversation', 'messages', 'client'));
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

        $companyName = $client->company?->company_name
            ?? $project->company?->company_name
            ?? $projectContact?->company?->company_name;
        $conversation = ProjectConversation::firstOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $project->tenant_id,
                'company_name' => $companyName,
            ]
        );

        ProjectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'sender_id' => $user->id,
            'body' => $request->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('portal.projects.messages.index', $project)->with('success', 'Message sent.');
    }
}
