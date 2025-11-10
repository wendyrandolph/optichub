<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Display a list of team members for this tenant */
    public function index()
    {
        $this->authorize('viewAny', TeamMember::class);

        $tenantId = Auth::user()->tenant_id;
        $members = TeamMember::where('tenant_id', $tenantId)->latest()->get();

        return view('team-members.index', compact('members'));
    }

    /** Show create form */
    public function create()
    {
        $this->authorize('create', TeamMember::class);
        return view('team-members.create');
    }

    /** Store new team member */
    public function store(Request $request)
    {
        $this->authorize('create', TeamMember::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:team-members,email',
            'role'       => 'required|string',
            'title'      => 'nullable|string|max:150',
            'phone'      => 'nullable|string|max:25',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['status'] = 'active';

        TeamMember::create($validated);

        return redirect()->route('tenant.team-members.index', Auth::user()->tenant_id)
            ->with('status', 'Team member added successfully!');
    }

    /** Show team member */
    public function show(TeamMember $team_member)
    {
        $this->authorize('view', $team_member);
        return view('team-members.show', compact('team_member'));
    }

    /** Edit form */
    public function edit(TeamMember $team_member)
    {
        $this->authorize('update', $team_member);
        return view('team-members.edit', compact('team_member'));
    }

    /** Update */
    public function update(Request $request, TeamMember $team_member)
    {
        $this->authorize('update', $team_member);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => "required|email|unique:team-members,email,{$team_member->id}",
            'role'       => 'required|string',
            'title'      => 'nullable|string|max:150',
            'phone'      => 'nullable|string|max:25',
            'status'     => 'required|string|in:active,inactive',
        ]);

        $team_member->update($validated);

        return redirect()->route('tenant.team-members.index', Auth::user()->tenant_id)
            ->with('status', 'Team member updated successfully!');
    }

    /** Delete */
    public function destroy(TeamMember $team_member)
    {
        $this->authorize('delete', $team_member);
        $team_member->delete();

        return back()->with('status', 'Team member deleted.');
    }
}
