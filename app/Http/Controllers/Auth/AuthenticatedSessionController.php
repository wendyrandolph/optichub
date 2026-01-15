<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view (guard-specific if provided).
     */
    public function create(Request $request): View
    {
        $view = $request->route('view', 'auth.login'); // e.g. 'auth.admin-login' or 'auth.client-login'
        return view($view);
    }

    /**
     * Handle an incoming authentication request (guard-aware).
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $guard = $request->route('guard', 'web');     // 'admin' | 'client' | 'web'
        $remember = $request->boolean('remember');

        if (Auth::guard($guard)->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Optional: enforce role by guard (comment out if not needed)
            // if ($guard === 'admin' && Auth::guard($guard)->user()?->role !== 'admin') {
            //     Auth::guard($guard)->logout();
            //     return back()->withErrors(['email' => 'Not authorized for admin access.']);
            // }

            // Send each guard to its home
            $user = Auth::guard($guard)->user();
            $home = match ($guard) {
                'admin'  => '/admin',
                'client' => '/portal',
                default  => ! empty($user?->tenant_id)
                    ? route('tenant.home', ['tenant' => $user->tenant_id], absolute: false)
                    : route('dashboard', absolute: false),
            };

            return redirect()->intended($home);
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->onlyInput('email');
    }

    /**
     * Destroy an authenticated session (guard-aware).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $guard = $request->route('guard', 'web');
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(
            $guard === 'admin'  ? route('admin.login') : ($guard === 'client' ? route('portal.login') : route('login'))
        );
    }
}
