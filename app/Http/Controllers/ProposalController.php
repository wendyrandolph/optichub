<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Project;
use App\Models\Client;
use App\Models\ProposalPaymentSchedule;
use App\Models\Tenant;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\ClientCompany;
use App\Jobs\SendTrackedEmail;
use App\Models\OutboundEmail;
use App\Http\Requests\Proposal\StoreProposalRequest;
use App\Http\Requests\Proposal\UpdateProposalRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    $query = Proposal::query()->latest();

    $status = request('status');
    if (!empty($status)) {
      $query->where('status', $status);
    }

    $search = trim((string) request('q'));
    if ($search !== '') {
      $query->where(function ($builder) use ($search) {
        $builder->where('title', 'like', "%{$search}%")
          ->orWhereHas('client', function ($clientQuery) use ($search) {
            $clientQuery->where('firstName', 'like', "%{$search}%")
              ->orWhere('lastName', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
          })
          ->orWhereHas('lead', function ($leadQuery) use ($search) {
            $leadQuery->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
          })
          ->orWhereHas('project', function ($projectQuery) use ($search) {
            $projectQuery->where('project_name', 'like', "%{$search}%");
          });
      });
    }

    $proposals = $query->get();

    return view('proposals.index', compact('proposals'));
  }

  /**
   * Show the form for creating a new proposal.
   * Replaces create()
   */
  public function createForm(): View
  {
    $this->authorize('create', Proposal::class);

    $tenantParam = request()->route('tenant');
    $tenant = $tenantParam instanceof Tenant ? $tenantParam : Tenant::find($tenantParam);

    // Fetch related resources (scoped to current tenant)
    $projects = Project::query()
      ->orderBy('project_name')
      ->get(['id', 'project_name']);
    $clients = Client::query()
      ->orderBy('lastName')
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName', 'email']);
    $leads = collect();
    if ($tenant && $tenant->workspace_type === 'creative') {
      $leads = Lead::query()
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'phone']);
    }
    $templates = \App\Models\ProposalTemplate::query()
      ->orderBy('name')
      ->get();

    $selectedTemplate = null;
    $templateId = (int) request()->query('template_id', 0);
    if ($templateId > 0) {
      $selectedTemplate = \App\Models\ProposalTemplate::query()
        ->where('id', $templateId)
        ->first();
    }
    if ($selectedTemplate) {
      $selectedTemplate->payment_schedule = $this->normalizePaymentSchedule($selectedTemplate->payment_schedule ?? []);
    }

    return view('proposals.create', compact('projects', 'clients', 'templates', 'selectedTemplate', 'tenant', 'leads'));
  }

  public function edit(\App\Models\Tenant $tenant, Proposal $proposal): View
  {
    $this->authorize('update', $proposal);
    if (strtolower((string) $proposal->status) === 'approved') {
      return Redirect::route('tenant.proposals.show', ['tenant' => $tenant->getKey(), 'proposal' => $proposal->id])
        ->with('error_message', 'Approved proposals are read-only. Duplicate to create a new version.');
    }

    $projects = Project::query()
      ->orderBy('project_name')
      ->get(['id', 'project_name']);
    $clients = Client::query()
      ->orderBy('lastName')
      ->orderBy('firstName')
      ->get(['id', 'firstName', 'lastName', 'email']);
    $leads = collect();
    if ($tenant->workspace_type === 'creative') {
      $leads = Lead::query()
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'phone']);
    }

    $proposal->load('paymentScheduleItems');
    $paymentSchedule = ProposalPaymentSchedule::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('proposal_id', $proposal->id)
      ->orderBy('sort_order')
      ->get();

    return view('proposals.edit', compact('proposal', 'projects', 'clients', 'paymentSchedule', 'tenant', 'leads'));
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
    $validated = $this->normalizeProposalPayload($request->validated());
    $paymentSchedule = $validated['payment_schedule'] ?? [];
    unset($validated['payment_schedule']);

    $tenantParam = request()->route('tenant');
    $tenant = $tenantParam instanceof Tenant ? $tenantParam : Tenant::find($tenantParam);
    if ($tenant) {
      $validated = $this->applyRecipient($validated, $tenant);
    }

    if (empty($validated['project_id']) && !empty($validated['project_name']) && $tenant) {
      $project = Project::create([
        'tenant_id' => $tenant->id,
        'contact_id' => $validated['contact_id'] ?? $validated['client_id'] ?? null,
        'project_name' => $validated['project_name'],
        'status' => 'open',
        'uses_phases' => true,
      ]);
      $validated['project_id'] = $project->id;
    }

    unset($validated['project_name'], $validated['use_existing_project']);

    $data = array_merge($validated, [
      'status' => $validated['status'] ?? 'draft',
      'unique_share_token' => Str::random(32),
    ]);

    DB::transaction(function () use ($data, $validated, $paymentSchedule) {
      $proposal = Proposal::create($data);
      $this->syncItems($proposal, $validated);
      $this->syncPayments($proposal, $paymentSchedule);
    });

    // Replaces manual header redirect with success message
    return Redirect::route('tenant.proposals.index', ['tenant' => request()->route('tenant')])
      ->with('success_message', 'Proposal created successfully!');
  }

  public function update(UpdateProposalRequest $request, \App\Models\Tenant $tenant, Proposal $proposal)
  {
    $this->authorize('update', $proposal);
    if (strtolower((string) $proposal->status) === 'approved') {
      return Redirect::route('tenant.proposals.show', ['tenant' => $tenant->getKey(), 'proposal' => $proposal->id])
        ->with('error_message', 'Approved proposals are read-only. Duplicate to create a new version.');
    }

    $validated = $this->normalizeProposalPayload($request->validated());
    $paymentSchedule = $validated['payment_schedule'] ?? [];
    unset($validated['payment_schedule']);
    $validated = $this->applyRecipient($validated, $tenant, $proposal);
    DB::transaction(function () use ($proposal, $validated, $paymentSchedule) {
      $proposal->update($validated);
      $proposal->items()->delete();
      $this->syncItems($proposal, $validated);
      $proposal->paymentScheduleItems()->delete();
      $this->syncPayments($proposal, $paymentSchedule);
    });

    return Redirect::route('tenant.proposals.show', ['tenant' => $tenant->getKey(), 'proposal' => $proposal->id])
      ->with('success_message', 'Proposal updated.');
  }

  /**
   * Send the proposal link to the client via email.
   * Replaces send(int $id)
   *
   * @param \App\Models\Proposal $proposal (Route Model Binding)
   * @return \Illuminate\Http\RedirectResponse
   */
  public function send(\Illuminate\Http\Request $request, \App\Models\Tenant $tenant, Proposal $proposal)
  {
    $this->authorize('update', $proposal); // Auth check

    // Eager load the client/lead relationship
    $proposal->load('client', 'lead');
    $client = $proposal->client;
    $lead = $proposal->lead;

    $recipientEmail = $client?->email ?? $lead?->email;
    if (empty($recipientEmail)) {
      return Redirect::route('tenant.proposals.show', ['tenant' => $tenant->getKey(), 'proposal' => $proposal->id])
        ->with('error_message', 'Client email not found for this proposal.');
    }

    try {
      if (config('mail.default')) {
        $outbound = OutboundEmail::create([
          'tenant_id' => $tenant->id,
          'user_id' => auth()->id(),
          'to_email' => $recipientEmail,
          'to_name' => trim(($client?->firstName ?? '') . ' ' . ($client?->lastName ?? '')) ?: ($lead?->name ?? null),
          'subject' => 'New Proposal: ' . $proposal->title,
          'type' => 'proposal_sent',
          'related_type' => Proposal::class,
          'related_id' => $proposal->id,
          'status' => 'queued',
          'queued_at' => now(),
          'meta' => ['proposal_id' => $proposal->id],
        ]);

        SendTrackedEmail::dispatch($outbound->id);
      }
    } catch (\Throwable $e) {
      Log::error("Proposal send failed for ID {$proposal->id}: " . $e->getMessage());
    }

    if (empty($proposal->public_token)) {
      $proposal->public_token = Str::random(40);
    }
    if (empty($proposal->unique_share_token)) {
      $proposal->unique_share_token = Str::random(40);
    }
    $proposal->update([
      'status' => 'sent',
      'sent_at' => now(),
      'public_token' => $proposal->public_token,
      'unique_share_token' => $proposal->unique_share_token,
    ]);

    $this->attachContractIfRequested($request, $proposal, $tenant);

    if ($tenant->workspace_type === 'creative') {
      app(\App\Services\AutomationRunner::class)
        ->run($tenant, 'proposal_sent', Proposal::class, $proposal->id);
    }

    return Redirect::route('tenant.proposals.index', ['tenant' => $tenant->getKey()])
      ->with('success_message', 'Proposal marked as sent.');
  }

  private function attachContractIfRequested(\Illuminate\Http\Request $request, Proposal $proposal, Tenant $tenant): void
  {
    $mode = $request->input('contract_mode', 'none');
    if ($mode === 'none') {
      return;
    }

    $contract = null;
    if ($mode === 'template') {
      $templateId = (int) $request->input('contract_template_id');
      if ($templateId > 0) {
        $template = \App\Models\ContractTemplate::query()
          ->where('tenant_id', $tenant->id)
          ->where('id', $templateId)
          ->first();
        if ($template) {
          $contract = $this->createContractFromTemplate($template, $proposal, $tenant);
        }
      }
    }

    if ($mode === 'upload' && $request->hasFile('contract_upload')) {
      $path = $request->file('contract_upload')->store('contracts/uploads', 'public');
      $hash = hash('sha256', Storage::disk('public')->get($path));
      $contract = \App\Models\Contract::create([
        'tenant_id' => $tenant->id,
        'proposal_id' => $proposal->id,
        'project_id' => $proposal->project_id,
        'title' => $proposal->title . ' Contract',
        'source_type' => 'upload_snapshot',
        'snapshot_json' => null,
        'snapshot_html' => null,
        'pdf_path' => null,
        'status' => 'sent',
        'sent_at' => now(),
        'snapshot_hash' => $hash,
      ]);
      $contract->update(['pdf_path' => $path]);
    }

    if ($contract) {
      $proposal->update(['contract_id' => $contract->id]);
    }
  }

  private function createContractFromTemplate(\App\Models\ContractTemplate $template, Proposal $proposal, Tenant $tenant): \App\Models\Contract
  {
    $snapshot = $template->source_type === 'builder' ? ($template->builder_json ?? []) : null;
    $hashSource = $template->source_type === 'builder'
      ? json_encode($snapshot)
      : ($template->file_path ? Storage::disk('public')->get($template->file_path) : '');
    $hash = hash('sha256', (string) $hashSource);

    $contract = \App\Models\Contract::create([
      'tenant_id' => $tenant->id,
      'proposal_id' => $proposal->id,
      'project_id' => $proposal->project_id,
      'contract_template_id' => $template->id,
      'title' => $template->title,
      'source_type' => $template->source_type === 'builder' ? 'builder_snapshot' : 'upload_snapshot',
      'snapshot_json' => $snapshot,
      'snapshot_html' => null,
      'pdf_path' => null,
      'status' => 'sent',
      'sent_at' => now(),
      'snapshot_hash' => $hash,
    ]);

    $pdf = Pdf::loadView('proposals.contract-pdf', [
      'tenant' => $tenant,
      'proposal' => $proposal,
      'contract' => $contract,
      'template' => $template,
    ])->setPaper('letter');

    $filePath = 'contracts/snapshots/' . $contract->id . '-' . $hash . '.pdf';
    Storage::disk('public')->put($filePath, $pdf->output());
    $contract->update(['pdf_path' => $filePath]);

    return $contract;
  }

  /**
   * Show the internal (admin) view of the proposal.
   * Replaces show(int $id)
   *
   * @param \App\Models\Proposal $proposal (Route Model Binding)
   */
  public function show(\App\Models\Tenant $tenant, Proposal $proposal): View
  {
    $this->authorize('view', $proposal);

    if (empty($proposal->unique_share_token)) {
      $proposal->unique_share_token = Str::random(32);
      $proposal->save();
    }

    $proposal->load('items', 'client', 'tenant', 'acceptance');
    $contractTemplates = \App\Models\ContractTemplate::query()
      ->where('tenant_id', $tenant->id)
      ->where('is_active', true)
      ->orderBy('title')
      ->get();
    $paymentSchedule = ProposalPaymentSchedule::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('proposal_id', $proposal->id)
      ->orderBy('sort_order')
      ->get();
    return view('proposals.show', compact('proposal', 'paymentSchedule', 'contractTemplates'));
  }

  public function pdf(\App\Models\Tenant $tenant, Proposal $proposal)
  {
    $this->authorize('view', $proposal);

    $proposal->load('items', 'client', 'tenant');
    $paymentSchedule = ProposalPaymentSchedule::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('proposal_id', $proposal->id)
      ->orderBy('sort_order')
      ->get();
    $pdf = Pdf::loadView('proposals.pdf', [
      'proposal' => $proposal,
      'paymentSchedule' => $paymentSchedule,
      'tenant' => $proposal->tenant,
    ]);

    $fileName = 'Proposal-' . ($proposal->id ?? 'draft') . '.pdf';
    return $pdf->download($fileName);
  }

  // --- Public Client Actions ---

  /**
   * Show the public, client-facing view of the proposal.
   * Replaces showClient(string $token)
   *
   * @param string $token
   */
  public function showClient(\Illuminate\Http\Request $request, string $token): View
  {
    $proposal = Proposal::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('unique_share_token', $token)
      ->first();

    if (!$proposal) {
      abort(404);
    }

    $isDraft = strtolower((string) $proposal->status) === 'draft';
    $isPreview = $request->boolean('preview');

    if ($isDraft && !$isPreview) {
      abort(404);
    }

    if ($isDraft && $isPreview && !auth()->check() && !auth('admin')->check()) {
      abort(403);
    }
    $proposal->load('items', 'client', 'tenant', 'acceptance');
    $paymentSchedule = ProposalPaymentSchedule::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('proposal_id', $proposal->id)
      ->orderBy('sort_order')
      ->get();
    return view('proposals.show_client', compact('proposal', 'paymentSchedule', 'isPreview'));
  }

  /**
   * Mark proposal as accepted.
   * Replaces accept(string $token)
   *
   * @param string $token
   */
  public function accept(string $token): View
  {
    $proposal = Proposal::findByToken($token);

    if ($proposal) {
      $proposal->load('client', 'lead');
      $recipientName = $proposal->recipientName();
      $recipientEmail = $proposal->client?->email ?? $proposal->lead?->email ?? 'unknown@example.com';
      $result = $this->handleApproval($proposal, [
        'name' => $recipientName ?: 'Client',
        'email' => $recipientEmail,
        'signed_name' => $recipientName ?: 'Client',
      ]);
      $message = $result ? 'Thank you for approving the proposal!' : 'This proposal is already approved.';
    } else {
      // Use Laravel's built-in abort for 404
      abort(404);
    }

    return view('proposals.confirmation', compact('message'));
  }

  public function approve(\Illuminate\Http\Request $request, string $token)
  {
    $proposal = Proposal::findByToken($token);
    if (!$proposal) {
      abort(404);
    }

    if (strtolower((string) $proposal->status) === 'approved') {
      return Redirect::route('proposal.public.show', $proposal->unique_share_token)
        ->with('success_message', 'This proposal has already been approved.');
    }

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255'],
      'signed_name' => ['required', 'string', 'max:255'],
      'consent' => ['accepted'],
    ]);

    $this->handleApproval($proposal, $validated, $request);

    return Redirect::route('proposal.public.show', $proposal->unique_share_token)
      ->with('success_message', 'Proposal approved.');
  }

  public function duplicate(\App\Models\Tenant $tenant, Proposal $proposal)
  {
    $this->authorize('create', Proposal::class);

    $proposal->load('items');

    $clone = $proposal->replicate([
      'status',
      'sent_at',
      'approved_at',
      'approved_pdf_path',
    ]);
    $clone->status = 'draft';
    $clone->sent_at = null;
    $clone->approved_at = null;
    $clone->approved_pdf_path = null;
    $clone->unique_share_token = Str::random(32);
    $clone->save();

    foreach ($proposal->items as $item) {
      $clone->items()->create([
        'type' => $item->type,
        'title' => $item->title,
        'description' => $item->description,
        'sort_order' => $item->sort_order,
      ]);
    }

    return Redirect::route('tenant.proposals.edit', ['tenant' => $tenant->getKey(), 'proposal' => $clone->id])
      ->with('success_message', 'New proposal version created.');
  }

  public function archive(\App\Models\Tenant $tenant, Proposal $proposal)
  {
    $this->authorize('delete', $proposal);

    $status = strtolower((string) $proposal->status);
    if ($status === 'draft') {
      $proposal->forceDelete();
    } else {
      $proposal->delete();
    }

    $message = $status === 'draft' ? 'Proposal deleted.' : 'Proposal archived.';

    return Redirect::route('tenant.proposals.index', ['tenant' => $tenant->getKey()])
      ->with('success_message', $message);
  }

  /**
   * Mark proposal as rejected.
   * Replaces reject(string $token)
   *
   * @param string $token
   */
  public function reject(string $token): View
  {
    $proposal = Proposal::findByToken($token);

    if ($proposal) {
      // Assuming markAsRejected is an Eloquent model method
      $proposal->markAsRejected();
      $message = 'You have declined the proposal.';
    } else {
      abort(404);
    }

    return view('proposals.confirmation', compact('message'));
  }

  private function normalizeProposalPayload(array $validated): array
  {
    $deliverables = collect($validated['deliverables'] ?? [])
      ->filter(fn ($row) => !empty($row['title']))
      ->values()
      ->all();

    $goals = collect($validated['goals'] ?? [])
      ->filter(fn ($row) => !empty($row['title']))
      ->values()
      ->all();

    $paymentSchedule = collect($validated['payment_schedule'] ?? [])
      ->filter(fn ($row) => is_array($row))
      ->values()
      ->all();

    $timeline = collect($validated['timeline'] ?? [])
      ->filter(fn ($row) => !empty($row['phase']) || !empty($row['duration']) || !empty($row['description']))
      ->values()
      ->all();

    $maintenancePlan = $validated['maintenance_plan'] ?? [];
    if (isset($maintenancePlan['enabled'])) {
      $maintenancePlan['enabled'] = (bool) $maintenancePlan['enabled'];
    }

    return array_merge($validated, [
      'goals' => $goals ?: null,
      'deliverables' => $deliverables ?: null,
      'payment_schedule' => $paymentSchedule ?: null,
      'timeline' => $timeline ?: null,
      'maintenance_plan' => $maintenancePlan ?: null,
    ]);
  }

  private function normalizePaymentSchedule(array $rows): array
  {
    $rows = collect($rows)
      ->map(fn ($row) => is_array($row) ? $row : [])
      ->filter(fn ($row) => !empty($row['label']) || !empty($row['amount']) || !empty($row['due_trigger']) || !empty($row['note']))
      ->values()
      ->all();

    $normalized = [];
    $count = count($rows);
    for ($i = 0; $i < $count; $i++) {
      $row = $rows[$i];
      $label = $row['label'] ?? null;
      $amount = $row['amount'] ?? null;
      $due = $row['due_trigger'] ?? null;
      $note = $row['note'] ?? null;

      // Merge a following amount-only row into a label-only row.
      if (!empty($label) && (empty($amount) || $amount === null) && ($i + 1) < $count) {
        $next = $rows[$i + 1];
        $nextLabel = $next['label'] ?? null;
        $nextAmount = $next['amount'] ?? null;
        if (empty($nextLabel) && (!empty($nextAmount) || $nextAmount === '0' || $nextAmount === 0)) {
          $amount = $nextAmount;
          $due = $due ?: ($next['due_trigger'] ?? null);
          $note = $note ?: ($next['note'] ?? null);
          $i++; // skip next row
        }
      }

      $normalized[] = [
        'label' => $label,
        'amount' => $amount,
        'due_trigger' => $due,
        'note' => $note,
      ];
    }

    return $normalized;
  }

  private function syncItems(Proposal $proposal, array $validated): void
  {
    foreach (($validated['goals'] ?? []) as $idx => $row) {
      if (empty($row['title'])) continue;
      $proposal->items()->create([
        'type' => 'goal',
        'title' => $row['title'],
        'description' => $row['description'] ?? null,
        'sort_order' => (int) ($row['sort_order'] ?? $idx),
      ]);
    }

    foreach (($validated['deliverables'] ?? []) as $idx => $row) {
      if (empty($row['title'])) continue;
      $proposal->items()->create([
        'type' => 'deliverable',
        'title' => $row['title'],
        'description' => $row['description'] ?? null,
        'sort_order' => (int) ($row['sort_order'] ?? $idx),
      ]);
    }
  }

  private function syncPayments(Proposal $proposal, array $rows): void
  {
    foreach ($rows as $idx => $row) {
      $label = $row['label'] ?? null;
      $amount = $row['amount'] ?? null;
      $due = $row['due_trigger'] ?? null;
      $note = $row['note'] ?? null;

      if (empty($label) || ($amount === null || $amount === '')) {
        continue;
      }

      $proposal->paymentScheduleItems()->create([
        'tenant_id' => $proposal->tenant_id,
        'label' => $label,
        'amount' => $amount,
        'due_trigger' => $due,
        'note' => $note,
        'sort_order' => $idx,
      ]);
    }
  }

  private function hydratePaymentSchedule(Proposal $proposal): void
  {
    if ($proposal->relationLoaded('paymentScheduleItems') && $proposal->paymentScheduleItems->isNotEmpty()) {
      return;
    }

    $query = ProposalPaymentSchedule::withoutGlobalScope(\App\Scopes\TenantScope::class)
      ->where('proposal_id', $proposal->id);

    if (!empty($proposal->tenant_id)) {
      $query->where('tenant_id', $proposal->tenant_id);
    }

    $items = $query->orderBy('sort_order')->get();

    $proposal->setRelation('paymentScheduleItems', $items);
  }

  private function handleApproval(Proposal $proposal, array $data, ?\Illuminate\Http\Request $request = null): bool
  {
    if (strtolower((string) $proposal->status) === 'approved') {
      return false;
    }

    $proposal->load('items', 'tenant', 'client');
    $pdf = Pdf::loadView('proposals.pdf', ['proposal' => $proposal, 'paymentSchedule' => $proposal->paymentScheduleItems, 'tenant' => $proposal->tenant]);
    $pdfBinary = $pdf->output();
    $hash = hash('sha256', $pdfBinary);
    $filePath = 'proposals/approved/' . $proposal->id . '-' . $hash . '.pdf';
    Storage::disk('public')->put($filePath, $pdfBinary);

    $proposal->update([
      'status' => 'approved',
      'approved_at' => now(),
      'approved_pdf_path' => $filePath,
    ]);

    $proposal->acceptance()->create([
      'tenant_id' => $proposal->tenant_id,
      'name' => $data['name'],
      'email' => $data['email'],
      'signed_name' => $data['signed_name'],
      'accepted_at' => now(),
      'ip_address' => $request?->ip(),
      'user_agent' => $request?->userAgent(),
      'proposal_hash' => $hash,
      'pdf_path' => $filePath,
    ]);

    if ($proposal->tenant?->workspace_type === 'creative') {
      app(\App\Services\AutomationRunner::class)
        ->run($proposal->tenant, 'proposal_approved', Proposal::class, $proposal->id);
    }

    return true;
  }

  private function applyRecipient(array $validated, Tenant $tenant, ?Proposal $proposal = null): array
  {
    if ($tenant->workspace_type !== 'creative') {
      $contactId = $validated['client_id'] ?? $validated['contact_id'] ?? null;
      if ($contactId) {
        $validated['client_id'] = $contactId;
        $validated['contact_id'] = $contactId;
      }
      return $validated;
    }

    $recipientType = $validated['recipient_type'] ?? 'new_lead';
    if ($recipientType === 'existing_contact') {
      $contactId = $validated['contact_id'] ?? $validated['client_id'] ?? null;
      if ($contactId) {
        $validated['client_id'] = $contactId;
        $validated['contact_id'] = $contactId;
      }
      $validated['lead_id'] = null;
      return $validated;
    }
    if ($recipientType === 'existing_lead') {
      $leadId = $validated['lead_id'] ?? null;
      if ($leadId) {
        $validated['lead_id'] = $leadId;
      }
      $validated['contact_id'] = null;
      $validated['client_id'] = null;
      return $validated;
    }

    $leadName = trim((string) ($validated['lead_name'] ?? ''));
    $leadEmail = trim((string) ($validated['lead_email'] ?? ''));
    $leadPhone = trim((string) ($validated['lead_phone'] ?? ''));
    $leadCompany = trim((string) ($validated['lead_company'] ?? ''));

    $companyId = null;
    if ($leadCompany !== '') {
      $company = ClientCompany::firstOrCreate(
        ['tenant_id' => $tenant->id, 'company_name' => $leadCompany],
        ['tenant_id' => $tenant->id, 'company_name' => $leadCompany]
      );
      $companyId = $company->id;
    }

    $leadQuery = Lead::query()->where('tenant_id', $tenant->id);
    if ($leadEmail !== '') {
      $leadQuery->where('email', $leadEmail);
    }
    $lead = $leadQuery->first();

    if ($lead) {
      $lead->fill([
        'name' => $leadName ?: $lead->name,
        'email' => $leadEmail ?: $lead->email,
        'phone' => $leadPhone ?: $lead->phone,
        'company_id' => $companyId ?: $lead->company_id,
      ])->save();
    } else {
      $lead = Lead::create([
        'tenant_id' => $tenant->id,
        'name' => $leadName,
        'email' => $leadEmail ?: null,
        'phone' => $leadPhone ?: null,
        'company_id' => $companyId,
        'status' => 'new',
      ]);
    }

    $validated['lead_id'] = $lead->id;
    $validated['contact_id'] = null;
    $validated['client_id'] = null;
    $validated['recipient_type'] = 'new_lead';

    return $validated;
  }
}
