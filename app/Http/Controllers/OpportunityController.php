<?php

namespace App\Http\Controllers;

use App\Models\Tenant;                           // {tenant} param
use App\Models\Tenant as Organization;          // <- use Tenant records as "organizations"
use App\Models\Opportunity;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Models\ClientCompany;
use App\Models\Lead;
use App\Models\User;
use App\Models\OpportunityActivity;
use App\Models\Project;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Services\OpportunityAutomation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class OpportunityController extends Controller
{
  protected OpportunityAutomation $automation;

  public function __construct()
  {
    $this->middleware('auth');
    $this->automation = app(OpportunityAutomation::class);
  }

  /** GET /{tenant}/opportunities */
  public function index(Request $request, \App\Models\Tenant $tenant)
  {
    $q   = $request->get('q', '');
    $st  = $request->get('stage', '');
    $so  = $request->get('sort', 'recent');
    $view = $request->get('view', '');
    $ownerId = $request->get('owner_id');
    $health = $request->get('health', '');
    $minAge = $request->get('min_age_days');
    $maxAge = $request->get('max_age_days');
    $minAge = is_numeric($minAge) ? (int) $minAge : null;
    $maxAge = is_numeric($maxAge) ? (int) $maxAge : null;
    $ageExpr = "GREATEST(DATEDIFF(CURDATE(), COALESCE(stage_changed_at, updated_at, created_at)), 0)";

    $stageList = ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    $now = Carbon::now();
    $stalledDays = 14;
    $readyWindow = 7;

    $query = Opportunity::query()
      ->where('tenant_id', $tenant->id)
      ->where('stage', '!=', 'lost')
      ->with([
        'company:id,company_name',
        'lead:id,name',
        'owner:id,first_name,last_name,email',
      ])
      ->when(is_numeric($ownerId), fn($qb) => $qb->where('owner_id', (int) $ownerId))
      ->when($q, fn($qb) => $qb->where(function ($w) use ($q) {
        $w->where('title', 'like', "%{$q}%")
          ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$q}%"))
          ->orWhereHas('lead', fn($l) => $l->where('name', 'like', "%{$q}%"));
      }))
      ->when($st, fn($qb) => $qb->where('stage', $st));

    // Health filters
    if ($health === 'overdue') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->whereNotNull('next_followup_at')
        ->where('next_followup_at', '<', $now);
    } elseif ($health === 'stalled') {
      $query->where(function ($qb) use ($stalledDays) {
        $qb->where('stage', 'stalled')
          ->orWhereRaw('updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)', [$stalledDays]);
      })
        ->where(function ($qb) use ($now) {
          $qb->whereNull('next_followup_at')
            ->orWhere('next_followup_at', '>=', $now);
        });
    } elseif ($health === 'ready') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->whereNotNull('expected_close_date')
        ->whereBetween('expected_close_date', [$now, (clone $now)->addDays($readyWindow)]);
    } elseif ($health === 'on_track') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->where(function ($qb) use ($now) {
          $qb->whereNull('next_followup_at')
            ->orWhere('next_followup_at', '>=', $now);
        })
        ->whereRaw('updated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)', [$stalledDays])
        ->where(function ($qb) use ($now, $readyWindow) {
          $qb->whereNull('expected_close_date')
            ->orWhere('expected_close_date', '>', (clone $now)->addDays($readyWindow));
        });
    }

    // Quick views
    if ($view === 'my') {
      $query->where('owner_id', auth()->id());
    } elseif ($view === 'needs_followup') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->whereNull('flagged_overdue_at')
        ->whereNotNull('next_followup_at')
        ->where('next_followup_at', '>=', $now);
    } elseif ($view === 'overdue') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->whereNotNull('next_followup_at')
        ->where('next_followup_at', '<', $now);
    } elseif ($view === 'closing_soon') {
      $query->whereNotIn('stage', ['won', 'lost'])
        ->whereNotNull('expected_close_date')
        ->whereBetween('expected_close_date', [$now, (clone $now)->addDays(14)]);
    }

    // Default sort tweaks for follow-up views
    $applySort = $so;
    if (in_array($view, ['needs_followup', 'overdue']) && $so === 'recent') {
      $applySort = 'followup';
    }

    $opps = $query
      ->when($applySort === 'title_asc',  fn($qb) => $qb->orderBy('title'))
      ->when($applySort === 'title_desc', fn($qb) => $qb->orderByDesc('title'))
      ->when($applySort === 'value_desc', fn($qb) => $qb->orderByDesc('estimated_value'))
      ->when($applySort === 'value_asc',  fn($qb) => $qb->orderBy('estimated_value'))
      ->when($applySort === 'close_asc',  fn($qb) => $qb->orderBy('expected_close_date'))
      ->when($applySort === 'close_desc', fn($qb) => $qb->orderByDesc('expected_close_date'))
      ->when($applySort === 'followup', fn($qb) => $qb->orderByRaw('next_followup_at is null, next_followup_at asc'))
      ->when($applySort === 'recent',     fn($qb) => $qb->latest('updated_at'))
      ->when($minAge !== null, fn($qb) => $qb->whereRaw("$ageExpr >= ?", [$minAge]))
      ->when($maxAge !== null, fn($qb) => $qb->whereRaw("$ageExpr <= ?", [$maxAge]))
      ->paginate(20)
      ->appends($request->only(['q', 'stage', 'sort', 'view', 'owner_id', 'min_age_days', 'max_age_days', 'health']));

    $kpis = $this->kpis($tenant, $stageList);

    return view('opportunities.index', [
      'tenant'        => $tenant,
      'opportunities' => $opps,
      'stages'        => array_map('ucfirst', $stageList),
      'kpis'          => $kpis,
      'view'          => $view,
    ]);
  }


  /** GET /{tenant}/opportunities/create */
  public function create(Tenant $tenant): View
  {
    $this->authorize('create', Opportunity::class);

    $companies = ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $leads = Lead::where('tenant_id', $tenant->id)
      ->with('company:id,company_name')
      ->get(['id', 'name', 'company_id']);
    $ownerIds = \App\Models\TeamMember::where('tenant_id', $tenant->id)->pluck('user_id')->filter();
    $owners = User::where('tenant_id', $tenant->id)
      ->whereIn('id', $ownerIds)
      ->orderBy('first_name')
      ->orderBy('last_name')
      ->get(['id', 'first_name', 'last_name', 'email']);

    return view('opportunities.create', [
      'tenant'        => $tenant,
      'companies' => $companies,
      'leads' => $leads,
      'owners' => $owners,
    ]);
  }

  /** POST /{tenant}/opportunities */
  public function store(StoreOpportunityRequest $request, Tenant $tenant): RedirectResponse
  {
    $this->authorize('create', Opportunity::class);

    $data               = $request->validated();
    $data['tenant_id']  = $tenant->id;
    $data['probability'] = $this->probabilityForStage($data['stage'] ?? 'new', $data['probability'] ?? null);

    // If company not provided but lead has one, auto-fill
    if (empty($data['company_id']) && !empty($data['lead_id'])) {
      $leadCompany = Lead::where('tenant_id', $tenant->id)->where('id', $data['lead_id'])->value('company_id');
      if ($leadCompany) {
        $data['company_id'] = $leadCompany;
      }
    }

    $opportunity = Opportunity::create($data);

    $this->logActivity($opportunity, 'note', 'Opportunity created', ['stage' => $opportunity->stage]);
    $this->automation->run('opportunity.created', $opportunity);
    if ($request->boolean('add_activity_note')) {
      $this->logActivity($opportunity, 'note', 'Automation: Opportunity created (requested)', []);
    }
    if ($request->boolean('create_followup_task')) {
      $this->logActivity($opportunity, 'followup', 'Follow-up task requested', []);
    }

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity created successfully.');
  }

  /** GET /{tenant}/opportunities/{opportunity}/edit */
  public function edit(Tenant $tenant, Opportunity $opportunity): View
  {
    $this->authorize('update', $opportunity);

    $companies = ClientCompany::where('tenant_id', $tenant->id)->get(['id', 'company_name']);
    $leads = Lead::where('tenant_id', $tenant->id)
      ->with('company:id,company_name')
      ->get(['id', 'name', 'company_id']);
    $ownerIds = \App\Models\TeamMember::where('tenant_id', $tenant->id)->pluck('user_id')->filter();
    $owners = User::where('tenant_id', $tenant->id)
      ->whereIn('id', $ownerIds)
      ->orderBy('first_name')
      ->orderBy('last_name')
      ->get(['id', 'first_name', 'last_name', 'email']);

    return view('opportunities.edit', [
      'tenant'        => $tenant,
      'opportunity'   => $opportunity,
      'companies' => $companies,
      'leads' => $leads,
      'owners' => $owners,
    ]);
  }

  /** PUT/PATCH /{tenant}/opportunities/{opportunity} */
  public function update(UpdateOpportunityRequest $request, Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('update', $opportunity);

    $incoming = $request->validated();
    $oldStage = $opportunity->stage;
    $oldOwner = $opportunity->owner_id;
    $oldValue = $opportunity->estimated_value;
    $oldFollowup = $opportunity->next_followup_at;
    if (empty($incoming['company_id'] ?? null) && !empty($incoming['lead_id'])) {
      $leadCompany = Lead::where('tenant_id', $tenant->id)->where('id', $incoming['lead_id'])->value('company_id');
      if ($leadCompany) {
        $incoming['company_id'] = $leadCompany;
      }
    }
    $incoming['probability'] = $this->probabilityForStage($incoming['stage'] ?? $oldStage, $incoming['probability'] ?? null);

    if (($incoming['stage'] ?? $oldStage) === 'lost' && empty($incoming['lost_reason'])) {
        return Redirect::back()->withErrors(['lost_reason' => 'Reason is required when marking lost.']);
    }

    $opportunity->update($incoming);

    if ($opportunity->wasChanged('stage')) {
        $opportunity->stage_changed_at = now();
        $opportunity->saveQuietly();
        $this->logActivity($opportunity, 'stage_change', 'Stage updated', [
            'from' => $oldStage,
            'to' => $opportunity->stage,
        ]);
        $this->automation->run('opportunity.stage_changed', $opportunity, ['from' => $oldStage, 'to' => $opportunity->stage]);
        if ($opportunity->stage === 'won') {
            $this->automation->run('opportunity.won', $opportunity);
        } elseif ($opportunity->stage === 'proposal' && empty($opportunity->next_followup_at)) {
            $opportunity->next_followup_at = now()->addDays(3);
            $opportunity->saveQuietly();
            $this->logActivity($opportunity, 'followup', 'Follow-up auto-scheduled', ['next_followup_at' => $opportunity->next_followup_at]);
        } elseif ($opportunity->stage === 'lost') {
            $this->automation->run('opportunity.lost', $opportunity);
        }
    }

    if ($opportunity->wasChanged('owner_id')) {
        $this->logActivity($opportunity, 'owner_change', 'Owner updated', [
            'from' => $oldOwner,
            'to' => $opportunity->owner_id,
        ]);
    }

    if ($opportunity->wasChanged('estimated_value')) {
        $this->logActivity($opportunity, 'value_change', 'Value updated', [
            'from' => $oldValue,
            'to' => $opportunity->estimated_value,
        ]);
    }

    if ($opportunity->wasChanged('next_followup_at')) {
        $this->logActivity($opportunity, 'followup', 'Follow-up updated', [
            'from' => $oldFollowup,
            'to' => $opportunity->next_followup_at,
        ]);
    }

    if ($request->boolean('add_activity_note')) {
      $this->logActivity($opportunity, 'note', 'Automation: Opportunity updated (requested)', []);
    }
    if ($request->boolean('create_followup_task')) {
      $this->logActivity($opportunity, 'followup', 'Follow-up task requested', []);
    }

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity updated successfully.');
  }

  /** DELETE /{tenant}/opportunities/{opportunity} */
  public function destroy(Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('delete', $opportunity);

    if ($opportunity->tenant_id !== $tenant->id) {
      abort(404);
    }

    ActivityLog::record(
      $tenant->id,
      auth()->id(),
      $opportunity,
      'opportunity.deleted',
      'Opportunity deleted',
      [
        'opportunity_title' => $opportunity->title,
        'opportunity_stage' => $opportunity->stage,
      ]
    );

    $opportunity->delete();

    return Redirect::route('tenant.opportunities.index', ['tenant' => $tenant])
      ->with('success', 'Opportunity deleted.');
  }

  /** (Optional) GET /{tenant}/opportunities/{opportunity} */
  public function show(Tenant $tenant, Opportunity $opportunity): View
  {
    $this->authorize('view', $opportunity);

    $opportunity->load(['company', 'lead', 'owner', 'activities.user' => function ($q) {
        $q->select('id', 'first_name', 'last_name', 'email');
    }])->loadCount('activities');

    return view('opportunities.show', [
      'tenant'      => $tenant,
      'opportunity' => $opportunity,
      'activities'  => $opportunity->activities()->latest()->get(),
    ]);
  }

  public function storeActivity(Request $request, Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('view', $opportunity);
    $data = Validator::make($request->all(), [
      'body' => ['required', 'string'],
    ])->validate();

    $this->logActivity($opportunity, 'note', $data['body']);

    return back()->with('success', 'Note added.');
  }

  public function updateFollowup(Request $request, Tenant $tenant, Opportunity $opportunity)
  {
    $this->authorize('update', $opportunity);
    $data = $request->validate([
      'next_followup_at' => ['nullable', 'date'],
    ]);
    $old = $opportunity->next_followup_at;
    $opportunity->next_followup_at = $data['next_followup_at'] ? Carbon::parse($data['next_followup_at']) : null;
    $opportunity->save();

    $this->logActivity($opportunity, 'followup', 'Follow-up updated', [
      'from' => $old,
      'to' => $opportunity->next_followup_at,
    ]);

    return response()->json(['status' => 'ok']);
  }

  protected function kpis(Tenant $tenant, array $stages): array
  {
    $base = Opportunity::where('tenant_id', $tenant->id);
    $total = (clone $base)->count();
    // Open pipeline weighted by probability for open stages only
    $pipeline = (clone $base)->whereIn('stage', ['new', 'qualified', 'proposal', 'negotiation'])
      ->get()
      ->sum(function ($o) {
        $prob = $o->probability ?? 0;
        return ($o->estimated_value ?? 0) * ($prob / 100);
      });
    $wonMtd = (clone $base)->where('stage', 'won')
      ->whereBetween('updated_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
      ->sum('estimated_value');

    $since = Carbon::now()->subDays(90);
    $won90 = (clone $base)->where('stage', 'won')->where('updated_at', '>=', $since)->count();
    $lost90 = (clone $base)->where('stage', 'lost')->where('updated_at', '>=', $since)->count();
    $winRate = ($won90 + $lost90) > 0 ? round(($won90 / ($won90 + $lost90)) * 100) : 0;

    $byStage = collect($stages)->mapWithKeys(function ($s) use ($base) {
      return [ucfirst($s) => (clone $base)->where('stage', $s)->count()];
    });

    $stageMeta = collect($stages)->mapWithKeys(function ($s) use ($base) {
      $items = (clone $base)->where('stage', $s)->get(['updated_at']);
      $count = $items->count();
      $avgDays = $count ? round($items->avg(fn($o) => $o->updated_at ? $o->updated_at->diffInDays(now()) : 0)) : 0;
      return [$s => ['count' => $count, 'avg_days' => $avgDays]];
    });

    return [
      'total' => $total,
      'pipeline' => $pipeline,
      'won_mtd' => $wonMtd,
      'win_rate_90' => $winRate,
      'by_stage' => $byStage,
      'stage_meta' => $stageMeta,
    ];
  }

  protected function logActivity(Opportunity $opp, string $type, string $body, array $meta = []): void
  {
    OpportunityActivity::create([
      'tenant_id' => $opp->tenant_id,
      'opportunity_id' => $opp->id,
      'user_id' => auth()->id(),
      'type' => $type,
      'body' => $body,
      'meta' => $meta,
    ]);
  }

  protected function probabilityForStage(string $stage, $provided): ?int
  {
    if (!is_null($provided)) {
      return (int) $provided;
    }
    $map = [
      'new' => 10,
      'qualified' => 25,
      'proposal' => 50,
      'negotiation' => 65,
      'won' => 100,
      'lost' => 0,
    ];
    $key = strtolower($stage);
    return $map[$key] ?? null;
  }

  public function convertToProject(Tenant $tenant, Opportunity $opportunity): RedirectResponse
  {
    $this->authorize('update', $opportunity);

    if (strtolower($opportunity->stage) !== 'won') {
      return back()->withErrors(['stage' => 'Opportunity must be won before converting to a project.']);
    }

    $project = Project::create([
      'tenant_id' => $tenant->id,
      'client_company_id' => $opportunity->company_id,
      'project_name' => $opportunity->title,
      'description' => $opportunity->notes,
      'status' => 'open',
      'opportunity_id' => $opportunity->id,
    ]);

    $this->logActivity($opportunity, 'project_created', 'Converted to project', [
      'project_id' => $project->id,
      'project_name' => $project->project_name,
    ]);

    if ($opportunity->owner_id) {
      Notification::create([
        'tenant_id' => $tenant->id,
        'user_id' => $opportunity->owner_id,
        'type' => 'opportunity.project_created',
        'data' => [
          'opportunity_id' => $opportunity->id,
          'project_id' => $project->id,
          'message' => 'Opportunity converted to project: ' . $project->project_name,
        ],
      ]);
    }

    $this->automation->run('opportunity.stage_changed', $opportunity, ['from' => $opportunity->stage, 'to' => 'project_converted']);

    return Redirect::route('tenant.projects.show', ['tenant' => $tenant->id, 'project' => $project->id])
      ->with('success', 'Project created from opportunity.');
  }
}
