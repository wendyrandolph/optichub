<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\TimeEntry\UpdateTimeEntryRequest;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  // GET {tenant}/time
  public function index(): View
  {
    $tenantParam = request()->route('tenant');
    $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int)$tenantParam;

    $entries = TimeEntry::with(['user', 'project', 'task'])
      ->where('tenant_id', $tenantId)
      ->latest()
      ->get();

    return view('time.index', compact('entries'));
  }

  // GET {tenant}/time/create  ← this is the one you’re missing
  public function create(): View
  {
    $tenantParam = request()->route('tenant');
    $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int)$tenantParam;

    $users    = User::where('tenant_id', $tenantId)->select('id', 'first_name', 'last_name',  'username')->orderBy('last_name')->get();
    $projects = Project::where('tenant_id', $tenantId)->select('id', 'project_name as name')->orderBy('project_name')->get();
    $tasks    = Task::where('tenant_id', $tenantId)->select('id', 'title')->orderBy('title')->get();

    return view('time.create', compact('users', 'projects', 'tasks'));
  }

  // POST {tenant}/time
  public function store(StoreTimeEntryRequest $request): RedirectResponse
  {
    $data = $request->validated();

    $tenantParam = $request->route('tenant');
    $data['tenant_id'] = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int)$tenantParam;

    // Optional: if hours is empty but start/end provided, compute here
    if (empty($data['hours']) && !empty($data['start_time']) && !empty($data['end_time'])) {
      $start = strtotime($data['start_time']);
      $end   = strtotime($data['end_time']);
      if ($start && $end && $end > $start) {
        $data['hours'] = round(($end - $start) / 3600, 2);
      }
    }

    TimeEntry::create($data);

    return redirect()
      ->route('tenant.time.index', ['tenant' => $data['tenant_id']])
      ->with('success_message', 'Time entry added successfully.');
  }

  // GET {tenant}/time/{entry}/edit  (rename your editForm to edit)
  public function edit(TimeEntry $entry): View
  {
    $this->authorize('update', $entry);

    $tenantId = $entry->tenant_id;
    $users    = User::where('tenant_id', $tenantId)->select('id', 'name', 'username')->get();
    $projects = Project::where('tenant_id', $tenantId)->select('id', 'project_name as name')->get();
    $tasks    = Task::where('tenant_id', $tenantId)->select('id', 'title')->get();

    return view('time.edit', compact('entry', 'users', 'projects', 'tasks'));
  }

  // PUT/PATCH {tenant}/time/{entry}
  public function update(UpdateTimeEntryRequest $request, TimeEntry $entry): RedirectResponse
  {
    $this->authorize('update', $entry);
    $entry->update($request->validated());

    return redirect()
      ->route('tenant.time.index', ['tenant' => $entry->tenant_id])
      ->with('success_message', 'Time entry updated.');
  }

  // DELETE {tenant}/time/{entry}
  public function destroy(TimeEntry $entry): RedirectResponse
  {
    $this->authorize('delete', $entry);
    $tenantId = $entry->tenant_id;
    $entry->delete();

    return redirect()
      ->route('tenant.time.index', ['tenant' => $tenantId])
      ->with('success_message', 'Time entry deleted.');
  }
}
