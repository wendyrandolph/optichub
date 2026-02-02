<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ClientMagicLinkMailable;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientMagicLinkController extends Controller
{
    public function create()
    {
        return view('portal.auth.magic-link');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])
            ->where('role', 'client')
            ->first();

        if ($user) {
            $token = Str::random(64);
            $hash = hash('sha256', $token);

            MagicLink::create([
                'user_id' => $user->id,
                'token_hash' => $hash,
                'expires_at' => now()->addDays(7),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);

            $link = route('portal.magic.consume', ['token' => $token]);

            Mail::to($user->email)->send(new ClientMagicLinkMailable($user, $link));
        }

        return back()->with('status', 'If that email is in our system, a login link has been sent.');
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);

        $magicLink = DB::transaction(function () use ($hash) {
            $link = MagicLink::where('token_hash', $hash)
                ->where('expires_at', '>=', now())
                ->lockForUpdate()
                ->first();

            if (! $link) {
                return null;
            }

            $link->update(['used_at' => now()]);

            return $link;
        });

        if (! $magicLink) {
            return redirect()->route('portal.magic.expired');
        }

        $user = $magicLink->user;

        if (! $user || $user->role !== 'client') {
            return redirect()->route('portal.magic.expired');
        }

        $clientGuard = Auth::guard('client');
        $clientGuard->setRememberDuration(60 * 24 * 60); // 60 days
        $clientGuard->login($user, true);
        Auth::shouldUse('client');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['tenant_id' => $user->tenant_id]);

        return redirect()->route('portal.dashboard');
    }

    public function expired()
    {
        return view('portal.auth.magic-expired');
    }
}
