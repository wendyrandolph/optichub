<?php

namespace App\Services;

use App\Models\UserMailAccount;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTokenService
{
    public function getAccessToken(UserMailAccount $account): ?string
    {
        if (!$account->refresh_token) {
            return null;
        }

        if ($account->token_expires_at && $account->token_expires_at->isFuture() && $account->access_token) {
            return $account->access_token;
        }

        $clientId = Config::get('services.google.client_id');
        $clientSecret = Config::get('services.google.client_secret');

        if (!$clientId || !$clientSecret) {
            return null;
        }

        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        if (!$resp->ok()) {
            $error = $resp->json('error') ?? 'token_refresh_failed';
            $account->status = $error === 'invalid_grant' ? 'revoked' : 'error';
            $account->access_token = null;
            $account->refresh_token = $error === 'invalid_grant' ? null : $account->refresh_token;
            $account->token_expires_at = null;
            $account->last_error = $resp->body();
            $account->save();
            Log::warning('Google token refresh failed', ['account' => $account->id, 'error' => $resp->body()]);
            return null;
        }

        $data = $resp->json();
        $account->access_token = $data['access_token'] ?? null;
        $account->token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
        // Keep existing refresh token when Google omits it on refresh
        if (!empty($data['refresh_token'])) {
            $account->refresh_token = $data['refresh_token'];
        }
        $account->status = 'connected';
        $account->last_error = null;
        $account->save();

        return $account->access_token;
    }
}
