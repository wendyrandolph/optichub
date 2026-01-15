<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceLocationController extends Controller
{
    public function index(Tenant $tenant)
    {
        $this->authorize('viewAny', ServiceLocation::class);

        $locations = ServiceLocation::query()
            ->where('tenant_id', $tenant->id)
            ->with(['client', 'company'])
            ->orderBy('label')
            ->orderBy('address_line1')
            ->paginate(20);

        return view('trades.locations.index', [
            'tenant' => $tenant,
            'locations' => $locations,
        ]);
    }

    public function create(Request $request, Tenant $tenant)
    {
        $this->authorize('create', ServiceLocation::class);

        $selectedClient = null;
        $selectedClientId = (int) $request->query('client', 0);
        if ($selectedClientId) {
            $selectedClient = Client::where('tenant_id', $tenant->id)->where('id', $selectedClientId)->first();
        }

        return view('trades.locations.create', [
            'tenant' => $tenant,
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'selectedClient' => $selectedClient,
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', ServiceLocation::class);

        $data = $this->validateData($request, $tenant);
        $data['tenant_id'] = $tenant->id;

        if (empty($data['client_company_id']) && !empty($data['client_id'])) {
            $clientCompanyId = Client::where('tenant_id', $tenant->id)
                ->where('id', $data['client_id'])
                ->value('client_company_id');
            $data['client_company_id'] = $clientCompanyId;
        }

        $location = ServiceLocation::create($data);

        return redirect()
            ->route('tenant.trades.locations.show', ['tenant' => $tenant->id, 'location' => $location->id])
            ->with('success_message', 'Service location created.');
    }

    public function show(Tenant $tenant, ServiceLocation $location)
    {
        $this->authorize('view', $location);
        $this->abortIfWrongTenant($tenant, $location);

        $location->load(['client', 'company']);
        $jobs = \App\Models\TradeJob::where('tenant_id', $tenant->id)
            ->where('service_location_id', $location->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return view('trades.locations.show', [
            'tenant' => $tenant,
            'location' => $location,
            'jobs' => $jobs,
        ]);
    }

    public function edit(Tenant $tenant, ServiceLocation $location)
    {
        $this->authorize('update', $location);
        $this->abortIfWrongTenant($tenant, $location);

        return view('trades.locations.edit', [
            'tenant' => $tenant,
            'location' => $location->load(['client', 'company']),
            'clients' => Client::where('tenant_id', $tenant->id)->orderBy('lastName')->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, ServiceLocation $location)
    {
        $this->authorize('update', $location);
        $this->abortIfWrongTenant($tenant, $location);

        $data = $this->validateData($request, $tenant);

        if (empty($data['client_company_id']) && !empty($data['client_id'])) {
            $clientCompanyId = Client::where('tenant_id', $tenant->id)
                ->where('id', $data['client_id'])
                ->value('client_company_id');
            $data['client_company_id'] = $clientCompanyId;
        }

        $location->update($data);

        return redirect()
            ->route('tenant.trades.locations.show', ['tenant' => $tenant->id, 'location' => $location->id])
            ->with('success_message', 'Service location updated.');
    }

    public function destroy(Tenant $tenant, ServiceLocation $location)
    {
        $this->authorize('delete', $location);
        $this->abortIfWrongTenant($tenant, $location);

        $location->delete();

        return redirect()
            ->route('tenant.trades.locations.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Service location deleted.');
    }

    protected function validateData(Request $request, Tenant $tenant): array
    {
        return $request->validate([
            'client_id' => [
                'required',
                Rule::exists('contacts', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'client_company_id' => [
                'nullable',
                Rule::exists('client_companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'label' => 'nullable|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'access_notes' => 'nullable|string',
        ]);
    }

    protected function abortIfWrongTenant(Tenant $tenant, ServiceLocation $location): void
    {
        if ((int) $location->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
