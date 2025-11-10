<?php

namespace App\Http\Controllers;

use App\Models\User;
//use App\Models\Organization;
use App\Models\OnboardingToken;
use App\Services\ApiKeyService;
use App\Http\Requests\Onboarding\SetPasswordRequest; // NEW: For password validation
use App\Http\Requests\Onboarding\RequestLinkRequest; // NEW: For email validation
use App\Http\Requests\Onboarding\UpdateCompanyRequest; // NEW: For company validation
use App\Mail\OnboardingLinkMailable; // NEW: For sending the link
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class OnboardingController extends Controller
{
  protected ApiKeyService $apiKeyService;

  public function __construct(ApiKeyService $apiKeyService)
  {
    // Middleware will be applied in routes/web.php or using ->middleware() in the routes.
    $this->apiKeyService = $apiKeyService;
  }

  // ----- Step 0: Set password (public via token) -----

  /**
   * Show the set password form after token validation.
   * Replaces setPasswordForm()
   */
  public function setPasswordForm(Request $request): View
  {
    $token = $request->query('token');

    // Eloquent/Service handles token lookup and expiration check
    $onboardingToken = OnboardingToken::findValid($token);

    if (!$onboardingToken) {
      return view('onboarding.expired');
    }

    return view('onboarding.set_password', [
      'token' => $token,
    ]);
  }

  /**
   * Handle the password submission and log the user in.
   * Replaces handleSetPassword() - uses Form Request for validation
   */
  public function handleSetPassword(SetPasswordRequest $request)
  {
    $token = $request->input('token');
    $validated = $request->validated();

    $onboardingToken = OnboardingToken::findValid($token);

    // This check acts as a final fail-safe for an expired/invalid token
    if (!$onboardingToken) {
      return Redirect::route('onboarding.expired');
    }

    $user = User::find($onboardingToken->user_id);

    DB::beginTransaction();
    try {
      // 1. Update User password
      $user->password = Hash::make($validated['password']);
      $user->must_change_password = false;
      $user->save();

      // 2. Mark Token as used
      $onboardingToken->used_at = now();
      $onboardingToken->save();

      DB::commit();

      // 3. Login the user (Replaces Auth::loginByUserId)
      Auth::login($user);

      // 4. Redirect to the next step
      return Redirect::route('onboarding.company');
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error('[onboarding] set-password failed: ' . $e->getMessage());

      // Redirect back with an error flash message
      return Redirect::back()->withInput()->withErrors(['general' => 'We couldn’t set your password. Please try again.']);
    }
  }
  /**
   * Show the company setup form.
   * Replaces companyForm() - uses middleware for auth checks
   */
  public function companyForm(): View
  {
    // Middleware ('auth', 'can:onboard') should enforce access
    $tenantId = Auth::user()->tenant_id;

    // Eloquent handles the lookup
    $org = Organization::find($tenantId);

    return view('onboarding.company', [
      'org' => $org,
    ]);
  }


  /** * Save organization basics and continue.
   * Replaces handleCompany() - uses Form Request for validation
   */
  public function handleCompany(UpdateCompanyRequest $request)
  {
    $validated = $request->validated();
    $orgId = Auth::user()->tenant_id;

    $org = Organization::find($orgId);

    // Update with validated data
    $ok = $org->update($validated);

    if (!$ok) {
      return Redirect::back()->withErrors(['general' => 'We couldn’t save your company info. Please try again.']);
    }

    return Redirect::route('onboarding.api-key');
  }


  // ----- Step 2: API key (auth) -----

  /** * Show existing keys and the form to generate a new key.
   * Replaces apiKeyForm()
   */
  public function apiKeyForm(): View
  {
    $tenantId = Auth::user()->tenant_id;

    // Eloquent/Model Scope handles the lookup
    $keys = ApiKey::listActiveByTenant($tenantId);

    // Use Laravel's session helpers for flash data
    $newPlainKey = session('flash_new_key');

    // Data is passed directly to the view; session is cleared by Laravel automatically
    return view('onboarding.api_key', [
      'keys' => $keys,
      'newPlainKey' => $newPlainKey,
    ]);
  }

  /**
   * Generate a new API key using a dedicated service.
   * Replaces handleApiKey()
   */
  public function handleApiKey()
  {
    $tenantId = Auth::user()->tenant_id;

    try {
      // Use Dependency Injected Service to handle key creation and hashing
      $plainKey = $this->apiKeyService->issue(
        $tenantId,
        'Website Integration',
        ['leads:write', 'events:read']
      );

      // Flash the plain key (show it once)
      session()->flash('flash_new_key', $plainKey);

      return Redirect::route('onboarding.api-key');
    } catch (\Throwable $e) {
      Log::error('[apiKey] issue failed: ' . $e->getMessage());

      // Redirect back with an error
      return Redirect::route('onboarding.api-key')->withErrors(['general' => 'We could not create an API key. Please try again.']);
    }
  }


  // ----- Step 3: Finish (auth) -----

  /**
   * Marks onboarding complete and redirects to the dashboard.
   * Replaces finish()
   */
  public function finish()
  {
    $this->middleware('auth'); // Ensure user is logged in
    $tenantId = Auth::user()->tenant_id;

    // Use Eloquent or Query Builder to update the organization record
    Organization::where('id', $tenantId)->update(['onboarded_at' => now()]);

    // The columnExists helper is no longer necessary; use migrations and Eloquent features.

    return Redirect::route('dashboard');
  }
  /**
   * Show the "request link" form.
   * Replaces requestLinkForm()
   */
  public function requestLinkForm(): View
  {
    return view('onboarding.request_link');
  }

  /**
   * Handle the request link submission.
   * Replaces handleRequestLink() - uses Form Request for validation
   */
  public function handleRequestLink(RequestLinkRequest $request)
  {
    // 1. Validation and CSRF handled by RequestLinkRequest
    $email = $request->validated('email');

    // 2. Look up user by email
    $user = User::where('email', $email)->first();

    // Always say “sent” even if user not found (don’t leak existence)
    if ($user) {
      $token = bin2hex(random_bytes(32));

      // 3. Create fresh onboarding token (Eloquent/Service handles this)
      OnboardingToken::create([
        'user_id' => $user->id,
        'token' => $token,
        'expires_at' => now()->addHours(48),
      ]);

      $magicUrl = route('onboarding.set-password', ['token' => $token]);

      // 4. Send Mailable (Replaces best-effort @mail())
      Mail::to($user->email)->send(new OnboardingLinkMailable($magicUrl));
    }

    return Redirect::route('onboarding.link-sent');
  }

  /**
   * Show the "link sent" confirmation page.
   * Replaces linkSent()
   */
  public function linkSent(): View
  {
    return view('onboarding.link_sent');
  }

  /**
   * Show the "token expired/invalid" page.
   */
  public function expired(): View
  {
    return view('onboarding.expired');
  }
}
