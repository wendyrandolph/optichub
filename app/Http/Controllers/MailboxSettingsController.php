<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGmailMailboxJob;
use App\Models\TenantEmailSetting;
use App\Models\UserMailAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MailboxSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tenantParam = $request->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;
        $userId = Auth::id();

        $gmailEnabled = (bool) Config::get('services.google.enable_sync', false);
        $envReady = !empty(Config::get('services.google.client_id')) && !empty(Config::get('services.google.client_secret')) && !empty(Config::get('services.google.redirect'));

        $account = UserMailAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('provider', 'gmail')
            ->first();

        $settings = TenantEmailSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'log_only_matched_contacts' => true,
                'unmatched_behavior' => 'ignore',
            ],
        );

        return view('admin.settings.mailbox', [
            'account' => $account,
            'settings' => $settings,
            'gmailConfigured' => $gmailEnabled && $envReady,
            'gmailEnabled' => $gmailEnabled,
            'tenantId' => $tenantId,
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $tenantParam = $request->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;
        $user = Auth::user();

        $gmailEnabled = (bool) Config::get('services.google.enable_sync', false);
        $gmailConfigured = $gmailEnabled && !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')) && !empty(config('services.google.redirect'));
        if (!$gmailConfigured) {
            return back()->with('flash_error', 'Gmail not configured. Add GOOGLE_CLIENT_ID/SECRET in .env.');
        }

        $state = base64_encode(json_encode(['tenant' => $tenantId, 'nonce' => Str::random(16)]));
        Session::put('gmail_oauth_state', $state);
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/userinfo.email openid https://www.googleapis.com/auth/userinfo.profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        return redirect()->away($authUrl);
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $tenantParam = $request->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;
        $userId = Auth::id();

        $account = UserMailAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('provider', 'gmail')
            ->first();

        if ($account) {
            $account->status = 'disconnected';
            $account->access_token = null;
            $account->refresh_token = null;
            $account->token_expires_at = null;
            $account->last_error = null;
            $account->google_sub = null;
            $account->save();
        }

        return redirect()
            ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
            ->with('flash_success', 'Disconnected Gmail.');
    }

    public function callback(Request $request): RedirectResponse
    {
        $tenantParam = $request->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;
        $gmailConfigured = (bool) Config::get('services.google.enable_sync', false)
            && !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.redirect'));
        if (!$gmailConfigured) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Gmail not configured. Add GOOGLE_CLIENT_ID/SECRET in .env.');
        }

        if ($request->has('error')) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Google authorization canceled.');
        }

        $state = $request->get('state');
        if (!$state || $state !== Session::pull('gmail_oauth_state')) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Invalid OAuth state. Please try connecting again.');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Missing authorization code.');
        }

        $tokenResp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResp->ok()) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Token exchange failed: ' . $tokenResp->json('error', $tokenResp->body()));
        }

        $tokenData = $tokenResp->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;

        if (!$accessToken) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'No access token returned from Google.');
        }

        $userInfoResp = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$userInfoResp->ok()) {
            return redirect()
                ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
                ->with('flash_error', 'Failed to fetch Google profile.');
        }

        $profile = $userInfoResp->json();
        $googleSub = $profile['sub'] ?? null;
        $emailAddr = $profile['email'] ?? null;

        $existing = UserMailAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->where('provider', 'gmail')
            ->first();

        $account = UserMailAccount::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'provider' => 'gmail',
            ],
            [
                'email_address' => $emailAddr,
                'google_sub' => $googleSub,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken ?: ($existing?->refresh_token),
                'token_expires_at' => now()->addSeconds($expiresIn),
                'status' => 'connected',
                'last_error' => null,
            ],
        );

        return redirect()
            ->route('tenant.settings.mailbox', ['tenant' => $tenantId])
            ->with('flash_success', 'Gmail connected.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $tenantParam = $request->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;
        $userId = Auth::id();

        $gmailEnabled = (bool) Config::get('services.google.enable_sync', false);
        $envReady = !empty(Config::get('services.google.client_id')) && !empty(Config::get('services.google.client_secret')) && !empty(Config::get('services.google.redirect'));
        if (!$gmailEnabled || !$envReady) {
            return back()->with('flash_error', 'Gmail sync is disabled or not configured.');
        }

        $account = UserMailAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('provider', 'gmail')
            ->first();

        if (!$account) {
            return back()->with('flash_error', 'No connected mailbox to sync.');
        }

        if ($account->sync_in_progress) {
            return back()->with('flash_error', 'Sync already in progress.');
        }

        $account->sync_in_progress = true;
        $account->last_sync_started_at = now();
        $account->save();

        SyncGmailMailboxJob::dispatch($account->id);

        return back()->with('flash_success', 'Sync started.');
    }
}
