<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientMessageController extends Controller
{
    public function index()
    {
        $client    = Auth::guard('client')->user();
        $contactId = $client->contact_id;

        $threads = Thread::query()
            ->where('contact_id', $contactId)
            ->with(['latestMessage'])
            ->latest('updated_at')
            ->get();

        return view('client.messages.index', [
            'threads' => $threads,
        ]);
    }

    public function show(Thread $thread)
    {
        $client    = Auth::guard('client')->user();
        $contactId = $client->contact_id;

        abort_unless($thread->contact_id === $contactId, Response::HTTP_FORBIDDEN);

        $thread->load(['messages' => function ($q) {
            $q->orderBy('created_at');
        }]);

        // Mark provider messages as read
        Message::where('thread_id', $thread->id)
            ->where('sender_type', 'provider')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('client.messages.show', [
            'thread'   => $thread,
            'messages' => $thread->messages,
        ]);
    }

    public function store(Request $request, Thread $thread)
    {
        $client    = Auth::guard('client')->user();
        $contactId = $client->contact_id;

        abort_unless($thread->contact_id === $contactId, Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = new Message();
        $message->thread_id   = $thread->id;
        $message->sender_type = 'client';
        $message->sender_id   = $client->id;
        $message->body        = $data['body'];
        $message->save();

        $thread->touch();

        // Later: dispatch notification to provider

        return redirect()
            ->route('portal.messages.show', $thread)
            ->with('status', 'Message sent.');
    }
}
