<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyLeadInbox;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TradeLeadEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadInboxController extends Controller
{
    public function store(Request $request, string $inboxKey): JsonResponse
    {
        $tenant = Tenant::query()->where('inbox_key', $inboxKey)->first();
        if (!$tenant) {
            return response()->json(['ok' => false, 'message' => 'Inbox not found.'], 404);
        }

        $payload = $request->all();
        $mapping = $this->resolveMapping($tenant, $payload);
        $standard = $mapping['standard'] ?? [];

        $resolved = [
            'name' => $this->payloadValue($payload, $standard['name'] ?? null, ['name', 'full_name']),
            'email' => $this->payloadValue($payload, $standard['email'] ?? null, ['email', 'email_address']),
            'phone' => $this->payloadValue($payload, $standard['phone'] ?? null, ['phone', 'phone_number']),
            'notes' => $this->payloadValue($payload, $standard['notes'] ?? null, ['message', 'notes']),
            'description' => $this->payloadValue($payload, $standard['description'] ?? null, ['description', 'details']),
            'preferred_time' => $this->payloadValue($payload, $standard['preferred_time'] ?? null, ['preferred_time']),
            'service_address' => $this->payloadValue($payload, $standard['service_address'] ?? null, ['service_address', 'address']),
        ];

        $validator = Validator::make($resolved, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $validator->after(function ($v) use ($resolved) {
            $hasContact = !empty($resolved['name']) || !empty($resolved['email']) || !empty($resolved['phone']);
            if (!$hasContact) {
                $v->errors()->add('name', 'Please provide a name, email, or phone.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $name = trim((string) ($resolved['name'] ?? ''));
        if ($name === '') {
            $name = $resolved['email'] ?? $resolved['phone'] ?? 'New Lead';
        }

        $customFields = [];
        foreach ((array) ($mapping['custom'] ?? []) as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $value = $payload[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $customFields[] = [
                'label' => $row['label'] ?? $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
            ];
        }

        $reservedKeys = array_filter(array_merge(
            array_values($standard),
            array_column((array) ($mapping['custom'] ?? []), 'key'),
            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'page_url', 'form_name']
        ));

        $extraFields = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $reservedKeys, true)) {
                continue;
            }
            if (in_array($key, ['_token', '_method'], true)) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $extraFields[$key] = json_encode($value);
            } else {
                $extraFields[$key] = (string) $value;
            }
        }

        $sourceDetail = [
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'page_url' => $payload['page_url'] ?? null,
            'referrer' => $request->headers->get('referer'),
            'form_name' => $payload['form_name'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if (!empty($customFields)) {
            $sourceDetail['custom_fields'] = $customFields;
        }
        if (!empty($extraFields)) {
            $sourceDetail['extra_fields'] = $extraFields;
        }

        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $resolved['email'] ?? null,
            'phone' => $resolved['phone'] ?? null,
            'notes' => $resolved['notes'] ?? null,
            'description' => $resolved['description'] ?? null,
            'preferred_time' => $resolved['preferred_time'] ?? null,
            'service_address' => $resolved['service_address'] ?? null,
            'status' => 'new',
            'source' => 'website',
            'source_detail' => array_filter($sourceDetail, fn($value) => $value !== null && $value !== ''),
            'captured_at' => now(),
        ]);

        TradeLeadEvent::create([
            'tenant_id' => $tenant->id,
            'lead_id' => $lead->id,
            'type' => 'created',
            'payload_json' => [
                'source' => 'website',
                'form_name' => $payload['form_name'] ?? null,
            ],
        ]);

        NotifyLeadInbox::dispatch($tenant->id, $lead->id);

        return response()->json(['ok' => true]);
    }

    protected function resolveMapping(Tenant $tenant, array $payload): array
    {
        $defaults = [
            'standard' => [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'notes' => 'message',
                'description' => 'description',
                'preferred_time' => 'preferred_time',
                'service_address' => 'service_address',
            ],
            'custom' => [],
        ];

        $mapping = (array) ($tenant->lead_field_mapping ?? []);
        $default = (array) ($mapping['default'] ?? $mapping);
        $forms = (array) ($mapping['forms'] ?? []);
        $formName = trim((string) ($payload['form_name'] ?? ''));

        $selected = $default;
        if ($formName !== '' && !empty($forms)) {
            foreach ($forms as $form) {
                if (strcasecmp($formName, (string) ($form['name'] ?? '')) === 0) {
                    $selected = $form;
                    break;
                }
            }
        }

        $standard = array_filter(
            (array) ($selected['standard'] ?? []),
            fn($value) => is_string($value) && trim($value) !== ''
        );

        return [
            'standard' => array_replace($defaults['standard'], $standard),
            'custom' => (array) ($selected['custom'] ?? []),
        ];
    }

    protected function payloadValue(array $payload, ?string $key, array $fallbacks): ?string
    {
        $keys = array_filter(array_merge([$key], $fallbacks));
        foreach ($keys as $candidate) {
            if (!array_key_exists($candidate, $payload)) {
                continue;
            }
            $value = $payload[$candidate];
            if ($value === null || $value === '') {
                continue;
            }
            return is_scalar($value) ? (string) $value : json_encode($value);
        }
        return null;
    }
}
