<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckRole;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\Report;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Subscription;
use App\Models\Tenant; // Renamed from Organization
use App\Models\TeamMember; // Renamed from Admins
use App\Models\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
  /**
   * Enforce provider/admin roles across this controller.
   */
  public function __construct()
  {
    $this->middleware(CheckRole::class . ':provider,admin,super_admin,superadmin');
  }

  // ----------------------------------------------------------------------
  // TENANT MANAGEMENT
  // ----------------------------------------------------------------------

  /** List all tenants (provider/super-admin view). */
  public function indexTenants()
  {
    $tenants = Tenant::withCount(['users', 'clients', 'projects'])->get();
    return view('admin.tenants.index', compact('tenants'));
  }

  /** Show new-tenant form. */
  public function createTenant()
  {
    return view('admin.tenants.create');
  }

  /** Store a new tenant. */
  public function storeTenant(\App\Http\Requests\StoreTenantRequest $request)
  {
    try {
      $tenant = Tenant::create($request->validated());

      // Optional activity (comment out if you don't have the activity() helper installed)
      // activity('tenant')->by(Auth::user())->on($tenant)->log("tenant_created: {$tenant->name}");

      return redirect()->route('admin.tenants.index')
        ->with('success', "Organization '{$tenant->name}' created successfully.");
    } catch (\Throwable $e) {
      Log::error("Tenant creation failed: " . $e->getMessage());
      return Redirect::back()->withInput()->with('error', 'Failed to create organization.');
    }
  }

  /** Show one tenant with relations. */
  public function showTenant(Tenant $tenant)
  {
    $tenant->load(['users', 'teamMembers', 'projects']);
    return view('admin.tenants.show', compact('tenant'));
  }

  /** Delete tenant (with simple dependent guard). */
  public function destroyTenant(Tenant $tenant)
  {
    $dependents = method_exists($tenant, 'dependentCounts') ? $tenant->dependentCounts() : ['users' => 0, 'clients' => 0];
    if (($dependents['users'] ?? 0) > 0 || ($dependents['clients'] ?? 0) > 0) {
      return Redirect::back()->with('error', 'Cannot delete organization with existing users or clients.');
    }

    $tenantName = $tenant->name;
    $tenant->delete();

    // activity('tenant')->by(Auth::user())->log("tenant_deleted: {$tenantName}");

    return redirect()->route('admin.tenants.index')
      ->with('success', "Tenant '{$tenantName}' and its data have been deleted.");
  }

  // ----------------------------------------------------------------------
  // DASHBOARD
  // ----------------------------------------------------------------------

  public function dashboard(Request $request)
  {
    $userId = null;
    try {
      $user     = Auth::user();
      $userId   = $user->id ?? null;
      $tenantId = $user->tenant_id ?? null;

      $orgType  = $request->session()->get('organization_type');
      $userRole = $request->session()->get('role');

      Log::info("DASHBOARD ACCESS: user={$userId}, tenant={$tenantId}, orgType={$orgType}, role={$userRole}");

      $isAdmin   = ($user->role ?? null) !== 'member';
      $scopeUser = $isAdmin ? null : $user;

      $tz    = config('app.timezone', 'America/Denver');
      $now   = \Carbon\Carbon::now($tz);
      $range = $request->query('range', 'wtd');

      $from = match ($range) {
        'today' => $now->clone()->startOfDay(),
        'mtd'   => $now->clone()->startOfMonth(),
        '30d'   => $now->clone()->subDays(29)->startOfDay(),
        default => $now->clone()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay(),
      };

      // Data lookups (tenant scoping handled by global scopes on models)
      $tasksDueToday   = \App\Models\Task::forUser($userId)->dueToday()->get();
      $assignedTasks   = \App\Models\Task::forUser($userId)->assigned()->get();
      $timeLoggedToday = method_exists(\App\Models\TimeEntry::class, 'forUser')
        ? \App\Models\TimeEntry::forUser($userId)->loggedToday()->sum('hours') : 0;

      $recentProjects = (method_exists(\App\Models\Project::class, 'forTenant') && method_exists(\App\Models\Project::class, 'recentlyUpdated'))
        ? \App\Models\Project::forTenant($tenantId)->recentlyUpdated(5)->get()
        : collect();

      // KPIs
      $kpi_hours_wtd           = method_exists(\App\Models\TimeEntry::class, 'sumHoursBetween') ? \App\Models\TimeEntry::sumHoursBetween($from, $now, $scopeUser) : 0;
      $kpi_tasks_completed_wtd = method_exists(\App\Models\Task::class, 'countCompletedBetween') ? \App\Models\Task::countCompletedBetween($from, $now) : 0;

      $due = \App\Models\Invoice::dueSnapshot($now, false);
      $kpi_invoices_due_total = (float)($due['total'] ?? 0);
      $kpi_invoices_due_count = (int)($due['count'] ?? 0);

      $kpi_new_leads_wtd = method_exists(\App\Models\Lead::class, 'countCreatedBetween')
        ? \App\Models\Lead::countCreatedBetween($from, $now)
        : \App\Models\Lead::whereBetween('created_at', [$from, $now])->count();

      // Alerts
      $alerts = [];

      $overdue = \App\Models\Invoice::dueSnapshot($now, true);
      if (!empty($overdue['count'])) {
        $href = \Illuminate\Support\Facades\Route::has('tenant.invoices.index')
          ? route('tenant.invoices.index', ['tenant' => $tenantId, 'filter' => 'overdue'])
          : '#';
        $alerts[] = ['icon' => 'fa-file-invoice', 'text' => "Overdue invoices {$overdue['count']}", 'href' => $href];
      }

      $unassigned = method_exists(\App\Models\Task::class, 'countUnassigned') ? \App\Models\Task::countUnassigned() : 0;
      if ($unassigned > 0) {
        $alerts[] = ['icon' => 'fa-user-clock', 'text' => "Unassigned tasks {$unassigned}", 'href' => route('tenant.tasks.index', ['tenant' => $tenantId, 'filter' => 'unassigned'])];
      }

      $staleProjects = method_exists(\App\Models\Project::class, 'countStale') ? \App\Models\Project::countStale(14) : 0;
      if ($staleProjects > 0) {
        $alerts[] = ['icon' => 'fa-hourglass-half', 'text' => "Stale projects {$staleProjects}", 'href' => route('tenant.projects.index', ['tenant' => $tenantId, 'filter' => 'stale'])];
      }

      $stuckTasks = method_exists(\App\Models\Task::class, 'countStuck') ? \App\Models\Task::countStuck() : 0;
      if ($stuckTasks > 0) {
        $alerts[] = ['icon' => 'fa-triangle-exclamation', 'text' => "Stuck tasks {$stuckTasks}", 'href' => route('tenant.tasks.index', ['tenant' => $tenantId, 'filter' => 'stuck'])];
      }

      // Financials
      $agingData        = \App\Models\Invoice::agingBuckets();
      $aging            = $agingData['buckets']    ?? ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
      $aging_hasAmount  = (bool)($agingData['hasAmount'] ?? false);

      $cash_collected   = \App\Models\Invoice::paymentsSumBetween($from, $now);
      $forecast         = [
        'hasAmount' => true,
        'total'     => \App\Models\Invoice::forecastDueBetween($now, $now->clone()->addDays(14), $tenantId),
        'count'     => 0,
      ];

      // Team capacity / on-time
      $capacity = method_exists(\App\Models\Task::class, 'openByAssigneeWithNames')
        ? \App\Models\Task::openByAssigneeWithNames()
        : [];

      $onTimeCount     = method_exists(\App\Models\Task::class, 'onTimeCompletionBetween') ? \App\Models\Task::onTimeCompletionBetween($from, $now) : 0;
      $totalCompleted  = method_exists(\App\Models\Task::class, 'countCompletedBetween')    ? \App\Models\Task::countCompletedBetween($from, $now) : 0;
      $on_time = [
        'on_time' => (int)$onTimeCount,
        'total'   => (int)$totalCompleted,
        'pct'     => $totalCompleted > 0 ? (int)round(($onTimeCount / $totalCompleted) * 100) : 0,
      ];

      $overdue_open = method_exists(\App\Models\Task::class, 'countOverdueOpen') ? \App\Models\Task::countOverdueOpen() : 0;

      // Utilization
      $workdays       = $from->diffInDaysFiltered(fn(\Carbon\Carbon $d) => !$d->isWeekend(), $now);
      $active_users   = \App\Models\User::where('tenant_id', $tenantId)->count();
      $capacity_hours = max($active_users * 8 * max($workdays, 1), 1);
      $billable_hours   = method_exists(TimeEntry::class, 'billableHoursBetween')
        ? TimeEntry::billableHoursBetween($from, $now, $tenantId) : 0;

      $uninvoiced_hours = method_exists(TimeEntry::class, 'uninvoicedBillableHours')
        ? TimeEntry::uninvoicedBillableHours($from, $now, $tenantId) : 0;

      $utilization_pct  = $capacity_hours > 0 ? (int)round(($billable_hours / $capacity_hours) * 100) : 0;

      // Pipeline
      $pipeline = method_exists(\App\Models\Lead::class, 'pipelineWtd') ? \App\Models\Lead::pipelineWtd($from, $now) : [];

      // Render
      return view('admin.dashboard', [
        'orgType'  => $orgType,
        'userRole' => $userRole,

        'isAdmin'         => $isAdmin,
        'tasksDueToday'   => $tasksDueToday,
        'assignedTasks'   => $assignedTasks,
        'timeLoggedToday' => $timeLoggedToday,
        'recentProjects'  => $recentProjects,

        'range'                     => $range,
        'kpi_hours_wtd'             => $kpi_hours_wtd,
        'kpi_tasks_completed_wtd'   => $kpi_tasks_completed_wtd,
        'kpi_invoices_due_total'    => $kpi_invoices_due_total,
        'kpi_invoices_due_count'    => $kpi_invoices_due_count,
        'kpi_invoices_due_display'  => $kpi_invoices_due_total > 0
          ? (function ($a) {
            $a = (float)$a;
            return $a >= 1_000_000 ? '$' . round($a / 1_000_000, 1) . 'm' : ($a >= 1000 ? '$' . round($a / 1000, 1) . 'k' : '$' . number_format($a, 0));
          })($kpi_invoices_due_total)
          : ($kpi_invoices_due_count . ' due'),
        'kpi_new_leads_wtd'         => $kpi_new_leads_wtd,

        'aging'           => $aging,
        'aging_hasAmount' => $aging_hasAmount,
        'cash_collected'  => $cash_collected,
        'forecast'        => $forecast,

        'alerts'        => $alerts,
        'capacity'      => $capacity,
        'pipeline'      => $pipeline,
        'on_time'       => $on_time,
        'overdue_open'  => $overdue_open,
        'staleProjects' => $staleProjects,
        'stuckTasks'    => $stuckTasks,

        'billable_hours'   => $billable_hours,
        'uninvoiced_hours' => $uninvoiced_hours,
        'utilization_pct'  => $utilization_pct,

        // for Blade links
        'tenant' => $tenantId,
      ]);
    } catch (\Throwable $e) {
      Log::error("Dashboard error for user {$userId}: " . $e->getMessage());
      return redirect('/login')->with('error_message', 'Error loading dashboard.');
    }
  }


  // ----------------------------------------------------------------------
  // ADMIN PAGES (stubs)
  // ----------------------------------------------------------------------

  public function reports()
  {

    // Optional: quick log to confirm we got here
    Log::info('AdminController@reports authorized for user ' . auth()->id());

    // Use this exact view key and create the matching file below
    return view('admin.reports.index');
  }


  public function settings()
  {
    $tenantId = auth()->user()->tenant_id;
    return redirect()->route('tenant.settings.index', ['tenant' => $tenantId]);
  }

  // --- Legacy methods below still reference $this->userModel, etc.
  // Consider removing or refactoring them to Eloquent if you still need them.
}
