<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactNote;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContactActivity;

class ContactNotesController extends Controller
{
    public function store(Request $request, Tenant $tenant, Client $contact)
    {
        $this->authorize('update', $contact);

        $data = $request->validate([
            'body' => 'required|string|min:8|max:2000',
            'note_type' => 'nullable|string|max:50',
            'happened_at' => 'nullable|date',
            'pinned' => 'sometimes|boolean',
        ]);

        $note = ContactNote::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'created_by' => Auth::id(),
            'body' => $data['body'],
            'note_type' => $data['note_type'] ?? null,
            'happened_at' => $data['happened_at'] ?? null,
            'pinned' => (bool) ($data['pinned'] ?? false),
        ]);

        // Log activity for relevant note types
        if (in_array(strtolower($data['note_type'] ?? ''), ['call', 'meeting', 'decision'])) {
            ContactActivity::create([
                'tenant_id' => $tenant->id,
                'contact_id' => $contact->id,
                'actor_id' => Auth::id(),
                'type' => 'note.' . strtolower($data['note_type']),
                'happened_at' => $data['happened_at'] ?? now(),
                'meta' => [
                    'note_id' => $note->id,
                    'body' => mb_substr($data['body'], 0, 120),
                ],
            ]);
        }

        // Gentle hint for action-like notes
        if (preg_match('/(need to|todo|next:)/i', $data['body'])) {
            return back()->with('success_message', 'Note added. Consider creating a task for action items.');
        }

        // Optional activity hook could be added here (Call/Meeting)

        return back()->with('success_message', 'Note added.');
    }

    public function destroy(Tenant $tenant, Client $contact, ContactNote $note)
    {
        $this->authorize('update', $contact);
        abort_unless($note->contact_id === $contact->id && $note->tenant_id === $tenant->id, 404);

        $note->delete();

        return back()->with('success_message', 'Note removed.');
    }

    public function pin(Tenant $tenant, Client $contact, ContactNote $note)
    {
        $this->authorize('update', $contact);
        abort_unless($note->contact_id === $contact->id && $note->tenant_id === $tenant->id, 404);

        $note->pinned = !$note->pinned;
        $note->save();

        return back()->with('success_message', $note->pinned ? 'Note pinned.' : 'Note unpinned.');
    }
}
