<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use App\Models\ContactActivity;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Support\Str;
use App\Mail\ClientCredentialsMailable;
use App\Models\Client;
use App\Models\User;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\ContactNote;
use App\Models\ContactFile;
use App\Models\Activity;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\EmailLog;
use App\Models\UserMailAccount;
use App\Models\Upload;
use App\Models\ProjectMessage;
use App\Models\MagicLink;
use App\Mail\ClientMagicLinkMailable;



class ClientController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth')->except(['portal', 'viewProjectDetails', 'viewTaskComments', 'formThankYou']);
    $this->middleware('auth:client')->only(['portal', 'viewProjectDetails', 'viewTaskComments', 'formThankYou']);
  }


  public function index(Tenant $tenant)
  {
    // If you're using a policy, this ties into ClientPolicy::viewAny
    $this->authorize('viewAny', [Client::class, $tenant]);

    $statusFilter = request('status', '');
    $loginFilter = request('login', '');
    $search = request('search', '');
    $sort = request('sort', 'name_asc');

    // Fetch all clients for this tenant
    $clients = Client::query()
      ->where('tenant_id', $tenant->id)
      ->when($search, function ($q) use ($search) {
        $q->where(function ($w) use ($search) {
          $w->where('firstName', 'like', "%{$search}%")
            ->orWhere('lastName', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        });
      })
      ->when($statusFilter !== '', fn($q) => $q->where('status', $statusFilter))
      ->when($loginFilter === 'yes', fn($q) => $q->whereHas('userAccount'))
      ->when($loginFilter === 'no', fn($q) => $q->whereDoesntHave('userAccount'))
      ->with(['company:id,company_name'])
      ->withCount('projects')
      ->when($sort === 'name_desc', fn($q) => $q->orderByDesc('lastName')->orderByDesc('firstName'))
      ->when($sort === 'updated', fn($q) => $q->orderByDesc('updated_at'))
      ->when($sort === 'name_asc', fn($q) => $q->orderBy('lastName')->orderBy('firstName'))
      ->paginate(25)
      ->appends(request()->only(['search', 'status', 'login', 'sort']));

    return view('contacts.index', [
      'tenant'   => $tenant,
      'clients'  => $clients,
      'contacts' => $clients,
    ]);
  }


  /** GET /{tenant}/contacts/create */
  public function create(Tenant $tenant)
  {
    $companyId = request('company');
    $selectedCompany = null;

    if ($companyId) {
      $selectedCompany = \App\Models\ClientCompany::where('tenant_id', $tenant->id)
        ->find($companyId);
    }

    $contact = new \App\Models\Contact([
      'tenant_id'         => $tenant->id,
      'client_company_id' => $selectedCompany?->id,
    ]);

    $companies = \App\Models\ClientCompany::where('tenant_id', $tenant->id)
      ->orderBy('company_name')
      ->get();

    $portalMessagePalette = $this->portalMessagePalette($tenant);

    return view('contacts.create', compact('tenant', 'contact', 'companies', 'selectedCompany', 'portalMessagePalette'));
  }


  /** POST /{tenant}/contacts */
  public function store(Request $request, Tenant $tenant)
  {
    $this->authorize('create', Client::class);

    $portalPalette = $this->portalMessagePalette($tenant);

    $data = $request->validate([
      'firstName' => 'required|string|max:255',
      'lastName'  => 'required|string|max:255',
      'email'     => [
        'required',
        'email',
        Rule::unique('clients', 'email')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'client_company_id' => [
        'nullable',
        Rule::exists('client_companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'phone'     => 'nullable|string|max:50',
      'status'    => ['required', Rule::in(['active', 'inactive'])],
      'notes'     => 'nullable|string',
      'portal_client_message_color' => [
        'nullable',
        'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/',
        Rule::in($portalPalette['client']),
      ],
    ]);

    $data['tenant_id'] = $tenant->id;
    $data['status']    = $data['status'] ?? 'active';
    if (! empty($data['portal_client_message_color'])) {
      $data['portal_team_message_color'] = $portalPalette['teamMap'][strtolower($data['portal_client_message_color'])] ?? null;
    }

    $client = Client::create($data);

    activity()
      ->useLog('client')
      ->performedOn($client)
      ->causedBy(Auth::user())
      ->log("client_created: {$client->firstName} {$client->lastName}");

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact created successfully!');
  }

  /** GET /{tenant}/contacts/{contact} */
  public function show(Tenant $tenant, Client $contact)
  {
    $this->authorize('view', $contact);

    $contact->load([
      'userAccount',
      'contactFiles.uploader',
      'notes.author',
    ])->loadCount(['projects']);

    $companyId = $contact->client_company_id;
    $projects = Project::query()
      ->where('tenant_id', $tenant->id)
      ->where(function ($q) use ($contact, $companyId) {
        $q->where('contact_id', $contact->id);
        if ($companyId) {
          $q->orWhere('client_company_id', $companyId)
            ->orWhereIn('contact_id', function ($sub) use ($companyId) {
              $sub->select('id')
                ->from('contacts')
                ->where('client_company_id', $companyId);
            });
        }
      })
      ->with([
        'phases',
        'agreements',
        'payments',
      ])
      ->latest('updated_at')
      ->get();

    // Simple stats (fallback-safe)
    $openProjects = $projects->whereNotIn('status', ['closed', 'archived'])->count();
    $unpaid = optional($projects)->flatMap->payments?->where('status', 'unpaid')->sum('amount') ?? null;

    $lastActivity = $contact->updated_at;
    if (method_exists($contact, 'activities')) {
      $lastActivity = optional($contact->activities()->latest()->first())->created_at ?? $lastActivity;
    }

    $nextFollowup = $contact->next_followup_at ?? null;

    $notes = $contact->notes()->orderByDesc('pinned')->latest()->get();
    $files = $contact->contactFiles()->latest()->get();
    $activities = $contact->contactActivities()->latest('happened_at')->latest()->take(15)->get();

    // Recent emails (matched to this contact)
    $recentEmails = EmailLog::query()
      ->where('tenant_id', $tenant->id)
      ->where('contact_id', $contact->id)
      ->orderByDesc('sent_at')
      ->orderByDesc('created_at')
      ->take(5)
      ->get();

    // Mailbox context for current user
    $currentMailbox = UserMailAccount::query()
      ->where('tenant_id', $tenant->id)
      ->where('user_id', auth()->id())
      ->first();

    $gmailConfigured = (bool) (config('services.google.enable_sync') ?? false) &&
      config('services.google.client_id') &&
      config('services.google.client_secret') &&
      config('services.google.redirect');

    $magicLink = null;
    $magicLinkDaysLeft = null;
    if ($contact->userAccount) {
      $magicLink = MagicLink::where('user_id', $contact->userAccount->id)
        ->orderByDesc('expires_at')
        ->first();
      if ($magicLink?->expires_at) {
        $magicLinkDaysLeft = now()->diffInDays($magicLink->expires_at, false);
      }
    }

    return view('contacts.view', [
      'tenant'  => $tenant->id,
      'tenant_workspace_type' => $tenant->workspace_type,
      'client'  => $contact,
      'kpi_open_projects' => $openProjects,
      'kpi_unpaid_balance' => $unpaid,
      'kpi_last_activity' => $lastActivity ? $lastActivity->diffForHumans() : null,
      'kpi_next_due' => $nextFollowup,
      'projects' => $projects,
      'notes' => $notes,
      'files' => $files,
      'activities' => $activities,
      'recentEmails' => $recentEmails,
      'gmailConfigured' => $gmailConfigured,
      'currentMailbox' => $currentMailbox,
      'magicLink' => $magicLink,
      'magicLinkDaysLeft' => $magicLinkDaysLeft,
    ]);
  }

  /** POST /contacts/{contact}/notes */
  public function storeNote(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $data = $request->validate([
      'body' => 'required|string|min:2|max:2000',
      'type' => 'nullable|string|max:50',
      'followup_at' => 'nullable|date',
      'pinned' => 'sometimes|boolean',
    ]);

    $note = ContactNote::create([
      'tenant_id' => $tenant->id,
      'contact_id' => $contact->id,
      'created_by' => auth()->id(),
      'type' => $data['type'] ?? null,
      'body' => $data['body'],
      'pinned' => (bool) ($data['pinned'] ?? false),
      'next_followup_at' => $data['followup_at'] ?? null,
    ]);

    if (!empty($data['followup_at'])) {
      $contact->next_followup_at = $data['followup_at'];
      $contact->save();
    }

    return back()->with('success_message', 'Note added.');
  }

  /** POST /contacts/{contact}/notes/{note}/pin */
  public function togglePin(Tenant $tenant, Client $contact, ContactNote $note)
  {
    $this->authorize('update', $contact);
    abort_unless($note->contact_id === $contact->id, 404);

    $note->pinned = !$note->pinned;
    $note->save();

    return back()->with('success_message', $note->pinned ? 'Note pinned.' : 'Note unpinned.');
  }

  /** POST /contacts/{contact}/followup */
  public function updateFollowup(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $data = $request->validate([
      'next_followup_at' => 'required|date',
    ]);

    $contact->next_followup_at = $data['next_followup_at'];
    $contact->save();

    ContactActivity::create([
      'tenant_id' => $tenant->id,
      'contact_id' => $contact->id,
      'actor_id' => auth()->id(),
      'type' => 'followup.updated',
      'meta' => ['next_followup_at' => $data['next_followup_at']],
      'happened_at' => $data['next_followup_at'],
    ]);

    return back()->with('success_message', 'Follow-up updated.');
  }

  /** POST /contacts/{contact}/files */
  public function storeFile(Request $request, Tenant $tenant, Client $contact)
  {
    abort(404);
  }

  /** DELETE /contacts/{contact}/files/{file} */
  public function destroyFile(Tenant $tenant, Client $contact, ContactFile $file)
  {
    abort(404);
  }

  /** GET /{tenant}/contacts/{contact}/edit */
  public function edit(Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    return view('contacts.edit', [
      'tenant' => $tenant->id,
      'client' => $contact,
      'companies' => \App\Models\ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
      'portalMessagePalette' => $this->portalMessagePalette($tenant),
    ]);
  }

  /** PUT/PATCH /{tenant}/contacts/{contact} */
  public function update(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $portalPalette = $this->portalMessagePalette($tenant);

    $data = $request->validate([
      'firstName' => 'required|string|max:255',
      'lastName'  => 'required|string|max:255',
      'email'     => [
        'required',
        'email',
        Rule::unique('clients', 'email')
          ->ignore($contact->id)
          ->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'client_company_id' => [
        'nullable',
        Rule::exists('client_companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
      ],
      'phone'     => 'nullable|string|max:50',
      'status'    => ['required', Rule::in(['active', 'inactive'])],
      'notes'     => 'nullable|string',
      'portal_client_message_color' => [
        'nullable',
        'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/',
        Rule::in($portalPalette['client']),
      ],
    ]);

    if (array_key_exists('portal_client_message_color', $data) && $data['portal_client_message_color'] === '') {
      $data['portal_client_message_color'] = null;
    }

    if (! empty($data['portal_client_message_color'])) {
      $data['portal_team_message_color'] = $portalPalette['teamMap'][strtolower($data['portal_client_message_color'])] ?? null;
    } else {
      $data['portal_team_message_color'] = null;
    }

    $contact->update($data);

    activity()
      ->useLog('client')
      ->performedOn($contact)
      ->causedBy(Auth::user())
      ->log("client_updated: {$contact->firstName} {$contact->lastName}");

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact updated successfully!');
  }

  private function portalMessagePalette(Tenant $tenant): array
  {
    $primary = $tenant->brandColorHex('primary') ?? '#1C2E70';
    $secondary = $tenant->brandColorHex('secondary') ?? '#172554';
    $accent = $tenant->brandColorHex('accent') ?? '#8FAF9A';
    $neutral = $tenant->brandColorHex('neutral') ?? '#3A3F4B';

    $mixHex = function (string $hexA, string $hexB, float $ratio): string {
      $hexA = ltrim($hexA, '#');
      $hexB = ltrim($hexB, '#');
      if (strlen($hexA) === 3) {
        $hexA = $hexA[0] . $hexA[0] . $hexA[1] . $hexA[1] . $hexA[2] . $hexA[2];
      }
      if (strlen($hexB) === 3) {
        $hexB = $hexB[0] . $hexB[0] . $hexB[1] . $hexB[1] . $hexB[2] . $hexB[2];
      }
      $a = hexdec($hexA);
      $b = hexdec($hexB);
      $ar = ($a >> 16) & 255;
      $ag = ($a >> 8) & 255;
      $ab = $a & 255;
      $br = ($b >> 16) & 255;
      $bg = ($b >> 8) & 255;
      $bb = $b & 255;
      $r = (int) round($ar * (1 - $ratio) + $br * $ratio);
      $g = (int) round($ag * (1 - $ratio) + $bg * $ratio);
      $bl = (int) round($ab * (1 - $ratio) + $bb * $ratio);
      return sprintf('#%02x%02x%02x', $r, $g, $bl);
    };

    $base = [
      $primary,
      $secondary,
      $accent,
      $neutral,
      $mixHex($primary, $accent, 0.5),
      $mixHex($secondary, $accent, 0.5),
    ];

    $base = array_values(array_unique(array_map('strtolower', $base)));
    $light = array_map(fn($hex) => $mixHex($hex, '#ffffff', 0.82), $base);

    $teamMap = [];
    foreach ($base as $idx => $color) {
      $teamMap[strtolower($color)] = $light[$idx] ?? $light[0] ?? '#e6eaf2';
    }

    return [
      'client' => $base,
      'team' => $light,
      'teamMap' => $teamMap,
    ];
  }

  /** DELETE /{tenant}/contacts/{contact} */
  public function destroy(Tenant $tenant, Client $contact)
  {
    $this->authorize('delete', $contact);

    if ($contact->tenant_id !== $tenant->id) {
      abort(404);
    }

    $name = trim("{$contact->firstName} {$contact->lastName}") ?: ($contact->email ?? 'Contact');
    $companyId = $contact->client_company_id ?? null;

    ActivityLog::record(
      $tenant->id,
      auth()->id(),
      $contact,
      'contact.deleted',
      'Contact deleted',
      [
        'contact_name' => $name,
        'contact_id' => $contact->id,
        'company_id' => $companyId,
      ]
    );

    $contact->delete();

    $referer = url()->previous();
    if ($companyId) {
      $companyUrl = route('tenant.companies.show', ['tenant' => $tenant->id, 'company' => $companyId]);
      if (Str::startsWith($referer, $companyUrl)) {
        return redirect()
          ->route('tenant.companies.show', ['tenant' => $tenant->id, 'company' => $companyId])
          ->with('success_message', 'Contact deleted successfully!');
      }
    }

    if (Str::contains($referer, '/contacts/') && Str::endsWith($referer, (string) $contact->id)) {
      return redirect()
        ->route('tenant.contacts.index', ['tenant' => $tenant->id])
        ->with('success_message', 'Contact deleted successfully!');
    }

    return redirect()->route('tenant.contacts.index', ['tenant' => $tenant->id])
      ->with('success_message', 'Contact deleted successfully!');
  }

  // -------- Client Portal --------
  public function portal()
  {
    // Authenticated client user
    $user = Auth::guard('client')->user();

    // This is the foreign key pointing at the contacts/clients table
    $contactId = $user->contact_id;

    Log::info('[client.portal] resolving client for portal', [
      'user_id'    => $user->id,
      'contact_id' => $contactId,
      'tenant_id'  => $user->tenant_id ?? null,
    ]);

    if (! $contactId) {
      // No contact attached at all – bail in a friendly way
      return response()->view('clients.missing-client', [
        'user' => $user,
      ], 404);
    }

    // Look up the client/contact row, enforcing tenant
    $client = Client::query()
      ->where('tenant_id', $user->tenant_id)
      ->where('id', $contactId)
      ->with(['uploads', 'tenant'])
      ->first();

    if (! $client) {
      Log::warning('[client.portal] no client row found for user', [
        'user_id'    => $user->id,
        'contact_id' => $contactId,
        'tenant_id'  => $user->tenant_id ?? null,
      ]);

      return response()->view('clients.missing-client', [
        'user' => $user,
      ], 404);
    }

    $companyId = $client->client_company_id;
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
      ->with([
        'phases',
        'tasks' => function ($q) {
          $q->where('assign_type', 'client')
            ->orWhere('requires_approval', true)
            ->orWhere('client_visible', true);
        },
      ])
      ->latest('updated_at')
      ->get();

    $client->setRelation('projects', $projects);

    // Related collections
    $activeProjects    = $projects->whereIn('status', ['open', 'active', 'in_progress', 'in-progress']);
    $completedProjects = $projects->whereIn('status', ['closed', 'completed']);
    $uploads           = $client->uploads ?? collect();
    $tenant            = $client->tenant;

    // Recent invoices for this contact (adjust limit as you like)
    $invoices = Invoice::query()
      ->where('contact_id', $contactId)
      ->latest('issue_date')
      ->take(5)
      ->with('lineItems')
      ->get();

    // Name / initials (adjust field names if yours are snake_case)
    $firstName = $client->firstName ?? $client->first_name ?? '';
    $lastName  = $client->lastName ?? $client->last_name ?? '';

    $fullName = trim($firstName . ' ' . $lastName);
    $initials = strtoupper(
      mb_substr($firstName, 0, 1) .
        mb_substr($lastName, 0, 1)
    );

    // Count open/in-progress tasks
    $openTasksCount = $projects
      ->pluck('tasks')
      ->flatten()
      ->whereIn('status', ['open', 'in-progress', 'in_progress', 'todo', 'working', 'doing'])
      ->count();

    $activityRows = collect();
    $projectIds = $projects->pluck('id')->filter()->values();

    if ($projectIds->isNotEmpty()) {
      $taskActivity = \App\Models\Task::query()
        ->whereIn('project_id', $projectIds)
        ->where(function ($q) {
          $q->where('assign_type', 'client')
            ->orWhere('requires_approval', true)
            ->orWhere('client_visible', true);
        })
        ->latest('updated_at')
        ->take(6)
        ->get()
        ->toBase()
        ->map(fn($task) => [
          'type' => 'task',
          'label' => 'Task update: ' . ($task->title ?? 'Task'),
          'when' => optional($task->updated_at)->diffForHumans(),
          'at' => $task->updated_at,
        ]);

      $projectActivity = \App\Models\Project::query()
        ->whereIn('id', $projectIds)
        ->latest('updated_at')
        ->take(4)
        ->get()
        ->toBase()
        ->map(fn($project) => [
          'type' => 'project',
          'label' => 'Project update: ' . ($project->project_name ?? 'Project'),
          'when' => optional($project->updated_at)->diffForHumans(),
          'at' => $project->updated_at,
        ]);

      $activityRows = collect($taskActivity)
        ->merge($projectActivity)
        ->sortByDesc(fn($row) => $row['at'] ?? null)
        ->take(8)
        ->values();
    }

    $fileActivity = Upload::query()
      ->where('tenant_id', $tenant->id)
      ->where('contact_id', $contactId)
      ->latest('created_at')
      ->take(4)
      ->get()
      ->toBase()
      ->map(fn($file) => [
        'type' => 'file',
        'label' => 'File shared: ' . ($file->original_name ?? 'File'),
        'when' => optional($file->created_at)->diffForHumans(),
        'at' => $file->created_at,
      ]);

    $messageActivity = collect();
    if ($projectIds->isNotEmpty()) {
      $conversationIds = \App\Models\ProjectConversation::whereIn('project_id', $projectIds)->pluck('id');
      if ($conversationIds->isNotEmpty()) {
        $messageActivity = ProjectMessage::query()
          ->whereIn('conversation_id', $conversationIds)
          ->where('sender_type', 'tenant')
          ->latest('created_at')
          ->take(4)
          ->get()
          ->toBase()
          ->map(fn($message) => [
            'type' => 'note',
            'label' => 'New message from your team',
            'when' => optional($message->created_at)->diffForHumans(),
            'at' => $message->created_at,
          ]);
      }
    }

    $invoiceActivity = $invoices
      ->toBase()
      ->map(fn($invoice) => [
        'type' => 'invoice',
        'label' => 'Invoice issued: ' . ($invoice->number ?? ('INV-' . $invoice->id)),
        'when' => optional($invoice->issue_date ?? $invoice->created_at)->diffForHumans(),
        'at' => $invoice->issue_date ?? $invoice->created_at,
      ])
      ->take(4);

    $activities = collect($activityRows)
      ->merge($invoiceActivity)
      ->merge($fileActivity)
      ->merge($messageActivity)
      ->sortByDesc(fn($row) => $row['at'] ?? null)
      ->take(10)
      ->values();

    return view('portal.dashboard', [
      'user'              => $user,
      'client'            => $client,
      'fullName'          => $fullName,
      'initials'          => $initials,
      'tenant'            => $tenant,
      'activeProjects'    => $activeProjects,
      'completedProjects' => $completedProjects,
      'uploads'           => $uploads,
      'openTasksCount'    => $openTasksCount,
      'invoices'          => $invoices,
      'activities'        => $activities,

    ]);
  }

  // Admin: create login for a contact
  public function createLogin(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    if ($contact->userAccount) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('error_message', 'A login already exists for this contact.');
    }

    $data = $request->validate([
      'email' => ['required', 'email', Rule::unique('users', 'email')],
      'password' => ['nullable', 'string', 'min:8'],
    ]);

    $tempPassword = $data['password'] ?? Str::random(12);

    $user = new User();
    $user->tenant_id = $tenant->id;
    $user->contact_id = $contact->id;
    $user->role = 'client';
    $user->email = $data['email'];
    $user->password = Hash::make($tempPassword);
    $user->first_name = $contact->firstName ?? $contact->first_name ?? null;
    $user->last_name = $contact->lastName ?? $contact->last_name ?? null;

    $user->save();

    try {
      Mail::to($user->email)->send(new ClientCredentialsMailable($contact, $tempPassword));

      activity()
        ->useLog('client')
        ->performedOn($contact)
        ->causedBy(Auth::user())
        ->log("client_login_created: Created portal login for {$user->email}");

      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('success_message', 'Login created and emailed to the contact.');
    } catch (\Exception $e) {
      Log::error("Failed to send client credentials email: " . $e->getMessage());
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('error_message', 'Login created, but email failed to send.');
    }
  }



  // Admin: resend login email
  // Route as: POST /{tenant}/contacts/{contact}/resend-login
  public function resendLoginEmail(Tenant $tenant, int $clientId)
  {
    $client = Client::where('tenant_id', $tenant->id)->findOrFail($clientId);

    $this->authorize('update', $client);

    $userAccount = $client->userAccount;

    if (!$userAccount) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'No user account found for this client.');
    }

    if (method_exists($userAccount, 'hasLoggedIn') && $userAccount->hasLoggedIn($userAccount->id)) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'User has already logged in — email not resent.');
    }

    $tempPassword = Str::random(12);
    $userAccount->password = Hash::make($tempPassword);
    // $userAccount->requires_password_change = true;
    $userAccount->save();

    try {
      Mail::to($userAccount->email)->send(new ClientCredentialsMailable($client, $tempPassword));

      activity()
        ->useLog('client')
        ->performedOn($client)
        ->causedBy(Auth::user())
        ->log("client_credentials_resent: Resent login email to {$userAccount->email}");

      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('success_message', 'Login email resent successfully.');
    } catch (\Exception $e) {
      Log::error("Failed to send client credentials email: " . $e->getMessage());
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $clientId])
        ->with('error_message', 'Failed to send login email. Check logs.');
    }
  }

  // Admin: send magic link to a client portal user
  public function sendMagicLink(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $userAccount = $contact->userAccount;
    if (! $userAccount) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('error_message', 'No portal login exists for this contact.');
    }

    $token = Str::random(64);
    $hash = hash('sha256', $token);

    MagicLink::create([
      'user_id' => $userAccount->id,
      'token_hash' => $hash,
      'expires_at' => now()->addDays(7),
      'ip' => $request->ip(),
      'user_agent' => substr((string) $request->userAgent(), 0, 1000),
    ]);

    $link = route('portal.magic.consume', ['token' => $token]);
    Mail::to($userAccount->email)->send(new ClientMagicLinkMailable($userAccount, $link));

    return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
      ->with('success_message', 'Magic link sent to the client.')
      ->with('magic_link_url', $link);
  }

  public function viewTaskComments(Request $request, int $taskId)
  {
    $task = Task::findOrFail($taskId);
    $this->authorize('view', $task);

    $comments = $task->comments()->orderBy('created_at', 'asc')->get();

    if ($request->ajax()) {
      return view('clients.partials.comments-list', compact('comments'));
    }
    return view('clients.task-comments', compact('task', 'comments'));
  }


  public function viewProjectDetails($projectId)
  {
    $user = Auth::guard('client')->user();
    if (!$user || $user->role !== 'client' || !$user->contact_id) {
      return redirect()->route('portal.dashboard');
    }

    $clientId = (int) $user->contact_id;

    $client = Client::where('tenant_id', $user->tenant_id)
      ->where('id', $clientId)
      ->firstOrFail();

    $project = Project::with(['phases', 'tasks'])->findOrFail($projectId);
    Gate::authorize('portal-view-project', $project);

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

    $clientVisibleTasks = $project->tasks->filter(function ($task) use ($clientId) {
      $assignedByContact = (int) ($task->contact_id ?? 0) === $clientId;
      $assignedByAssign = ($task->assign_type ?? '') === 'client'
        && (int) ($task->assign_id ?? 0) === $clientId;
      $clientVisible = (bool) ($task->client_visible ?? false);
      $needsApproval = (bool) ($task->requires_approval ?? false)
        || in_array(($task->approval_status ?? ''), ['needs_approval', 'awaiting_approval', 'approval'], true);
      $title = strtolower((string) ($task->title ?? ''));
      $titleTagged = str_starts_with($title, 'client:');

      return $assignedByContact || $assignedByAssign || $clientVisible || $needsApproval || $titleTagged;
    });

    $phaseGroups = $project->phases
      ->map(function ($phase) use ($clientVisibleTasks) {
        $tasks = $clientVisibleTasks->where('phase_id', $phase->id)->values();
        return [
          'id'         => $phase->id,
          'name'       => $phase->name,
          'tasks'      => $tasks,
          'sort_order' => $phase->sort_order,
        ];
      })
      ->sortBy('sort_order');

    $unassignedTasks = $clientVisibleTasks->whereNull('phase_id')->values();
    if ($unassignedTasks->isNotEmpty()) {
      $phaseGroups->push([
        'id'         => -1,
        'name'       => 'Other',
        'tasks'      => $unassignedTasks,
        'sort_order' => PHP_INT_MAX,
      ]);
    }

    $currentPhase = $phaseGroups->first(function ($group) {
      return collect($group['tasks'])->contains(function ($task) {
        return in_array($task->status, ['open', 'in-progress'], true);
      });
    });

    if (!$currentPhase) {
      $currentPhase = $phaseGroups->filter(fn($g) => collect($g['tasks'])->isNotEmpty())->last();
    }

    return view('portal.projects.show', [
      'project'      => $project,
      'phaseGroups'  => $phaseGroups->values(),
      'currentPhase' => $currentPhase,
    ]);
  }

  public function createMagicLinkTask(Request $request, Tenant $tenant, Client $contact)
  {
    $this->authorize('update', $contact);

    $userAccount = $contact->userAccount;
    if (! $userAccount) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('error_message', 'No portal login exists for this contact.');
    }

    $latestLink = MagicLink::where('user_id', $userAccount->id)
      ->orderByDesc('expires_at')
      ->first();

    $dueDate = $latestLink?->expires_at ?? now()->addDays(7);
    $title = 'Send new portal link to ' . trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));

    $existing = Task::query()
      ->where('tenant_id', $tenant->id)
      ->where('contact_id', $contact->id)
      ->where('title', $title)
      ->whereIn('status', ['todo', 'in_progress'])
      ->first();

    if ($existing) {
      return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
        ->with('success_message', 'Task already exists for this contact.');
    }

    Task::create([
      'tenant_id' => $tenant->id,
      'user_id' => Auth::id(),
      'contact_id' => $contact->id,
      'title' => $title,
      'description' => 'Magic link expires soon. Send a fresh portal link to keep access active.',
      'status' => 'todo',
      'priority' => 'medium',
      'due_date' => $dueDate,
    ]);

    return redirect()->route('tenant.contacts.show', ['tenant' => $tenant->id, 'contact' => $contact->id])
      ->with('success_message', 'Task created for the admin.');
  }

  public function formThankYou(Request $request)
  {
    $taskId = $request->query('task_id');
    return view('clients.form-thank-you', ['task_id' => $taskId]);
  }
}
