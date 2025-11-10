<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartTrialRequest;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class TrialController extends Controller
{
  /**
   * @var OrganizationService
   */
  protected $organizationService;

  /**
   * Use dependency injection to get the OrganizationService.
   */
  public function __construct(OrganizationService $organizationService)
  {
    $this->organizationService = $organizationService;
  }

  /**
   * Show the trial signup form view.
   */
  public function showTrialForm()
  {
    return view('auth.trial-signup');
  }

  /**
   * Handles the submission of the trial form, creates organization and user.
   *
   * @param StartTrialRequest $request Handles validation and rate limiting
   * @return RedirectResponse
   */
  public function start(StartTrialRequest $request): RedirectResponse
  {
    try {
      $data = $request->validated();

      // The OrganizationService handles the transactional creation of the
      // organization, the initial user, and the setup token.
      $result = $this->organizationService->startTrial(
        $data['email'],
        $data['company']
      );

      // Log successful trial creation
      Log::info("New trial started.", [
        'org_id' => $result['organization_id'],
        'user_id' => $result['user_id'],
        'email' => $data['email'],
      ]);

      // Redirect the user to the secure onboarding flow (set password)
      // The token is used to authenticate the user for this one-time action.
      return redirect()->route('onboarding.setup-password', [
        'token' => $result['token']
      ])->with('success', 'Your trial has been started! Check your email to set your password.');
    } catch (\Throwable $e) {
      // Log the exception
      Log::error("Trial signup failed: " . $e->getMessage(), [
        'exception' => $e,
        'email' => $request->input('email')
      ]);

      // Redirect back with a general error message
      return back()->withInput()->withErrors([
        'error' => 'Could not start your trial. Please try again or contact support.'
      ]);
    }
  }
}
