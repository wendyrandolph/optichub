<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyLeadInbox;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        if (empty($tenant->inbox_key)) {
            $tenant->update(['inbox_key' => $this->generateInboxKey()]);
        }

        $webhookUrl = route('public.leads.inbox', ['inbox_key' => $tenant->inbox_key]);
        $mapping = $this->normalizeMapping($tenant->lead_field_mapping ?? []);

        return view('trades.settings.leads', [
            'tenant' => $tenant,
            'webhookUrl' => $webhookUrl,
            'leadFieldMapping' => $mapping,
        ]);
    }

    public function testLead(Tenant $tenant): RedirectResponse
    {
        if (empty($tenant->inbox_key)) {
            $tenant->update(['inbox_key' => $this->generateInboxKey()]);
        }

        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sample Lead',
            'email' => 'sample.lead@example.com',
            'phone' => '555-555-0101',
            'status' => 'new',
            'source' => 'website',
            'notes' => 'Generated from Trades Settings → Leads.',
            'captured_at' => now(),
        ]);

        NotifyLeadInbox::dispatch($tenant->id, $lead->id);

        return redirect()
            ->route('tenant.trades.settings.leads', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Test lead created.');
    }

    public function updateRecipients(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'lead_notification_recipients' => ['nullable', 'string'],
        ]);

        $raw = trim((string) ($data['lead_notification_recipients'] ?? ''));
        $recipients = $raw === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $raw))));

        $tenant->update([
            'lead_notification_recipients' => $recipients,
        ]);

        return redirect()
            ->route('tenant.trades.settings.leads', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Notification recipients updated.');
    }

    public function updateMapping(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'mapping' => ['nullable', 'array'],
            'mapping.default' => ['nullable', 'array'],
            'mapping.default.standard' => ['nullable', 'array'],
            'mapping.default.custom' => ['nullable', 'array'],
            'mapping.default.custom.*.key' => ['nullable', 'string', 'max:100'],
            'mapping.default.custom.*.label' => ['nullable', 'string', 'max:120'],
            'mapping.forms' => ['nullable', 'array'],
            'mapping.forms.*.name' => ['nullable', 'string', 'max:120'],
            'mapping.forms.*.standard' => ['nullable', 'array'],
            'mapping.forms.*.custom' => ['nullable', 'array'],
            'mapping.forms.*.custom.*.key' => ['nullable', 'string', 'max:100'],
            'mapping.forms.*.custom.*.label' => ['nullable', 'string', 'max:120'],
        ]);

        $mapping = $this->normalizeMapping($data['mapping'] ?? []);

        $tenant->update([
            'lead_field_mapping' => $mapping,
        ]);

        return redirect()
            ->route('tenant.trades.settings.leads', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Lead field mapping updated.');
    }

    protected function generateInboxKey(): string
    {
        do {
            $key = Str::random(40);
            $exists = Tenant::query()->where('inbox_key', $key)->exists();
        } while ($exists);

        return $key;
    }

    protected function normalizeMapping(array $mapping): array
    {
        $standardDefaults = [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'notes' => 'message',
            'description' => 'description',
            'preferred_time' => 'preferred_time',
            'service_address' => 'service_address',
        ];

        $defaultMapping = $this->normalizeFormMapping($mapping['default'] ?? $mapping, $standardDefaults);

        $forms = [];
        foreach (array_slice((array) ($mapping['forms'] ?? []), 0, 2) as $form) {
            $name = trim((string) ($form['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $forms[] = [
                'name' => $name,
                'standard' => $this->normalizeFormMapping($form, $standardDefaults)['standard'],
                'custom' => $this->normalizeFormMapping($form, $standardDefaults)['custom'],
            ];
        }

        return [
            'default' => $defaultMapping,
            'forms' => $forms,
        ];
    }

    protected function normalizeFormMapping(array $mapping, array $standardDefaults): array
    {
        $standard = array_filter(
            (array) ($mapping['standard'] ?? $standardDefaults),
            fn($value) => is_string($value) && trim($value) !== ''
        );

        $custom = [];
        foreach ((array) ($mapping['custom'] ?? []) as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $custom[] = [
                'key' => $key,
                'label' => trim((string) ($row['label'] ?? '')) ?: $key,
            ];
        }

        return [
            'standard' => array_replace($standardDefaults, $standard),
            'custom' => $custom,
        ];
    }
}
