<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Project;
use App\Models\Client;
use App\Mail\ProposalSentMailable; // NEW: For sending the proposal link
use App\Http\Requests\Proposal\StoreProposalRequest; // NEW: For validation
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProposalController extends Controller
{
  public function __construct()
  {
    // Enforce authentication for all internal (admin) actions
    $this->middleware('auth')->except(['showClient', 'accept', 'reject']);
  }

  // --- Internal/Admin Views ---

  /**
   * Display a listing of the proposals.
   * Replaces index()
   */
  public function index(): View
  {
    $this->authorize('viewAny', Proposal::class);

    // Eloquent handles fetching proposals scoped to the current tenant
    $proposals = Proposal::latest()->get();

    return view('proposals.index', compact('proposals'));
  }

  /**
   * Show the form for creating a new proposal.
   * Replaces create()
   */
  public function createForm(): View
  {
    $this->authorize('create', Proposal::class);

    // Fetch related resources (scoped to current tenant)
    $projects = Project::all(['id', 'name']);
    $clients = Client::all(['id', 'name']);

    return view('proposals.create', compact('projects', 'clients'));
  }

  /**
   * Store a newly created proposal in storage.
   * Replaces store() - Validation/CSRF handled by StoreProposalRequest
   *
   * @param \App\Http\Requests\Proposal\StoreProposalRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(StoreProposalRequest $request)
  {
    $validated = $request->validated();

    // 1. Prepare Content (The Proposal model should use JSON casting for 'content')
    $proposalSections = [
      'goals'      => $validated['goals'] ?? '',
      'objectives' => $validated['objectives'] ?? '',
      'investment' => $validated['investment'] ?? '',
      'timeline'   => $validated['timeline'] ?? '',
    ];

    // 2. Combine data for creation
    $data = array_merge($validated, [
      'content' => $proposalSections, // Model casting handles JSON encoding
      'unique_share_token' => Str::random(32), // Laravel helper for unique token
    ]);

    // 3. Create the proposal
    Proposal::create($data);

    // Replaces manual header redirect with success message
    return Redirect::route('proposals.index')
      ->with('success_message', 'Proposal created successfully!');
  }

  /**
   * Send the proposal link to the client via email.
   * Replaces send(int $id)
   *
   * @param \App\Models\Proposal $proposal (Route Model Binding)
   * @return \Illuminate\Http\RedirectResponse
   */
  public function send(Proposal $proposal)
  {
    $this->authorize('update', $proposal); // Auth check

    // Eager load the client relationship
    $proposal->load('client');
    $client = $proposal->client;

    if (!$client || empty($client->email)) {
      return Redirect::route('proposals.show', $proposal)
        ->with('error_message', 'Client email not found for this proposal.');
    }

    try {
      // 1. Send Mailable (replaces procedural mail())
      Mail::to($client->email)->send(new ProposalSentMailable($proposal));

      // 2. Update status
      $proposal->update([
        'status' => 'sent',
        'sent_at' => now()
      ]);

      return Redirect::route('proposals.index')
        ->with('success_message', 'Proposal sent successfully!');
    } catch (\Throwable $e) {
      // Log the error and redirect back
      Log::error("Proposal send failed for ID {$proposal->id}: " . $e->getMessage());

      return Redirect::route('proposals.show', $proposal)
        ->with('error_message', 'Failed to send proposal email. Check application logs.');
    }
  }

  /**
   * Show the internal (admin) view of the proposal.
   * Replaces show(int $id)
   *
   * @param \App\Models\Proposal $proposal (Route Model Binding)
   */
  public function show(Proposal $proposal): View
  {
    $this->authorize('view', $proposal);

    // Content is automatically decoded by the model's JSON cast
    return view('proposals.view', compact('proposal'));
  }

  // --- Public Client Actions ---

  /**
   * Show the public, client-facing view of the proposal.
   * Replaces showClient(string $token)
   *
   * @param string $token
   */
  public function showClient(string $token): View
  {
    $proposal = Proposal::where('unique_share_token', $token)->first();

    if (!$proposal) {
      abort(404);
    }
    // Content is automatically decoded by the model's JSON cast
    return view('proposals.show_client', compact('proposal'));
  }

  /**
   * Mark proposal as accepted.
   * Replaces accept(string $token)
   *
   * @param string $token
   */
  public function accept(string $token): View
  {
    $proposal = Proposal::where('unique_share_token', $token)->first();

    if ($proposal) {
      // Assuming markAsAccepted is an Eloquent model method
      $proposal->markAsAccepted();
      $message = 'Thank you for accepting the proposal!';
    } else {
      // Use Laravel's built-in abort for 404
      abort(404);
    }

    return view('proposals.confirmation', compact('message'));
  }

  /**
   * Mark proposal as rejected.
   * Replaces reject(string $token)
   *
   * @param string $token
   */
  public function reject(string $token): View
  {
    $proposal = Proposal::where('unique_share_token', $token)->first();

    if ($proposal) {
      // Assuming markAsRejected is an Eloquent model method
      $proposal->markAsRejected();
      $message = 'You have declined the proposal.';
    } else {
      abort(404);
    }

    return view('proposals.confirmation', compact('message'));
  }
}
