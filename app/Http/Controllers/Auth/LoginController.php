<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth:admin,client,web')->only('logout');
    }

    /**
     * Display the login form.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('auth.login');
    }
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);
        $loginContext = $request->input('login_context', 'team');
        if (! in_array($loginContext, ['team', 'client'], true)) {
            $loginContext = 'team';
        }

    // 1) Lookup user (single users table)
        /** @var \App\Models\User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        // 2) Decide guard
        $role = strtolower((string) $user->role);

        $isPlatformUser =
            in_array($role, ['provider', 'super_admin', 'superadmin'], true)
            || ($role === 'admin' && empty($user->tenant_id)); // admin with no tenant = platform/admin console

        $guard = match (true) {
            $role === 'client' => 'client',
            $isPlatformUser    => 'admin',
            // tenant/workspace users
            default            => 'web',
        };

        if ($loginContext === 'team' && $guard === 'client') {
            $request->session()->regenerateToken();
            return back()
                ->withErrors(['email' => 'You are signing in to the Client Portal. Use your client email and password.'])
                ->with('login_context', 'client')
                ->onlyInput('email');
        }
        if ($loginContext === 'client' && $guard !== 'client') {
            $request->session()->regenerateToken();
            return back()
                ->withErrors(['email' => 'You are signing in to Team/Admin. Use your team email and password.'])
                ->with('login_context', 'team')
                ->onlyInput('email');
        }

        // 3) Login
        Auth::guard($guard)->login($user, $request->boolean('remember'));
        Auth::shouldUse($guard);

        // 4) Regenerate session (prevents fixation)
        $request->session()->regenerate();

        // 5) Sidebar context
        $user->refresh();
        [$orgType, $userRole] = $this->determineSidebarContext($user);
        $request->session()->put([
            'organization_type' => $orgType,
            'role'              => $userRole,
        ]);

        // 6) Log activity (tenant users only)
        if (! empty($user->tenant_id)) {
            ActivityLog::record(
                $user->tenant_id,
                $user->id,
                $user,
                'login',
                'User logged in'
            );
        }

        // Clear any stale intended URL to prevent cross-area redirects
        $request->session()->forget('url.intended');

        // ADMIN area: always land on admin dashboard (providers)
        if ($guard === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // CLIENT portal: land on portal dashboard
        if ($guard === 'client') {
            if (! empty($user->tenant_id)) {
                $request->session()->put('tenant_id', $user->tenant_id);
            }
            return redirect()->route('portal.dashboard');
        }

        // TENANT workspace: send to tenant home (workspace-aware)
        if (! empty($user->tenant_id)) {
            return redirect()->route('tenant.home', ['tenant' => $user->tenant_id]);
        }

        // Final fallback
        return redirect()->to('/dashboard');
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
     * Explicit logout that clears all guards, invalidates session, and regenerates CSRF.
     */
    public function logout(Request $request)
    {
        // Ensure we forget the key we are now using.
        $request->session()->forget(['organization_type', 'role']);

        foreach (['admin', 'client', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Signed out.');
    }
}
