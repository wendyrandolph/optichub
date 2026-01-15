<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Client;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
  public function index()
  {
    $tenantId = Auth::user()->tenant_id;

    $recentActivity = ActivityLog::with(['user:id,first_name,last_name,username', 'related'])
      ->forTenant($tenantId)
      ->latest()
      ->paginate(25);

    // Fallback: synthesize activity if no logs yet
    if ($recentActivity->count() === 0) {
      $fallback = collect();

      $recentTasks = Task::where('tenant_id', $tenantId)
        ->latest('updated_at')
        ->take(10)
        ->get();

      foreach ($recentTasks as $t) {
        $fallback->push((object) [
          'created_at'   => $t->updated_at ?? $t->created_at,
          'action'       => 'Task ' . ($t->status ?? 'updated'),
          'description'  => $t->title ?? 'Task updated',
          'related_type' => Task::class,
          'related_id'   => $t->id,
          'related'      => $t,
          'user'         => null,
        ]);
      }

      $recentProjects = Project::where('tenant_id', $tenantId)
        ->latest('updated_at')
        ->take(5)
        ->get();

      foreach ($recentProjects as $p) {
        $fallback->push((object) [
          'created_at'   => $p->updated_at ?? $p->created_at,
          'action'       => 'Project updated',
          'description'  => $p->project_name ?? 'Project updated',
          'related_type' => Project::class,
          'related_id'   => $p->id,
          'related'      => $p,
          'user'         => null,
        ]);
      }

      $recentActivity = $fallback->sortByDesc('created_at');
    }

    return view('admin.activity.index', compact('recentActivity'));
  }

  public function showRelated(string $relatedType, int $relatedId)
  {
    $modelClass = match ($relatedType) {
      'project' => Project::class,
      'client'  => Client::class,
      'task'    => Task::class,
      default   => null,
    };

    if (!$modelClass) {
      return back()->with('error', 'Invalid entity type.');
    }

    $entity = $modelClass::findOrFail($relatedId);
    $this->authorize('view', $entity); // ensure your policies allow this

    $tenantId = Auth::user()->tenant_id;

    $activity = ActivityLog::with('user:id,first_name,last_name,username')
      ->forTenant($tenantId)
      ->where('related_type', $modelClass)
      ->where('related_id', $relatedId)
      ->latest()
      ->paginate(25);

    return view('admin.activity.show-related', compact('activity', 'entity', 'relatedType'));
  }
}
