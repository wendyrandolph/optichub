<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoginController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Display the login form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return $this->showLoginForm();
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return $this->login($request);
    }

    /**
     * Determine where to redirect users after login.
     */
    protected function redirectTo(): string
    {
        $user = Auth::user();

        if ($user && in_array($user->role ?? null, ['provider', 'admin', 'super_admin', 'superadmin'], true)) {
            return 'admins.dashboard';
        }
        //Tenant aware fallback 
        return route('tenant.home', ['tenant' => $user->tenant_id]);
    }

    /**
     * Determine the correct sidebar context (organization type and role)
     * based on the authenticated user's database role field AND tenant relationship.
     *
     * @param \App\Models\User $user
     * @return array
     */
    protected function determineSidebarContext($user): array
    {
        $rawRole = strtolower($user->role ?? 'unknown');
        $orgType = null;
        $role = null;

        // Log::debug("--- determineSidebarContext START ---");
        // Log::debug("User ID: {$user->id}, Raw Role: {$rawRole}, Tenant ID: {$user->tenant_id}");


        switch ($rawRole) {
            // --- PLATFORM/PROVIDER ROLES (Should NEVER be null) ---
            case 'super_admin':
            case 'superadmin':
            case 'platform_admin':
            case 'provider': // This is the role you are using!
            case 'admin':
            case 'provider_employee':
            case 'provider_client':
                // Correct: These platform roles are always assigned the 'provider' organization type
                $orgType = 'provider';
                if (in_array($rawRole, ['super_admin', 'superadmin', 'platform_admin', 'provider', 'admin'])) {
                    $role = 'admin';
                } elseif ($rawRole === 'provider_employee') {
                    $role = 'employee';
                } elseif ($rawRole === 'provider_client') {
                    $role = 'client';
                }
                Log::debug("Case: Platform Role. Org Type set to 'provider'.");
                break;


            // --- TENANT-ATTACHED ROLES (Requires tenant relationship lookup) ---
            case 'tenant_admin':
            case 'tenant_employee':
            case 'client_org_client':
                if ($user->tenant) {
                    // Correct: Tenant roles inherit their org type from the linked tenant's 'type' column
                    $orgType = $user->tenant->type ?? null;

                    if ($rawRole === 'tenant_admin') {
                        $role = 'admin';
                    } elseif ($rawRole === 'tenant_employee') {
                        $role = 'employee';
                    } elseif ($rawRole === 'client_org_client') {
                        $role = 'client';
                    }
                } else {
                    Log::error("User with tenant-based role '{$rawRole}' (ID: {$user->id}) is missing a linked Tenant record.");
                }
                break;

            default:
                Log::warning("Unmapped role found: {$rawRole}");
                break;
        }

        // Log::debug("--- determineSidebarContext END. Result: [{$orgType}, {$role}] ---");
        return [$orgType, $role];
    }


    /**
     * Override the default post-authentication redirect.
     */
    protected function authenticated(Request $request, $user)
    {
        // CRITICAL: Refresh to ensure relationships are loaded before accessing them.
        $user->refresh();

        $currentRole = $user->role ?? 'NULL/Unset';

        // --- Set the organization context for the sidebar ---
        [$orgType, $userRole] = $this->determineSidebarContext($user);

        // Store the necessary UI context in the session
        // FIX: The organization type must be stored under the key 'organization_type' 
        // to be retrieved correctly by other controllers.
        $request->session()->put([
            'organization_type' => $orgType,
            'role' => $userRole,
        ]);

        // GUARANTEED LOG: This will show what the session was set to.
        Log::info("Context set for user ID {$user->id}. organization_type: {$orgType}, role: {$userRole}");

        // Handle the redirect based on the reliable currentRole
        if (in_array($currentRole, ['provider', 'admin', 'super_admin', 'superadmin'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended($this->redirectTo());
    }

    /**
     * Log the user out and send them back to the login screen.
     */
    public function logout(Request $request)
    {
        // Ensure we forget the key we are now using.
        $request->session()->forget(['organization_type', 'role']);
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
