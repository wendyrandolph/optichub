<?php

namespace App\Http\Controllers;

use App\Models\Task;       // Renamed from 'Tasks' for Laravel convention
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon; // Laravel's DateTime helper
use DateTimeImmutable; // Used in original logic, kept for clarity


class CalendarController extends Controller
{
  // The constructor handles dependency injection, replacing $pdo instantiation
  // We don't need explicit properties for models anymore.

  // We'll enforce authentication via middleware on the route group instead of in the controller.

  /**
   * Renders the main calendar view.
   * Replaces index()
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    // Replaces $this->view('calendar/index');
    return view('calendar.index');
  }

  /**
   * Fetches tasks and time entries, formats them, and returns a JSON response
   * for a calendar application (e.g., FullCalendar).
   * Replaces getEvents()
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getEvents(): JsonResponse
  {
    // Apply Multi-Tenancy: Filter data only for the current user's organization.
    $tenantId = Auth::user()->tenant_id;

    // Eager load related data to avoid N+1 queries
    $tasks = Task::where('tenant_id', $tenantId)
      ->with(['assignedUser', 'project']) // Assuming relationships exist
      ->get();

    $entries = TimeEntry::where('tenant_id', $tenantId)
      ->with(['user', 'project', 'task'])
      ->get();

    $events = [];
    $today = Carbon::today(); // Laravel's Carbon helper

    // --- 1. Format Tasks ---
    foreach ($tasks as $task) {
      // Check if due_date is set
      if (empty($task->due_date)) {
        continue;
      }

      $assignType = strtolower($task->assign_type ?? '');
      $status = strtolower($task->status ?? '');

      // Use Carbon for date comparisons
      $dueDate = Carbon::parse($task->due_date);
      $isOverdue = $dueDate->startOfDay()->lessThan($today) && $status !== 'completed';

      // Determine Color (Logic remains the same)
      $color = match (true) {
        $isOverdue => '#dc3545',                            // red
        $assignType === 'user' && $status !== 'completed' => '#D96B45', // orange
        $assignType === 'client' && $status !== 'completed' => '#FFC107', // yellow
        $assignType === 'client' && $status === 'completed' => '#4CAF50', // green
        $assignType === 'user' && $status === 'completed' => '#1F3C66', // dark blue
        default => '#2E5D95',                               // fallback
      };

      $events[] = [
        'id' => 'task-' . $task->id,
        'title' => '📝 ' . ($task->title ?? 'Untitled'),
        'start' => $task->due_date,
        'color' => $color,
        'extendedProps' => [
          'type' => 'task',
          'status' => $task->status ?? '',
          'assigned' => $task->assignedUser->name ?? '', // Access relationship name
          'description' => $task->description ?? ''
        ]
      ];
    }

    // --- 2. Format Time Entries ---
    foreach ($entries as $entry) {
      // Your original entry logic calculated end time manually. 
      // If your TimeEntry model now stores start_time and end_time, use those.
      // Assuming it stores start_time and duration (hours):
      $start = Carbon::parse($entry->start_time);
      $end = $start->clone()->addHours($entry->hours);

      $events[] = [
        'id' => 'time-' . $entry->id,
        'title' => '⏱ ' . $entry->description,
        'start' => $entry->start_time,
        'end' => $end->toDateTimeString(), // Ensure it's formatted for the calendar
        'color' => '#EA7D51',
        'extendedProps' => [
          'type' => 'time',
          'hours' => (float)$entry->hours,
          'user' => $entry->user->name ?? '',
          'project' => $entry->project->name ?? '',
          'task' => $entry->task->title ?? ''
        ]
      ];
    }

    // 3. Return JSON response (Replaces header/echo/exit)
    return response()->json($events);
  }
}
