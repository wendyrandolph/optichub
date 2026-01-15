<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ClientCompany;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class ClientCompanyController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorize('view', $tenant); // optional, if you use policies

        $q = trim($request->get('q', ''));
        $filter = $request->get('filter', '');

        // Stats (unfiltered) for quick filters
        $statsBase = ClientCompany::query()->where('tenant_id', $tenant->id);
        $stats = [
            'total' => (clone $statsBase)->count(),
            'with_site' => (clone $statsBase)->whereNotNull('website')->where('website', '!=', '')->count(),
            'with_phone' => (clone $statsBase)->whereNotNull('phone')->where('phone', '!=', '')->count(),
            'with_contacts' => (clone $statsBase)->whereHas('contacts')->count(),
            'without_contacts' => (clone $statsBase)->whereDoesntHave('contacts')->count(),
        ];

        $query = ClientCompany::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('contacts')
            ->withCount([
                'projects as active_projects_count' => function ($q) {
                    $q->where('status', '!=', 'closed');
                },
            ]);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('company_name', 'like', "%{$q}%")
                    ->orWhere('industry', 'like', "%{$q}%")
                    ->orWhere('website', 'like', "%{$q}%");
            });
        }

        // Quick filters
        switch ($filter) {
            case 'has_website':
                $query->whereNotNull('website')->where('website', '!=', '');
                break;
            case 'has_phone':
                $query->whereNotNull('phone')->where('phone', '!=', '');
                break;
            case 'has_contacts':
                $query->having('contacts_count', '>', 0);
                break;
            case 'no_contacts':
                $query->having('contacts_count', '=', 0);
                break;
            default:
                // no extra filter
                break;
        }

        $companies = $query
            ->orderBy('company_name')
            ->paginate(20)
            ->appends(['q' => $q, 'filter' => $filter]);

        return view('tenant.companies.index', [
            'tenant'    => $tenant,
            'companies' => $companies,
            'kpis'      => $stats,
            'q'         => $q,
            'filter'    => $filter,
        ]);
    }

    public function create(Tenant $tenant)
    {
        // empty model instance so the form can use ->company_name etc.
        $company = new ClientCompany([
            'tenant_id' => $tenant->id,
            'account_owner_id' => auth()->id(),
        ]);

        $users = method_exists($tenant, 'users')
            ? $tenant->users()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username', 'email'])
            : User::where('tenant_id', $tenant->id)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username', 'email']);

        return view('tenant.companies.create', [
            'tenant'  => $tenant,
            'company' => $company,
            'users'   => $users,
            'contactsForSelect' => collect(),
            'statusOptions' => ['prospect' => 'Prospect', 'active' => 'Active', 'past' => 'Past'],
            'billingTypes' => ['hourly' => 'Hourly', 'retainer' => 'Retainer', 'project' => 'Project', 'na' => 'N/A'],
            'communicationOptions' => ['email' => 'Email', 'phone' => 'Phone', 'chat' => 'Chat', 'video' => 'Video'],
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('view', $tenant); // or 'update', depending on your policy

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'industry'     => 'nullable|string|max:255',
            'client_status' => 'nullable|string|in:prospect,active,past',
            'website'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'account_owner_id' => 'nullable|exists:users,id',
            'primary_contact_id' => 'nullable|exists:contacts,id',
            'billing_type' => 'nullable|string|in:hourly,retainer,project,na',
            'maintenance_plan' => 'nullable|string|max:255',
            'renewal_date' => 'nullable|date',
            'preferred_communication' => 'nullable|string|in:email,phone,chat,video',
            'timezone' => 'nullable|string|max:100',
        ]);

        $data['tenant_id'] = $tenant->id; // trust route, not user input
        $data['account_owner_id'] = $data['account_owner_id'] ?? auth()->id();

        ClientCompany::create($data);

        return redirect()
            ->route('tenant.companies.index', ['tenant' => $tenant->id])
            ->with('success', 'Client company created.');
    }

    public function quickStore(Request $request, Tenant $tenant)
    {
        $this->authorize('view', $tenant);

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);
        $data['tenant_id'] = $tenant->id;

        $company = ClientCompany::create($data);

        return response()->json([
            'id' => $company->id,
            'company_name' => $company->company_name,
        ]);
    }

    public function show(Tenant $tenant, ClientCompany $company)
    {
        $this->authorize('view', $tenant);

        if ($company->tenant_id !== $tenant->id) {
            abort(404);
        }

        $company->load([
            'contacts' => function ($q) {
                $q->orderBy('lastName')->orderBy('firstName');
            },
            'services.logs',
            'services' => function ($q) {
                $q->orderByRaw("CASE WHEN renewal_date IS NULL THEN 1 ELSE 0 END")
                    ->orderBy('renewal_date')
                    ->orderByDesc('created_at');
            },
        ])->loadCount([
            'contacts',
            'projects',
            'projects as active_projects_count' => function ($q) {
                $q->where('status', '!=', 'closed');
            },
        ]);

        $projectsPreview = $company->projects()
            ->orderByRaw("CASE WHEN status != 'closed' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->take(5)
            ->withCount('tasks')
            ->get(['id', 'project_name', 'status', 'end_date', 'updated_at']);

        $primaryContact = $company->contacts->first();
        $latestProjectUpdate = $company->projects()->max('updated_at');
        $activeServicesCount = $company->services->where('status', 'active')->count();
        $nextServiceRenewal = $company->services
            ->where('status', 'active')
            ->filter(fn($s) => $s->renewal_date)
            ->sortBy('renewal_date')
            ->first();

        // Retainer balance current period
        $retainerService = $company->services->first(fn($s) => $s->type === 'retainer' && isset($s->meta['retainer_amount']));
        $retainerBalance = null;
        if ($retainerService) {
            $cycle = $retainerService->billing_cycle;
            $periodStart = match ($cycle) {
                'annual' => now()->copy()->startOfYear(),
                'monthly' => now()->copy()->startOfMonth(),
                default => $retainerService->start_date ?? now()->copy()->startOfMonth(),
            };
            $logs = $retainerService->logs->where('log_type', 'retainer_usage')
                ->filter(fn($log) => $log->occurred_at ? $log->occurred_at >= $periodStart : true);
            $usedAmount = $logs->sum('amount');
            $usedHours = $logs->sum('hours');
            $retainerType = $retainerService->meta['retainer_type'] ?? null;
            $retainerAmount = $retainerService->meta['retainer_amount'] ?? null;
            if ($retainerAmount !== null) {
                if ($retainerType === 'hours') {
                    $retainerBalance = $retainerAmount - $usedHours;
                } else {
                    $retainerBalance = $retainerAmount - $usedAmount;
                }
            }
        }

        $lastMaintenance = $company->services
            ->filter(fn($s) => $s->type === 'maintenance')
            ->flatMap(fn($s) => $s->logs)
            ->where('log_type', 'maintenance')
            ->sortByDesc('occurred_at')
            ->first();

        return view('tenant.companies.show', [
            'tenant'  => $tenant,
            'company' => $company,
            'projectsPreview' => $projectsPreview,
            'primaryContact' => $primaryContact,
            'latestProjectUpdate' => $latestProjectUpdate,
            'services' => $company->services->take(5),
            'activeServicesCount' => $activeServicesCount,
            'nextServiceRenewal' => $nextServiceRenewal,
            'retainerBalance' => $retainerBalance,
            'lastMaintenance' => $lastMaintenance,
        ]);
    }

    public function edit(Tenant $tenant, ClientCompany $company)
    {
        $this->authorize('view', $tenant); // tighten later if needed

        // safety: ensure company belongs to tenant
        if ($company->tenant_id !== $tenant->id) {
            abort(404);
        }

        $company->load(['contacts' => function ($q) {
            $q->orderBy('lastName')->orderBy('firstName');
        }]);

        $users = method_exists($tenant, 'users')
            ? $tenant->users()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username', 'email'])
            : User::where('tenant_id', $tenant->id)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username', 'email']);

        return view('tenant.companies.edit', [
            'tenant'  => $tenant,
            'company' => $company,
            'users'   => $users,
            'contactsForSelect' => $company->contacts,
            'statusOptions' => ['prospect' => 'Prospect', 'active' => 'Active', 'past' => 'Past'],
            'billingTypes' => ['hourly' => 'Hourly', 'retainer' => 'Retainer', 'project' => 'Project', 'na' => 'N/A'],
            'communicationOptions' => ['email' => 'Email', 'phone' => 'Phone', 'chat' => 'Chat', 'video' => 'Video'],
        ]);
    }

    public function update(Request $request, Tenant $tenant, ClientCompany $company)
    {
        $this->authorize('view', $tenant);

        if ($company->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'industry'     => 'nullable|string|max:255',
            'client_status' => 'nullable|string|in:prospect,active,past',
            'website'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'account_owner_id' => 'nullable|exists:users,id',
            'primary_contact_id' => 'nullable|exists:contacts,id',
            'billing_type' => 'nullable|string|in:hourly,retainer,project,na',
            'maintenance_plan' => 'nullable|string|max:255',
            'renewal_date' => 'nullable|date',
            'preferred_communication' => 'nullable|string|in:email,phone,chat,video',
            'timezone' => 'nullable|string|max:100',
        ]);

        $company->update($data);

        return redirect()
            ->route('tenant.companies.index', ['tenant' => $tenant->id])
            ->with('success', 'Client company updated.');
    }
}
