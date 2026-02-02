<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Models\Task;
use App\Models\Upload;
use App\Models\ProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectConversationController extends Controller
{
    protected function findConversationByToken(string $token): ProjectConversation
    {
        $conversation = ProjectConversation::with(['project', 'messages'])
            ->where('public_token', $token)
            ->firstOrFail();

        abort_if($conversation->public_expires_at && $conversation->public_expires_at->isPast(), 403, 'Link expired.');

        return $conversation;
    }

    public function showPublic(string $token)
    {
        $conversation = $this->findConversationByToken($token);
        $project = $conversation->project;
        abort_unless($project, 404);

        $messages = $conversation->messages()->orderBy('created_at')->get();

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->when($project->contact_id, fn($q) => $q->where('contact_id', $project->contact_id))
            ->orderBy('due_date')
            ->get();

        $uploads = Upload::query()
            ->where('project_id', $project->id)
            ->when($project->contact_id, fn($q) => $q->where(function ($sub) use ($project) {
                $sub->whereNull('client_visible')->orWhere('client_visible', true)->orWhere('contact_id', $project->contact_id);
            }))
            ->get();

        return view('public.conversation', compact('project', 'conversation', 'messages', 'tasks', 'uploads'));
    }

    public function publicMessage(Request $request, string $token)
    {
        $conversation = $this->findConversationByToken($token);
        $project = $conversation->project;
        abort_unless($project, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $senderId = $project->contact_id ?? 0;

        ProjectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'sender_id' => $senderId,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Message sent.');
    }

    public function publicTaskStatus(Request $request, string $token, Task $task)
    {
        $conversation = $this->findConversationByToken($token);
        $project = $conversation->project;
        abort_unless($project, 404);
        abort_unless((int)$task->project_id === (int)$project->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,completed'],
        ]);

        $task->update(['status' => $data['status']]);

        return back()->with('status', 'Task updated.');
    }

    public function publicFile(string $token, Upload $upload)
    {
        $conversation = $this->findConversationByToken($token);
        $project = $conversation->project;
        abort_unless($project, 404);
        abort_unless((int)$upload->project_id === (int)$project->id, 404);

        // Visibility: allow if client_visible or belongs to the project contact
        if ($upload->client_visible === false) {
            abort(403);
        }
        if ($upload->contact_id && $project->contact_id && (int)$upload->contact_id !== (int)$project->contact_id) {
            abort(403);
        }

        $disk = Storage::disk('public');
        $path = ltrim($upload->path, '/');
        if (!$disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path));
    }

    public function publicMessageUpdate(Request $request, string $token, ProjectMessage $message)
    {
        $conversation = $this->findConversationByToken($token);
        abort_unless((int)$message->conversation_id === (int)$conversation->id, 404);
        abort_unless(($message->sender_type ?? '') === 'client', 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $data['body']]);
        $conversation->update(['last_message_at' => now()]);

        return back()->with('status', 'Message updated.');
    }

    public function publicMessageDelete(string $token, ProjectMessage $message)
    {
        $conversation = $this->findConversationByToken($token);
        abort_unless((int)$message->conversation_id === (int)$conversation->id, 404);
        abort_unless(($message->sender_type ?? '') === 'client', 403);

        $message->delete();

        return back()->with('status', 'Message deleted.');
    }

    public function show(Project $project)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);
        abort_unless((int)$project->tenant_id === (int)$user->tenant_id, 404);

        if ($guard === 'client') {
            abort_unless((int)$project->contact_id === (int)$user->contact_id, 403);
        }

        if ($guard === 'client') {
            return redirect()->route('portal.projects.show', $project->id);
        }

        return redirect()->route('projects.show', $project->id);
    }

    public function approve(Project $project, Request $request)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);
        abort_unless((int)$project->tenant_id === (int)$user->tenant_id, 404);

        $conversation = ProjectConversation::firstOrCreate([
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
        ]);

        $contactId = $guard === 'client' ? $user->contact_id : null;

        $conversation->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by_contact_id' => $contactId,
            'approval_note' => $request->input('note'),
        ]);

        return back()->with('status', 'Project approved');
    }

    public function requestChanges(Project $project, Request $request)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);
        abort_unless((int)$project->tenant_id === (int)$user->tenant_id, 404);

        $conversation = ProjectConversation::firstOrCreate([
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
        ]);

        $conversation->update([
            'approval_status' => 'changes_requested',
            'approval_note' => $request->input('note'),
        ]);

        return back()->with('status', 'Changes requested');
    }

    public function decision(Project $project, Request $request)
    {
        $guard = Auth::guard('client')->check() ? 'client' : 'admin';
        $user = Auth::guard($guard)->user();

        abort_unless($user, 403);
        abort_unless((int)$project->tenant_id === (int)$user->tenant_id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:approved,changes_requested'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $conversation = ProjectConversation::firstOrCreate([
            'tenant_id'  => $project->tenant_id,
            'project_id' => $project->id,
        ]);

        $conversation->update([
            'approval_status' => $data['status'],
            'approved_at' => $data['status'] === 'approved' ? now() : null,
            'approved_by_contact_id' => $guard === 'client' ? $user->contact_id : null,
            'approval_note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Decision saved.');
    }
}
