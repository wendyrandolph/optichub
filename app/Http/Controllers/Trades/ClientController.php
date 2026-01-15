<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use App\Models\TradeJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Tenant $tenant, Request $request)
    {
        $query = Client::query()->where('tenant_id', $tenant->id);
        $search = trim((string) $request->query('q'));
        $scope = (string) $request->query('scope', '');
        $hasServicePlans = Schema::hasTable('trade_service_plans');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                    ->orWhere('lastName', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($scope === 'with_jobs') {
            $query->whereHas('tradeJobs', fn($q) => $q->where('tenant_id', $tenant->id));
        } elseif ($scope === 'no_jobs') {
            $query->whereDoesntHave('tradeJobs', fn($q) => $q->where('tenant_id', $tenant->id));
        } elseif ($scope === 'recent') {
            $query->orderByDesc('updated_at');
        } else {
            $query->orderBy('lastName')->orderBy('firstName');
        }

        $query->with('company')
            ->withCount(['tradeJobs', 'serviceLocations']);

        if ($hasServicePlans) {
            $query->withCount('tradeServicePlans');
        }

        $query->addSelect([
            'last_job_at' => TradeJob::select('updated_at')
                ->whereColumn('client_id', 'contacts.id')
                ->where('tenant_id', $tenant->id)
                ->latest('updated_at')
                ->limit(1),
        ]);

        $clients = $query->paginate(20)->withQueryString();

        $totalClients = Client::where('tenant_id', $tenant->id)->count();
        $clientsAdded30d = Client::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $clientsWithJobs = TradeJob::where('tenant_id', $tenant->id)
            ->whereNotNull('client_id')
            ->distinct('client_id')
            ->count('client_id');
        $clientsWithPlans = null;
        if ($hasServicePlans && Schema::hasColumn('trade_service_plans', 'client_id')) {
            $clientsWithPlans = \App\Models\TradeServicePlan::where('tenant_id', $tenant->id)
                ->whereNotNull('client_id')
                ->distinct('client_id')
                ->count('client_id');
        }

        return view('trades.clients.index', [
            'tenant' => $tenant,
            'clients' => $clients,
            'search' => $search,
            'scope' => $scope,
            'totalClients' => $totalClients,
            'clientsAdded30d' => $clientsAdded30d,
            'clientsWithJobs' => $clientsWithJobs,
            'clientsWithPlans' => $clientsWithPlans,
        ]);
    }

    public function create(Tenant $tenant)
    {
        return view('trades.clients.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validateData($request, $tenant);
        $data['tenant_id'] = $tenant->id;

        $client = Client::create($data);

        if ($request->query('return') === 'jobs.create') {
            return redirect()
                ->route('tenant.trades.jobs.create', ['tenant' => $tenant->id, 'client' => $client->id])
                ->with('success', 'Client created.');
        }

        return redirect()
            ->route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id])
            ->with('success', 'Client created.');
    }

    public function show(Tenant $tenant, Client $client)
    {
        if ($client->tenant_id !== $tenant->id) {
            abort(404);
        }

        $locations = ServiceLocation::where('tenant_id', $tenant->id)
            ->where('client_id', $client->id)
            ->orderBy('label')
            ->get();

        $jobs = TradeJob::where('tenant_id', $tenant->id)
            ->where('client_id', $client->id)
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $servicePlans = collect();
        if ($tenant->trades_recurring_enabled) {
            $servicePlans = \App\Models\TradeServicePlan::where('tenant_id', $tenant->id)
                ->where('client_id', $client->id)
                ->with('serviceLocation')
                ->orderByRaw('next_occurrence IS NULL')
                ->orderBy('next_occurrence')
                ->get();
        }

        return view('trades.clients.show', [
            'tenant' => $tenant,
            'client' => $client,
            'locations' => $locations,
            'jobs' => $jobs,
            'servicePlans' => $servicePlans,
        ]);
    }

    public function edit(Tenant $tenant, Client $client)
    {
        if ($client->tenant_id !== $tenant->id) {
            abort(404);
        }

        return view('trades.clients.edit', [
            'tenant' => $tenant,
            'client' => $client,
        ]);
    }

    public function update(Request $request, Tenant $tenant, Client $client): RedirectResponse
    {
        if ($client->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $this->validateData($request, $tenant, $client->id);
        $client->update($data);

        return redirect()
            ->route('tenant.trades.clients.show', ['tenant' => $tenant->id, 'client' => $client->id])
            ->with('success', 'Client updated.');
    }

    protected function validateData(Request $request, Tenant $tenant, ?int $clientId = null): array
    {
        return $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('contacts', 'email')
                    ->where(fn($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($clientId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
