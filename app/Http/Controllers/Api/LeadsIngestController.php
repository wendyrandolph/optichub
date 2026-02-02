<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyLeadInbox;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Tenant;
use App\Models\TenantLeadSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadsIngestController extends Controller
{
    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $settings = TenantLeadSetting::query()->firstOrCreate([
            'tenant_id' => $tenant->id,
        ]);

        if (empty($settings->inbound_secret)) {
            return response()->json(['success' => false, 'message' => 'Inbound secret not configured.'], 403);
        }

        if (!$this->passesOriginAllowlist($request, (array) ($settings->allowlist_domains ?? []))) {
            return response()->json(['success' => false, 'message' => 'Origin not allowed.'], 403);
        }

        if (!$this->passesAuth($request, $settings->inbound_secret)) {
            $hasToken = (string) $request->header('X-Renlo-Token');
            $hasSignature = (string) $request->header('X-Renlo-Signature');
            $status = $hasToken !== '' || $hasSignature !== '' ? 403 : 401;
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], $status);
        }

        if ($this->isSpam($request)) {
            return response()->json(['success' => true, 'spam' => true]);
        }

        $payload = $this->payloadData($request);

        $firstName = $this->payloadValue($payload, ['first_name', 'firstName', 'fname']);
        $lastName = $this->payloadValue($payload, ['last_name', 'lastName', 'lname']);
        $name = $this->payloadValue($payload, ['name', 'full_name', 'fullName']);
        $email = $this->payloadValue($payload, ['email', 'email_address']);
        $phone = $this->payloadValue($payload, ['phone', 'phone_number']);
        $company = $this->payloadValue($payload, ['company', 'company_name', 'organization']);
        $message = $this->payloadValue($payload, ['message', 'notes', 'comment', 'details']);
        $source = $this->payloadValue($payload, ['source']) ?? 'website';
        $formName = $this->payloadValue($payload, ['form_name', 'formName']);
        $sourceUrl = $this->payloadValue($payload, ['source_url', 'page_url', 'pageUrl'])
            ?? $request->headers->get('referer');

        if (!$name) {
            $name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }
        if (!$name) {
            $name = $email ?? $phone ?? 'New Lead';
        }

        $knownKeys = [
            'name', 'full_name', 'fullName',
            'first_name', 'firstName', 'fname',
            'last_name', 'lastName', 'lname',
            'email', 'email_address',
            'phone', 'phone_number',
            'company', 'company_name', 'organization',
            'message', 'notes', 'comment', 'details',
            'source', 'source_url', 'page_url', 'pageUrl',
            'form_name', 'formName',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'website', 'started_at', 'renlo_token',
        ];

        $meta = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $knownKeys, true)) {
                continue;
            }
            if (in_array($key, ['_token', '_method'], true)) {
                continue;
            }
            $meta[$key] = $this->normalizeMetaValue($value);
        }

        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'message' => $message,
            'status' => 'new',
            'status_changed_at' => now(),
            'priority' => 'normal',
            'source' => $source,
            'source_url' => $sourceUrl,
            'form_name' => $formName,
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'meta' => empty($meta) ? null : $meta,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        if ($message) {
            LeadEvent::create([
                'tenant_id' => $tenant->id,
                'lead_id' => $lead->id,
                'type' => 'note',
                'payload' => ['note' => $message, 'source' => 'ingest'],
            ]);
        }

        NotifyLeadInbox::dispatch($tenant->id, $lead->id);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    protected function passesAuth(Request $request, string $secret): bool
    {
        $token = (string) $request->header('X-Renlo-Token');
        if ($token === '') {
            $token = (string) $request->input('renlo_token');
        }
        if ($token !== '') {
            return hash_equals($secret, $token);
        }

        $signature = (string) $request->header('X-Renlo-Signature');
        if ($signature !== '') {
            $payload = $request->getContent();
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        return false;
    }

    protected function passesOriginAllowlist(Request $request, array $allowlist): bool
    {
        $domains = array_values(array_filter(array_map([$this, 'normalizeDomain'], $allowlist)));
        if (empty($domains)) {
            return true;
        }

        $origin = (string) $request->headers->get('origin');
        $referer = (string) $request->headers->get('referer');
        $host = $this->extractHost($origin) ?? $this->extractHost($referer);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);
        foreach ($domains as $domain) {
            if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    protected function isSpam(Request $request): bool
    {
        $honeypot = trim((string) $request->input('website'));
        if ($honeypot !== '') {
            return true;
        }

        $startedAt = $request->input('started_at');
        if (!$startedAt) {
            return false;
        }

        $submittedAt = $this->parseStartedAt($startedAt);
        if (!$submittedAt) {
            return false;
        }

        return $submittedAt->diffInSeconds(now()) < 2;
    }

    protected function payloadData(Request $request): array
    {
        $json = $request->json()->all();
        if (!empty($json)) {
            return $json;
        }

        return $request->all();
    }

    protected function payloadValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }
            return is_scalar($value) ? (string) $value : json_encode($value);
        }

        return null;
    }

    protected function normalizeMetaValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    protected function normalizeDomain(string $domain): ?string
    {
        $domain = trim(strtolower($domain));
        if ($domain === '') {
            return null;
        }

        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain !== '' ? $domain : null;
    }

    protected function extractHost(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        return $parts['host'] ?? null;
    }

    protected function parseStartedAt(mixed $value): ?Carbon
    {
        if (is_numeric($value)) {
            $numeric = (float) $value;
            if ($numeric > 1000000000000) {
                $numeric = $numeric / 1000;
            }
            return Carbon::createFromTimestamp($numeric);
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
