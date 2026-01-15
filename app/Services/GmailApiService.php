<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmailApiService
{
    public function listMessages(string $accessToken, string $queryAfter, ?string $pageToken = null, int $maxResults = 50): array
    {
        $resp = Http::withToken($accessToken)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', array_filter([
                'q' => $queryAfter,
                'maxResults' => $maxResults,
                'pageToken' => $pageToken,
            ]));

        if (!$resp->ok()) {
            Log::warning('Gmail list messages failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return ['messages' => [], 'nextPageToken' => null, 'status' => $resp->status()];
        }

        return [
            'messages' => $resp->json('messages') ?? [],
            'nextPageToken' => $resp->json('nextPageToken') ?? null,
            'status' => $resp->status(),
        ];
    }

    public function getMessage(string $accessToken, string $messageId): ?array
    {
        $resp = Http::withToken($accessToken)
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}", [
                'format' => 'metadata',
                'metadataHeaders' => ['From', 'To', 'Cc', 'Subject', 'Date'],
            ]);

        if (!$resp->ok()) {
            Log::warning('Gmail get message failed', ['id' => $messageId, 'status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }

        return $resp->json();
    }
}
