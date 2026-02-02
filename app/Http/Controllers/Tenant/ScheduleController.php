<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $user = auth()->user() ?? auth('admin')->user();

        if (! $user || (int) $user->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if (($tenant->workspace_type ?? null) !== 'creative') {
            abort(404);
        }

        $today = Carbon::today();
        $viewMode = $request->get('view', 'today') === 'week' ? 'week' : 'today';
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $tasks = Task::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['completed', 'archived'])
            ->where(function ($query) use ($user) {
                $query->where('assigned_user_id', $user->id)
                    ->orWhere(function ($sub) use ($user) {
                        $sub->whereNull('assigned_user_id')
                            ->where('user_id', $user->id);
                    });
            })
            ->with([
                'project:id,project_name,contact_id',
                'project.contact:id,firstName,lastName,first_name,last_name,company_name',
                'assignedUser:id,first_name,last_name,email',
            ])
            ->withSum(['timeEntries as logged_hours' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->whereNotNull('end_time');
            }], 'hours')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('updated_at')
            ->get();

        $overdue = $tasks->filter(fn ($task) => $task->due_date && $task->due_date->lt($today));
        $todayTasks = $tasks->filter(fn ($task) => $task->due_date && $task->due_date->isSameDay($today));
        $thisWeek = $tasks->filter(function ($task) use ($weekStart, $weekEnd, $today) {
            return $task->due_date
                && $task->due_date->between($weekStart, $weekEnd)
                && ! $task->due_date->isSameDay($today)
                && ! $task->due_date->lt($today);
        });
        $later = $tasks->filter(fn ($task) => ! $task->due_date || $task->due_date->gt($weekEnd));

        $assignedCount = $tasks->count();
        $overdueCount = $overdue->count();
        $estimatedMinutesWeek = $tasks
            ->filter(fn ($task) => $task->due_date && $task->due_date->between($weekStart, $weekEnd))
            ->sum('estimated_minutes');
        $loggedHoursWeek = TimeEntry::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('hours');

        $workPreference = $user->workPreferenceOrCreate($tenant);
        $overCapacity = $workPreference->weekly_target_hours
            ? ($estimatedMinutesWeek > ($workPreference->weekly_target_hours * 60))
            : false;

        return view('schedule-index', [
            'tenant' => $tenant,
            'user' => $user,
            'today' => $today,
            'viewMode' => $viewMode,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'assignedCount' => $assignedCount,
            'overdueCount' => $overdueCount,
            'estimatedMinutesWeek' => $estimatedMinutesWeek,
            'loggedHoursWeek' => (float) $loggedHoursWeek,
            'overdueTasks' => $overdue,
            'todayTasks' => $todayTasks,
            'thisWeekTasks' => $thisWeek,
            'laterTasks' => $later,
            'workPreference' => $workPreference,
            'overCapacity' => $overCapacity,
        ]);
    }
}
