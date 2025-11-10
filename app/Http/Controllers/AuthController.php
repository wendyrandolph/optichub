<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
  /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen.
    |
    */

  use AuthenticatesUsers;

  /**
   * Where to redirect users after login.
   * * NOTE: We keep this as a fallback, but the actual redirection is now 
   * handled dynamically in the 'authenticated' method below.
   *
   * @var string
   */
  protected $redirectTo = '/home';

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest')->except('logout');
  }

  /**
   * Handle a successful authentication attempt.
   * * We override this method from the AuthenticatesUsers trait to redirect 
   * the user to their specific tenant's home page.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  mixed  $user
   * @return \Illuminate\Http\Response
   */
  protected function authenticated(Request $request, $user)
  {
    // 1. Ensure the user is properly linked to a tenant.
    // (Assuming the User model has a 'tenant' relationship)
    if (!$user->tenant || !$user->tenant->slug) {
      // Log the user out and show an error if they don't have a valid tenant assigned.
      Auth::logout();
      return redirect('/login')->withErrors(['tenant_error' => 'You are not assigned to a valid tenant. Please contact support.']);
    }

    $tenantSlug = $user->tenant->slug;

    // 2. Redirect to the tenant-scoped home route.
    // Assuming your main post-login route is named 'tenant.home' (or 'tenant.dashboard')
    // and requires the '{tenant}' slug as the first parameter.
    return redirect()->route('tenant.home', [
      'tenant' => $tenantSlug
    ]);
  }
}
