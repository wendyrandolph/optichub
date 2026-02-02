<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    public function create(Request $request, $tenant)
    {
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;

        $projects = Project::where('tenant_id', $tenantId)
            ->orderBy('project_name')
            ->get(['id', 'project_name']);

        $members = \App\Models\TeamMember::where('tenant_id', $tenantId)
            ->orderBy('lastName')
            ->select('id', 'user_id', 'firstName', 'lastName', 'email')
            ->get();

        $clients = Contact::where('tenant_id', $tenantId)
            ->orderBy('lastName')
            ->orderBy('firstName')
            ->get(['id', 'firstName', 'lastName', 'email']);

        return view('meetings-create', compact('tenantId', 'projects', 'members', 'clients'));
    }

    public function store(Request $request, $tenant)
    {
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where('tenant_id', $tenantId)],
            'member_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $request->user()?->id;
        $data['all_day'] = (bool) ($data['all_day'] ?? false);

        if (empty($data['end_at'])) {
            $data['end_at'] = Carbon::parse($data['start_at'])->addHour();
        }

        $meeting = Meeting::create($data);

        return redirect()
            ->route('tenant.meetings.show', ['tenant' => $tenantId, 'meeting' => $meeting->id])
            ->with('success_message', 'Meeting scheduled.');
    }

    public function show(Request $request, $tenant, Meeting $meeting)
    {
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;

        abort_unless($meeting->tenant_id === $tenantId, 404);

        $meeting->load(['project', 'contact', 'member']);

        return view('meetings-show', compact('tenantId', 'meeting'));
    }
}
