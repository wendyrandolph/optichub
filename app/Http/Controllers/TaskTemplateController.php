<?php

namespace App\Http\Controllers;

use App\Models\TaskTemplate; // Assuming this is the Eloquent Model
use App\Http\Requests\TaskTemplate\StoreTaskTemplateRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class TaskTemplateController extends Controller
{
  public function __construct()
  {
    // Enforce authentication for all template management actions
    $this->middleware('auth');

    // You might add authorization middleware here if specific roles are required
    // $this->middleware('can:manage-templates'); 
  }

  /**
   * Display a listing of the resource.
   * Replaces index()
   *
   * @return \Illuminate\View\View
   */
  public function index(): View
  {
    // Fetch all templates using Eloquent
    $templates = TaskTemplate::latest()->get();

    // Laravel's view helper automatically handles file extensions
    return view('task_templates.index', compact('templates'));
  }

  /**
   * Store a newly created resource in storage.
   * Replaces the logic inside create() when REQUEST_METHOD is POST.
   *
   * @param \App\Http\Requests\TaskTemplate\StoreTaskTemplateRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(StoreTaskTemplateRequest $request)
  {
    // Validation and sanitization handled by StoreTaskTemplateRequest
    $data = $request->validated();

    try {
      TaskTemplate::create($data);

      // Replaces $_SESSION['success_message'] and header redirect
      return Redirect::route('task-templates.index')
        ->with('success_message', 'Task template created successfully!');
    } catch (\Throwable $e) {
      // Handle database errors
      return Redirect::route('task-templates.index')
        ->with('error_message', 'Failed to create task template.');
    }
  }

  /**
   * Remove the specified resource from storage.
   * Replaces delete($id)
   *
   * @param \App\Models\TaskTemplate $taskTemplate (Route Model Binding)
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(TaskTemplate $taskTemplate)
  {
    // Optional: Add authorization check here if needed (e.g., policy)
    // $this->authorize('delete', $taskTemplate);

    try {
      $taskTemplate->delete();

      // Replaces $_SESSION['success_message'] and header redirect
      return Redirect::route('task-templates.index')
        ->with('success_message', 'Task template deleted successfully!');
    } catch (\Throwable $e) {
      // Handle errors, perhaps if the template is referenced elsewhere
      return Redirect::route('task-templates.index')
        ->with('error_message', 'Failed to delete task template.');
    }
  }

  // Note: The original controller was missing the "show create form" method, 
  // so it should be added here for completeness if you use resourceful routing.
  // public function create(): View 
  // {
  //     return view('task_templates.create');
  // }
}
