<?php

// app/Http/Controllers/TaxSettingsController.php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxSettingsController extends Controller
{
    protected function tenantFromRoute(Request $request)
    {
        // Assuming route model binding or "tenant" param is an ID
        $routeTenant = $request->route('tenant');

        if ($routeTenant instanceof \App\Models\Tenant) {
            return $routeTenant;
        }

        return \App\Models\Tenant::findOrFail($routeTenant);
    }

    public function edit(Request $request)
    {
        $tenant = $this->tenantFromRoute($request);

        $tenant->load('taxRates');

        return view('admin.settings.tax', [
            'tenant' => $tenant,
            'taxRates' => $tenant->taxRates()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $this->tenantFromRoute($request);

        $data = $request->validate([
            'tax_enabled'      => ['sometimes', 'boolean'],
            'tax_inclusive'    => ['sometimes', 'boolean'],
            'default_currency' => ['required', 'string', 'size:3'],
        ]);

        $original = $tenant->only(['tax_enabled', 'tax_inclusive', 'default_currency']);

        $tenant->update([
            'tax_enabled'      => (bool) ($data['tax_enabled'] ?? false),
            'tax_inclusive'    => (bool) ($data['tax_inclusive'] ?? false),
            'default_currency' => strtoupper($data['default_currency']),
        ]);

        $changed = $tenant->only(['tax_enabled', 'tax_inclusive', 'default_currency']);

        activity()
            ->causedBy($request->user('admin'))
            ->performedOn($tenant)
            ->withProperties([
                'type'     => 'tax_settings_updated',
                'original' => $original,
                'changed'  => $changed,
            ])
            ->log('Tenant tax settings updated');

        return redirect()
            ->route('tenant.settings.tax.index', ['tenant' => $tenant->id])
            ->with('status', 'Tax settings updated.');
    }


    public function storeRate(Request $request)
    {
        $tenant = $this->tenantFromRoute($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'applies_by_default' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $tenant->taxRates()->create([
            'name' => $data['name'],
            'rate' => $data['rate'],
            'applies_by_default' => (bool) ($data['applies_by_default'] ?? false),
            'active' => (bool) ($data['active'] ?? true),
        ]);
        activity()
            ->causedBy($request->user('admin'))
            ->performedOn($tenant)
            ->withProperties([
                'type'    => 'tax_rate_created',
                'taxRate' => $rate->only(['id', 'name', 'rate', 'applies_by_default', 'active']),
            ])
            ->log('Tenant tax rate created');

        return redirect()
            ->route('tenant.settings.tax.index', ['tenant' => $tenant->id])
            ->with('status', 'Tax rate added.');
    }

    public function updateRate(Request $request, TaxRate $taxRate)
    {
        $tenant = $this->tenantFromRoute($request);

        abort_unless($taxRate->tenant_id === $tenant->id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'applies_by_default' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $taxRate->update([
            'name' => $data['name'],
            'rate' => $data['rate'],
            'applies_by_default' => (bool) ($data['applies_by_default'] ?? false),
            'active' => (bool) ($data['active'] ?? true),
        ]);
        activity()
            ->causedBy($request->user('admin'))
            ->performedOn($tenant)
            ->withProperties([
                'type'     => 'tax_rate_updated',
                'taxRate'  => $taxRate->only(['id', 'name', 'rate', 'applies_by_default', 'active']),
            ])
            ->log('Tenant tax rate updated');

        return redirect()
            ->route('tenant.settings.tax.index', ['tenant' => $tenant->id])
            ->with('status', 'Tax rate updated.');
    }

    public function destroyRate(Request $request, TaxRate $taxRate)
    {
        $tenant = $this->tenantFromRoute($request);

        abort_unless($taxRate->tenant_id === $tenant->id, 403);

        $taxRate->delete();
        activity()
            ->causedBy($request->user('admin'))
            ->performedOn($tenant)
            ->withProperties([
                'type'    => 'tax_rate_deleted',
                'taxRate' => $taxRate->only(['id', 'name', 'rate', 'applies_by_default', 'active']),
            ])
            ->log('Tenant tax rate deleted');

        return redirect()
            ->route('tenant.settings.tax.index', ['tenant' => $tenant->id])
            ->with('status', 'Tax rate deleted.');
    }
}
