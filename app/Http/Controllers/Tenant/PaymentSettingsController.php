<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntegration;
use App\Models\TenantPaymentAccount;
use App\Services\StripeConnectService;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function index(Tenant $tenant, Request $request): View
    {
        $manualMethods = PaymentIntegration::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'manual')
            ->orderByDesc('created_at')
            ->get();

        $stripeAccount = TenantPaymentAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'stripe')
            ->first();

        if ($stripeAccount && $request->get('stripe') === 'connected' && $stripeAccount->status !== 'active') {
            $stripeAccount->update(['status' => 'active']);
        }

        $waveAccount = TenantPaymentAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'wave')
            ->first();

        $view = $tenant->workspace_type === 'trades'
            ? 'trades.settings.payments.index'
            : 'settings.payments.index';

        return view($view, [
            'tenant' => $tenant,
            'manualMethods' => $manualMethods,
            'stripeAccount' => $stripeAccount,
            'waveAccount' => $waveAccount,
        ]);
    }

    public function connectStripe(Tenant $tenant, StripeConnectService $stripe): RedirectResponse
    {
        if (! $stripe->enabled()) {
            return redirect()
                ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
                ->with('status', 'Stripe is not configured yet.');
        }

        $account = $stripe->createOrGetAccount($tenant);
        $accountId = $account->secret_data['stripe_account_id'] ?? null;

        if (! $accountId) {
            return redirect()
                ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
                ->with('status', 'Stripe account could not be created.');
        }

        $tenantParam = $tenant->id ?? request()->route('tenant');
        $returnUrl = route('tenant.settings.payments.index', ['tenant' => $tenantParam, 'stripe' => 'connected']);
        $refreshUrl = route('tenant.settings.payments.stripe.refresh', ['tenant' => $tenantParam]);

        $link = $stripe->createAccountLink($accountId, $returnUrl, $refreshUrl);

        return redirect()->away($link->url);
    }

    public function refreshStripe(Tenant $tenant, StripeConnectService $stripe): RedirectResponse
    {
        if (! $stripe->enabled()) {
            return redirect()
                ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
                ->with('status', 'Stripe is not configured yet.');
        }

        $account = $stripe->createOrGetAccount($tenant);
        $accountId = $account->secret_data['stripe_account_id'] ?? null;

        if (! $accountId) {
            return redirect()
                ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
                ->with('status', 'Stripe account could not be created.');
        }

        $tenantParam = $tenant->id ?? request()->route('tenant');
        $returnUrl = route('tenant.settings.payments.index', ['tenant' => $tenantParam, 'stripe' => 'connected']);
        $refreshUrl = route('tenant.settings.payments.stripe.refresh', ['tenant' => $tenantParam]);

        $link = $stripe->createAccountLink($accountId, $returnUrl, $refreshUrl);

        return redirect()->away($link->url);
    }

    public function disconnectStripe(Tenant $tenant): RedirectResponse
    {
        $account = TenantPaymentAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'stripe')
            ->first();

        if ($account) {
            $account->update(['status' => 'disabled']);
        }

        $tenantParam = $tenant->id ?? request()->route('tenant');
        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenantParam])
            ->with('status', 'Stripe disconnected.');
    }

    public function updateStripeStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,disabled'],
        ]);

        $account = TenantPaymentAccount::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'stripe')
            ->first();

        if (! $account) {
            return redirect()
                ->route('tenant.settings.payments.index', ['tenant' => $tenant->id])
                ->with('status', 'Stripe is not connected yet.');
        }

        $account->update(['status' => $data['status']]);

        $tenantParam = $tenant->id ?? request()->route('tenant');
        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenantParam])
            ->with('status', 'Payment status updated.');
    }

    public function connectWave(Tenant $tenant): RedirectResponse
    {
        TenantPaymentAccount::query()
            ->updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider' => 'wave'],
                ['status' => 'pending']
            );

        $tenantParam = $tenant->id ?? request()->route('tenant');
        return redirect()
            ->route('tenant.settings.payments.index', ['tenant' => $tenantParam])
            ->with('status', 'Wave onboarding is coming soon.');
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
