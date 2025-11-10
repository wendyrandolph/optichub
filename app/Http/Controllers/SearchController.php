<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Http\Requests\SearchRequest; // NEW: For input validation
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;

class SearchController extends Controller
{
  /**
   * ReportController constructor.
   */
  public function __construct()
  {
    // Enforce authentication for the search feature
    $this->middleware('auth');
  }

  /**
   * Executes the search and displays results.
   * Replaces the old index() method.
   *
   * @param \App\Http\Requests\SearchRequest $request
   * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
   */
  public function index(SearchRequest $request)
  {
    // Validation handled by SearchRequest, ensuring 'q' is not empty and is trimmed.
    $query = $request->validated('q');

    // Note: The SearchRequest already handles the case where 'q' is empty
    // by redirecting back with an error, so the manual empty check is removed.

    // Use Eloquent scopes for searching, leveraging the ORM.
    $clients = Client::search($query)->get();
    $projects = Project::search($query)->get();
    $tasks = Task::search($query)->get();

    return view('search.results', compact('query', 'clients', 'projects', 'tasks'));
  }
}
