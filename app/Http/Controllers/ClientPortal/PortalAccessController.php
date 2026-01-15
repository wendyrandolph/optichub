<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ProjectConversation;
use Illuminate\Http\Request;

class PortalAccessController extends Controller
{
    public function show(string $token)
    {
        $conversation = ProjectConversation::where('public_token', $token)
            ->where(function ($q) {
                $q->whereNull('public_expires_at')
                    ->orWhere('public_expires_at', '>', now());
            })
            ->with(['project', 'project.messages', 'project.client'])
            ->firstOrFail();

        $conversation->update([
            'public_last_viewed_at' => now(),
        ]);

        return view('portal.access.project', [
            'conversation' => $conversation,
            'project' => $conversation->project,
            'tenant' => $conversation->project->tenant,
        ]);
    }

    public function approve(Request $request, string $token)
    {
        $conversation = ProjectConversation::where('public_token', $token)
            ->firstOrFail();

        $conversation->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approval_note' => $request->input('note'),
        ]);

        return redirect()
            ->back()
            ->with('status', 'Project approved successfully.');
    }
}
