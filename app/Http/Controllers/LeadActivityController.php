<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity; // Use Eloquent Model
use App\Http\Requests\Lead\StoreLeadActivityRequest; // NEW: Dedicated Form Request
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

class LeadActivityController extends Controller
{
  /**
   * Protect the controller with authentication and policies.
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Creates a new activity log entry for a specific lead.
   * Replaces addActivity($leadId)
   *
   * @param \App\Http\Requests\Lead\StoreLeadActivityRequest $request
   * @param \App\Models\Lead $lead (Route Model Binding)
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(StoreLeadActivityRequest $request, Lead $lead)
  {
    // 1. Authorization Check (Ensure the user can modify this lead)
    $this->authorize('update', $lead);

    $validatedData = $request->validated();
    $tenantId = Auth::user()->tenant_id;

    try {
      // 2. Eloquent Relationship Creation
      // This replaces $this->activityModel->create(...) and automatically sets lead_id.
      $lead->activities()->create([
        'activity_type' => $validatedData['activity_type'],
        'description'   => $validatedData['description'],
        'tenant_id'     => $tenantId, // Always include tenant_id for security
        'user_id'       => Auth::id(), // Record who added the activity
      ]);

      // 3. Success Redirect (Replaces $_SESSION flash and manual redirect)
      return Redirect::back()
        ->with('success_message', 'Activity added successfully!');
    } catch (\Throwable $e) {
      Log::error("[lead.activity.store] Failed to add activity: {$e->getMessage()}");

      // 4. Error Redirect
      return Redirect::back()
        ->withInput()
        ->with('error_message', 'Failed to add activity.');
    }
  }
}
