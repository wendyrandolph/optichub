<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntegration;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $manualMethods = PaymentIntegration::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'manual')
            ->orderByDesc('created_at')
            ->get();

        $view = $tenant->workspace_type === 'trades'
            ? 'trades.settings.payments.index'
            : 'settings.payments.index';

        return view($view, [
            'tenant' => $tenant,
            'manualMethods' => $manualMethods,
        ]);
    }

    public function storeManualMethod(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'external_url' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $enabled = $request->boolean('is_enabled', true);

        PaymentIntegration::create([
            'tenant_id' => $tenant->id,
            'provider' => 'manual',
            'label' => $data['label'],
            'external_url' => $data['external_url'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'is_enabled' => $enabled,
            'active' => $enabled,
        ]);

        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
            ->with('status', 'Payment method added.');
    }

    public function updateManualMethod(Request $request, Tenant $tenant, PaymentIntegration $integration): RedirectResponse
    {
        if ($integration->tenant_id !== $tenant->id || $integration->provider !== 'manual') {
            abort(404);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'external_url' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $enabled = $request->boolean('is_enabled', true);

        $integration->update([
            'label' => $data['label'],
            'external_url' => $data['external_url'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'is_enabled' => $enabled,
            'active' => $enabled,
        ]);

        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
            ->with('status', 'Payment method updated.');
    }

    public function destroyManualMethod(Tenant $tenant, PaymentIntegration $integration): RedirectResponse
    {
        if ($integration->tenant_id !== $tenant->id || $integration->provider !== 'manual') {
            abort(404);
        }

        $integration->delete();

        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
            ->with('status', 'Payment method removed.');
    }
}
