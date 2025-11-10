<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchedulerController extends Controller
{
  // Inject models via the constructor (instead of manual instantiation)
  protected Project $projectModel;
  protected Task $taskModel;
  protected TimeEntry $timeModel;

  public function __construct(Project $projectModel, Task $taskModel, TimeEntry $timeModel)
  {
    // Set the authorization middleware
    // Assuming only users with the 'view-reports' ability (e.g., admins) can access.
    $this->middleware('can:view-reports');

    $this->projectModel = $projectModel;
    $this->taskModel = $taskModel;
    $this->timeModel = $timeModel;
  }

  /**
   * Display the main reports index/dashboard.
   * Replaces index() and the adminOnly() wrapper.
   *
   * @return \Illuminate\View\View
   */
  public function index(): View
  {
    // 1. Data Retrieval: Assumes these methods exist on the Eloquent models 
    //    or are handled by specialized Report Services.
    $totalHours = $this->timeModel->getTotalHours(); // Static or instance method
    $projectTimeBudgetData = $this->projectModel->getProjectTimeVsBudget();
    $overdueTasks = $this->taskModel->getOverdueTasks();
    $weeklyTime = $this->timeModel->getWeeklyTimeSummary();
    $userTime = $this->timeModel->getMonthlyUserTime();

    // 2. Return View: Uses Laravel's view helper
    return view('reports.index', [
      'totalHours' => $totalHours,
      'projectTimeBudgetData' => $projectTimeBudgetData,
      'overdueTasks' => $overdueTasks,
      'weeklyTime' => $weeklyTime,
      'userTime' => $userTime
    ]);
  }
}
