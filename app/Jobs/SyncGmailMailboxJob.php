<?php

namespace App\Jobs;

use App\Models\UserMailAccount;
use App\Models\EmailLog;
use App\Models\Contact;
use App\Models\TenantEmailSetting;
use App\Services\GoogleTokenService;
use App\Services\GmailApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncGmailMailboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_MESSAGES = 250;
    private const MAX_PAGES = 5;
    private const MAX_RETRIES = 3;

    public int $mailAccountId;

    public function __construct(int $mailAccountId)
    {
        $this->mailAccountId = $mailAccountId;
    }

    public function handle(): void
    {
        $account = UserMailAccount::find($this->mailAccountId);
        if (!$account) {
            return;
        }

        $account->sync_in_progress = true;
        $account->last_sync_started_at = now();
        $account->save();

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped_unmatched' => 0,
            'skipped_rules' => 0,
            'errors' => 0,
        ];

        if (!config('services.google.enable_sync')) {
            $account->status = 'error';
            $account->last_error = 'Gmail sync disabled';
            $account->sync_in_progress = false;
            $account->last_sync_finished_at = now();
            $account->last_sync_stats = $stats;
            $account->save();
            return;
        }

        $tokenService = app(GoogleTokenService::class);
        $gmailService = app(GmailApiService::class);

        $token = $tokenService->getAccessToken($account);
        if (!$token) {
            $account->status = $account->status === 'revoked' ? 'revoked' : 'error';
            $account->last_error = $account->last_error ?? 'Unable to get access token';
            $account->sync_in_progress = false;
            $account->last_sync_finished_at = now();
            $account->last_sync_stats = $stats;
            $account->save();
            return;
        }

        try {
            $account->status = 'syncing';
            $account->save();

            $since = $account->last_synced_at
                ? $account->last_synced_at->copy()->subDays(7)
                : now()->subDays(30);
            $query = 'after:' . $since->format('Y/m/d');

            $tenantSettings = TenantEmailSetting::firstOrCreate(
                ['tenant_id' => $account->tenant_id],
                ['log_only_matched_contacts' => true, 'unmatched_behavior' => 'ignore']
            );

            $pageToken = null;
            $page = 0;

            do {
                $page++;
                $resp = $gmailService->listMessages($token, $query, $pageToken, 50);
                $status = $resp['status'] ?? 0;

                if (in_array($status, [429, 503])) {
                    $this->backoff($status);
                    $stats['errors']++;
                    continue;
                }

                $messages = $resp['messages'] ?? [];

                foreach ($messages as $msg) {
                    if ($stats['processed'] >= self::MAX_MESSAGES) {
                        break 2;
                    }

                    $msgId = $msg['id'] ?? null;
                    if (!$msgId) {
                        continue;
                    }

                    $full = $gmailService->getMessage($token, $msgId);
                    if (!$full) {
                        $stats['errors']++;
                        continue;
                    }

                    $parsed = $this->normalizeMessage($full, $account->email_address);
                    if (!$parsed || empty($parsed['provider_message_id'])) {
                        $stats['errors']++;
                        continue;
                    }

                    [$contactId, $companyId] = $this->matchContact($account->tenant_id, $parsed['participants']);

                    if ($tenantSettings->log_only_matched_contacts && !$contactId) {
                        $stats['skipped_rules']++;
                        $stats['processed']++;
                        continue;
                    }

                    $statusVal = 'logged';
                    $needsReview = false;
                    if (!$contactId && !$tenantSettings->log_only_matched_contacts) {
                        $needsReview = $tenantSettings->unmatched_behavior === 'needs_review';
                        $statusVal = $needsReview ? 'needs_review' : 'ignored';
                        if (!$needsReview) {
                            $stats['skipped_unmatched']++;
                        }
                    }

                    $log = EmailLog::updateOrCreate(
                        [
                            'tenant_id' => $account->tenant_id,
                            'mail_account_id' => $account->id,
                            'provider_message_id' => $parsed['provider_message_id'],
                        ],
                        [
                            'user_id' => $account->user_id,
                            'provider' => 'gmail',
                            'provider_thread_id' => $parsed['provider_thread_id'],
                            'direction' => $parsed['direction'],
                            'from_email' => $parsed['from_email'],
                            'to_emails' => $parsed['to_emails'],
                            'cc_emails' => $parsed['cc_emails'],
                            'subject' => $parsed['subject'],
                            'snippet' => $parsed['snippet'],
                            'sent_at' => $parsed['sent_at'],
                            'received_at' => $parsed['sent_at'],
                            'contact_id' => $contactId,
                            'company_id' => $companyId,
                            'status' => $statusVal,
                            'needs_review' => $needsReview,
                        ]
                    );

                    if ($log->wasRecentlyCreated) {
                        $stats['inserted']++;
                    } else {
                        $stats['updated']++;
                    }

                    $stats['processed']++;
                }

                $pageToken = $resp['nextPageToken'] ?? null;
            } while ($pageToken && $page < self::MAX_PAGES);

            $account->last_synced_at = now();
            $account->status = $account->status === 'revoked' ? 'revoked' : 'connected';
            $account->last_error = null;
        } catch (\Throwable $e) {
            $stats['errors']++;
            $account->last_error = $e->getMessage();
            Log::error('SyncGmailMailboxJob failed', [
                'mail_account_id' => $account->id,
                'tenant_id' => $account->tenant_id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $account->sync_in_progress = false;
            $account->last_sync_finished_at = now();
            $account->last_sync_stats = $stats;
            $account->save();
        }
    }

    private function normalizeMessage(array $full, ?string $mailboxEmail): ?array
    {
        $payload = $full['payload']['headers'] ?? [];
        $headers = [];
        foreach ($payload as $h) {
            $headers[$h['name']] = $h['value'];
        }

        $from = $headers['From'] ?? '';
        $to = $headers['To'] ?? '';
        $cc = $headers['Cc'] ?? '';
        $subject = $headers['Subject'] ?? '';
        $dateHeader = $headers['Date'] ?? null;
        $snippet = $full['snippet'] ?? '';

        $sentAt = null;
        if ($dateHeader) {
            $sentAt = Carbon::parse($dateHeader);
        } elseif (isset($full['internalDate'])) {
            $sentAt = Carbon::createFromTimestampMs((int) $full['internalDate']);
        } else {
            $sentAt = now();
        }

        $fromEmail = $this->extractEmail($from);
        $toEmails = $this->extractEmails($to);
        $ccEmails = $this->extractEmails($cc);
        $participants = array_values(array_unique(array_filter(array_merge([$fromEmail], $toEmails, $ccEmails))));

        $direction = 'inbound';
        if ($mailboxEmail && strcasecmp($fromEmail, $mailboxEmail) === 0) {
            $direction = 'outbound';
        } elseif ($mailboxEmail && (in_array(strtolower($mailboxEmail), array_map('strtolower', $toEmails)) || in_array(strtolower($mailboxEmail), array_map('strtolower', $ccEmails)))) {
            $direction = 'inbound';
        }

        return [
            'provider_message_id' => $full['id'] ?? null,
            'provider_thread_id' => $full['threadId'] ?? null,
            'from_email' => $fromEmail,
            'to_emails' => $toEmails,
            'cc_emails' => $ccEmails,
            'subject' => $subject,
            'snippet' => $snippet,
            'sent_at' => $sentAt,
            'direction' => $direction,
            'participants' => $participants,
        ];
    }

    private function extractEmail(string $headerValue): ?string
    {
        if (preg_match('/<([^>]+)>/', $headerValue, $m)) {
            return strtolower(trim($m[1]));
        }
        $trimmed = trim($headerValue);
        return $trimmed !== '' ? strtolower($trimmed) : null;
    }

    private function extractEmails(string $headerValue): array
    {
        $parts = preg_split('/[;,]/', $headerValue);
        $emails = [];
        foreach ($parts as $p) {
            $e = $this->extractEmail($p);
            if ($e) {
                $emails[] = $e;
            }
        }
        return array_values(array_unique($emails));
    }

    private function matchContact(int $tenantId, array $participants): array
    {
        $participants = array_filter($participants);
        if (empty($participants)) {
            return [null, null];
        }

        $contact = Contact::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('email', $participants)
            ->first();

        if ($contact) {
            $companyId = $contact->client_company_id ?? null;
            return [$contact->id, $companyId];
        }

        return [null, null];
    }

    private function backoff(int $status): void
    {
        // rudimentary exponential backoff
        static $attempt = 0;
        $attempt++;
        if ($attempt > self::MAX_RETRIES) {
            return;
        }
        sleep(min(5, pow(2, $attempt)));
    }
}
