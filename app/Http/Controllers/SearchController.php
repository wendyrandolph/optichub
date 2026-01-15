<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\TeamMember;
use App\Models\Task;
use App\Http\Requests\SearchRequest; // NEW: For input validation
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;

class SearchController extends Controller
{
  /**
   * ReportController constructor.
   */
  public function __construct()
  {
    // Enforce authentication for the search feature
    $this->middleware('auth');
  }

  /**
   * Executes the search and displays results.
   * Replaces the old index() method.
   *
   * @param \App\Http\Requests\SearchRequest $request
   * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
   */
  public function index(SearchRequest $request)
  {
    // Validation handled by SearchRequest, ensuring 'q' is not empty and is trimmed.
    $query = $request->validated('q');

    // Note: The SearchRequest already handles the case where 'q' is empty
    // by redirecting back with an error, so the manual empty check is removed.

    // Use Eloquent scopes for searching, leveraging the ORM.
    $clients = Client::search($query)->get();
    $projects = Project::search($query)->get();
    $tasks = Task::search($query)->get();

    return view('search.results', compact('query', 'clients', 'projects', 'tasks'));
  }

  /**
   * Lightweight JSON search for the quick-search modal.
   */
  public function quick(Request $request, ?\App\Models\Tenant $tenant = null)
  {
    $q = trim((string) $request->query('q', ''));
    if (mb_strlen($q) < 2) {
      return response()->json(['results' => []]);
    }

    // Resolve user from any guard (admin > default > client)
    $user = auth('admin')->user() ?? auth()->user() ?? auth('client')->user();
    $tenantId = $tenant?->id
      ?? (is_numeric($request->route('tenant')) ? (int) $request->route('tenant') : null)
      ?? ($request->route('tenant') instanceof \App\Models\Tenant ? $request->route('tenant')->id : null)
      ?? (int) $request->query('tenant', 0)
      ?: ($user?->tenant_id ?? null);

    // Treat provider + admin level roles as allowed to search across tenants when no tenant is specified
    $isProvider = in_array($user?->role, ['provider', 'super_admin', 'superadmin', 'admin'], true);

    $limit = 5;

    $projects = Project::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where(function ($sub) use ($q) {
        $sub->where('project_name', 'like', "%{$q}%")
          ->orWhere('description', 'like', "%{$q}%");
      })
      ->orderByDesc('updated_at')
      ->limit($limit)
      ->get(['id', 'project_name', 'description', 'tenant_id']);

    $companies = ClientCompany::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where('company_name', 'like', "%{$q}%")
      ->orderBy('company_name')
      ->limit($limit)
      ->get(['id', 'company_name', 'industry', 'website', 'tenant_id']);

    $tasks = Task::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where(function ($sub) use ($q) {
        $sub->where('title', 'like', "%{$q}%")
          ->orWhere('description', 'like', "%{$q}%");
      })
      ->orderByDesc('updated_at')
      ->limit($limit)
      ->get(['id', 'title', 'status', 'project_id', 'tenant_id']);

    $invoices = Invoice::query()
      ->when($tenantId, fn($q2) => $q2->where('invoices.tenant_id', $tenantId))
      ->leftJoin('contacts as c', 'invoices.contact_id', '=', 'c.id')
      ->where(function ($sub) use ($q) {
        $sub->where('invoice_number', 'like', "%{$q}%")
          ->orWhere('c.firstName', 'like', "%{$q}%")
          ->orWhere('c.lastName', 'like', "%{$q}%");
      })
      ->orderByDesc('invoices.updated_at')
      ->limit($limit)
      ->get([
        'invoices.id',
        'invoices.tenant_id',
        'invoices.invoice_number',
        'invoices.status as invoice_status',
        'invoices.total_amount',
        'invoices.sent_at',
        'invoices.contact_id',
        'c.firstName',
        'c.lastName'
      ]);

    $leads = Lead::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where(function ($sub) use ($q) {
        $sub->where('name', 'like', "%{$q}%")
          ->orWhere('first_name', 'like', "%{$q}%")
          ->orWhere('last_name', 'like', "%{$q}%")
          ->orWhere('email', 'like', "%{$q}%");
      })
      ->orderByDesc('updated_at')
      ->limit($limit)
      ->get(['id', 'name', 'first_name', 'last_name', 'email', 'status', 'tenant_id']);

    $messages = ProjectMessage::query()
      ->whereHas('conversation.project', function ($sub) use ($tenantId) {
        if ($tenantId) {
          $sub->where('tenant_id', $tenantId);
        }
      })
      ->where('body', 'like', "%{$q}%")
      ->with(['conversation.project:id,tenant_id,project_name'])
      ->orderByDesc('created_at')
      ->limit($limit)
      ->get(['id', 'conversation_id', 'body', 'created_at']);

    $contacts = Client::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where(function ($sub) use ($q) {
        $sub->where('firstName', 'like', "%{$q}%")
          ->orWhere('lastName', 'like', "%{$q}%")
          ->orWhere('email', 'like', "%{$q}%");
      })
      ->orderBy('lastName')
      ->limit($limit)
      ->get(['id', 'firstName', 'lastName', 'email', 'tenant_id']);

    $teamMembers = TeamMember::query()
      ->when($tenantId, fn($q2) => $q2->where('tenant_id', $tenantId))
      ->where(function ($sub) use ($q) {
        $sub->where('firstName', 'like', "%{$q}%")
          ->orWhere('lastName', 'like', "%{$q}%")
          ->orWhere('email', 'like', "%{$q}%")
          ->orWhere('title', 'like', "%{$q}%");
      })
      ->orderBy('lastName')
      ->limit($limit)
      ->get(['id', 'firstName', 'lastName', 'email', 'title', 'tenant_id']);

    $results = [];

    foreach ($projects as $project) {
      $tid = $tenantId ?: $project->tenant_id;
      if (! $tid) continue;
      $results[] = [
        'type'   => 'project',
        'id'     => $project->id,
        'title'  => $project->project_name,
        'subtitle' => $project->description ? mb_strimwidth($project->description, 0, 80, '…') : 'Project',
        'url'    => route('tenant.projects.show', ['tenant' => $tid, 'project' => $project->id]),
      ];
    }

    foreach ($companies as $company) {
      $tid = $tenantId ?: $company->tenant_id;
      if (! $tid) continue;
      $results[] = [
        'type'   => 'company',
        'id'     => $company->id,
        'title'  => $company->company_name,
        'subtitle' => $company->industry ?: ($company->website ?: 'Client company'),
        'url'    => route('tenant.companies.show', ['tenant' => $tid, 'company' => $company->id]),
      ];
    }

    foreach ($tasks as $task) {
      $tid = $tenantId ?: $task->tenant_id;
      if (! $tid) continue;
      $results[] = [
        'type'   => 'task',
        'id'     => $task->id,
        'title'  => $task->title,
        'subtitle' => strtoupper($task->status ?? 'todo'),
        'url'    => route('tenant.tasks.show', ['tenant' => $tid, 'task' => $task->id]),
      ];
    }

    foreach ($invoices as $invoice) {
      $tid = $tenantId ?: $invoice->tenant_id;
      if (! $tid) continue;
      $name = trim(($invoice->firstName ?? '') . ' ' . ($invoice->lastName ?? ''));
      $results[] = [
        'type'   => 'invoice',
        'id'     => $invoice->id,
        'title'  => $invoice->invoice_number ?: 'Invoice #' . $invoice->id,
        'subtitle' => trim(($invoice->invoice_status ?? $invoice->status ?? '') . ' ' . ($name ? "• {$name}" : '')),
        'url'    => route('tenant.invoices.show', ['tenant' => $tid, 'invoice' => $invoice->id]),
      ];
    }

    foreach ($leads as $lead) {
      $tid = $tenantId ?: $lead->tenant_id;
      if (! $tid) continue;
      $leadName = $lead->name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
      $results[] = [
        'type'   => 'lead',
        'id'     => $lead->id,
        'title'  => $leadName ?: 'Lead',
        'subtitle' => $lead->email ?: ($lead->status ?? 'Lead'),
        'url'    => route('tenant.leads.show', ['tenant' => $tid, 'lead' => $lead->id]),
      ];
    }

    foreach ($contacts as $contact) {
      $tid = $tenantId ?: $contact->tenant_id;
      if (! $tid) continue;
      $name = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? '')) ?: 'Contact';
      $results[] = [
        'type'   => 'contact',
        'id'     => $contact->id,
        'title'  => $name,
        'subtitle' => $contact->email ?? 'Contact',
        'url'    => route('tenant.contacts.show', ['tenant' => $tid, 'contact' => $contact->id]),
      ];
    }

    foreach ($teamMembers as $member) {
      $tid = $tenantId ?: $member->tenant_id;
      if (! $tid) continue;
      $name = trim(($member->firstName ?? '') . ' ' . ($member->lastName ?? '')) ?: 'Team member';
      $results[] = [
        'type'   => 'team_member',
        'id'     => $member->id,
        'title'  => $name,
        'subtitle' => $member->email ?: ($member->title ?? 'Team member'),
        'url'    => route('tenant.team-members.show', ['tenant' => $tid, 'team_member' => $member->id]),
      ];
    }

    foreach ($messages as $msg) {
      $project = $msg->conversation?->project;
      if (! $project) {
        continue;
      }
      $tid = $tenantId ?: $project->tenant_id;
      if (! $tid) continue;
      $snippet = mb_strimwidth(strip_tags($msg->body ?? ''), 0, 80, '…');
      $results[] = [
        'type'   => 'message',
        'id'     => $msg->id,
        'title'  => $project->project_name ?? 'Project message',
        'subtitle' => $snippet ?: 'Message',
        'url'    => route('tenant.projects.show', ['tenant' => $tid, 'project' => $project->id]) . '#messages',
      ];
    }

    return response()->json([
      'results' => array_slice($results, 0, 15),
    ]);
  }
}
