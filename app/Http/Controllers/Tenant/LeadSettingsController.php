<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantLeadSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadSettingsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $settings = TenantLeadSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $endpointUrl = route('api.leads.ingest', ['tenant' => $tenant->getRouteKey()]);

        return view('settings.leads', [
            'tenant' => $tenant,
            'settings' => $settings,
            'endpointUrl' => $endpointUrl,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $settings = TenantLeadSetting::firstOrCreate(['tenant_id' => $tenant->id]);

        $data = $request->validate([
            'notify_email' => ['nullable', 'string', 'max:255'],
            'allowlist_domains' => ['nullable', 'string'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'auto_reply_subject' => ['nullable', 'string', 'max:255'],
            'auto_reply_body' => ['nullable', 'string'],
        ]);

        $allowlist = $request->has('allowlist_domains')
            ? $this->normalizeDomains($data['allowlist_domains'] ?? '')
            : $settings->allowlist_domains;

        $settings->update([
            'notify_email' => $request->has('notify_email') ? ($data['notify_email'] ?? null) : $settings->notify_email,
            'allowlist_domains' => $allowlist,
            'auto_reply_enabled' => $request->has('auto_reply_enabled') ? $request->boolean('auto_reply_enabled') : $settings->auto_reply_enabled,
            'auto_reply_subject' => $request->has('auto_reply_subject') ? ($data['auto_reply_subject'] ?? null) : $settings->auto_reply_subject,
            'auto_reply_body' => $request->has('auto_reply_body') ? ($data['auto_reply_body'] ?? null) : $settings->auto_reply_body,
        ]);

        return redirect()
            ->route('tenant.settings.leads.index', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Lead settings updated.');
    }

    public function regenerate(Tenant $tenant): RedirectResponse
    {
        $settings = TenantLeadSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $settings->rotateSecret();

        return redirect()
            ->route('tenant.settings.leads.index', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Secret token regenerated.');
    }

    private function normalizeDomains(string $raw): array
    {
        $domains = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));
        return array_values($domains);
    }
}
