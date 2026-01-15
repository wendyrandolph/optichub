<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TradeLeadEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Tenant $tenant, Request $request): View
    {
        $q = trim((string) $request->string('q'));
        $status = $request->string('status')->toString();
        $source = $request->string('source')->toString();
        $assignedTo = $request->integer('assigned_to');

        $statusOptions = ['new', 'contacted', 'scheduled', 'won', 'lost'];
        $sourceOptions = ['manual', 'website', 'webhook', 'import'];

        $query = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->with(['assignedTo', 'owner'])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when(in_array($status, $statusOptions, true), fn($qb) => $qb->where('status', $status))
            ->when(in_array($source, $sourceOptions, true), fn($qb) => $qb->where('source', $source))
            ->when($assignedTo, fn($qb) => $qb->where('assigned_to_user_id', $assignedTo))
            ->orderByDesc('created_at');

        $leads = $query->paginate(20)->appends($request->query());

        $statusCounts = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $assignees = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'dispatcher', 'lead_tech', 'lead tech', 'lead-tech'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('trades.leads.index', compact(
            'tenant',
            'leads',
            'statusCounts',
            'statusOptions',
            'sourceOptions',
            'assignees',
        ));
    }

    public function create(Tenant $tenant): View
    {
        $statusOptions = ['new', 'contacted', 'scheduled', 'won', 'lost'];
        $sourceOptions = ['manual', 'website', 'webhook', 'import'];

        return view('trades.leads.create', compact('tenant', 'statusOptions', 'sourceOptions'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validateLead($request);
        $data['tenant_id'] = $tenant->id;
        $data['status'] = $data['status'] ?? 'new';
        $data['source'] = $data['source'] ?? 'manual';
        $data['captured_at'] = $data['captured_at'] ?? now();

        if (empty($data['name'])) {
            $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }
        if (empty($data['name'])) {
            $data['name'] = $data['email'] ?? $data['phone'] ?? 'New Lead';
        }

        $data = $this->applyStatusTimestamps($data, null);

        $lead = Lead::create($data);
        $this->logEvent($tenant, $lead, 'created', [
            'source' => $lead->source,
        ]);

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Lead created.');
    }

    public function show(Tenant $tenant, Lead $lead): View
    {
        $this->abortIfWrongTenant($tenant, $lead);

        return view('trades.leads.show', [
            'tenant' => $tenant,
            'lead' => $lead,
            'statusOptions' => ['new', 'contacted', 'scheduled', 'won', 'lost'],
            'assignees' => User::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('role', ['admin', 'dispatcher', 'lead_tech', 'lead tech', 'lead-tech'])
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
            'events' => $lead->events()->latest()->get(),
        ]);
    }

    public function edit(Tenant $tenant, Lead $lead): View
    {
        $this->abortIfWrongTenant($tenant, $lead);
        $statusOptions = ['new', 'contacted', 'scheduled', 'won', 'lost'];
        $sourceOptions = ['manual', 'website', 'webhook', 'import'];

        return view('trades.leads.edit', compact('tenant', 'lead', 'statusOptions', 'sourceOptions'));
    }

    public function update(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);
        $data = $this->validateLead($request, true);

        $statusBefore = $lead->status;
        $data = $this->applyStatusTimestamps($data, $lead);

        if (empty($data['name'])) {
            $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }

        $lead->update($data);

        if (array_key_exists('status', $data) && $data['status'] !== $statusBefore) {
            $this->logEvent($tenant, $lead, 'status_changed', [
                'from' => $statusBefore,
                'to' => $data['status'],
            ]);
        }

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Lead updated.');
    }

    public function convert(Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);

        $client = Client::query()
            ->where('tenant_id', $tenant->id)
            ->when($lead->email, fn($qb) => $qb->where('email', $lead->email))
            ->first();

        if (!$client && $lead->phone) {
            $client = Client::query()
                ->where('tenant_id', $tenant->id)
                ->where('phone', $lead->phone)
                ->first();
        }

        if (!$client) {
            [$firstName, $lastName] = $this->splitName($lead->name);

            $client = Client::create([
                'tenant_id' => $tenant->id,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => 'active',
            ]);
        }

        $lead->update([
            'status' => 'won',
            'status_changed_at' => now(),
            'won_at' => $lead->won_at ?? now(),
            'became_client_at' => $lead->became_client_at ?? now(),
        ]);

        $this->logEvent($tenant, $lead, 'converted', [
            'client_id' => $client->id,
        ]);

        return redirect()
            ->route('tenant.trades.jobs.create', ['tenant' => $tenant->getRouteKey()])
            ->with('success', 'Lead converted to client.')
            ->withInput(['client' => $client->id, 'lead' => $lead->id]);
    }

    public function contact(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);

        $updates = [];
        if (!$lead->first_contacted_at) {
            $updates['first_contacted_at'] = now();
        }
        if ($lead->status === 'new') {
            $updates['status'] = 'contacted';
            $updates['status_changed_at'] = now();
        }

        if (!empty($updates)) {
            $lead->update($updates);
        }

        $this->logEvent($tenant, $lead, 'contacted', []);

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Lead marked as contacted.');
    }

    public function status(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:new,contacted,scheduled,won,lost'],
        ]);

        $statusBefore = $lead->status;
        $updates = $this->applyStatusTimestamps($data, $lead);

        $lead->update($updates);

        if ($statusBefore !== $updates['status']) {
            $this->logEvent($tenant, $lead, 'status_changed', [
                'from' => $statusBefore,
                'to' => $updates['status'],
            ]);
        }

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Lead status updated.');
    }

    public function assign(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);

        $data = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (!empty($data['assigned_to_user_id'])) {
            $assigned = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', $data['assigned_to_user_id'])
                ->exists();
            if (!$assigned) {
                abort(404);
            }
        }

        $lead->update([
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);

        $this->logEvent($tenant, $lead, 'assigned', [
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Lead assignment updated.');
    }

    public function note(Request $request, Tenant $tenant, Lead $lead): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $lead);

        $data = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $this->logEvent($tenant, $lead, 'note_added', [
            'note' => $data['note'],
        ]);

        return redirect()
            ->route('tenant.trades.leads.show', ['tenant' => $tenant->getRouteKey(), 'lead' => $lead->id])
            ->with('success', 'Note added.');
    }

    protected function validateLead(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:new,contacted,scheduled,won,lost'],
            'source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'preferred_time' => ['nullable', 'string', 'max:120'],
            'service_address' => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        if (
            empty($data['name'])
            && empty($data['first_name'])
            && empty($data['last_name'])
            && empty($data['email'])
            && empty($data['phone'])
        ) {
            return $request->validate([
                'name' => ['required', 'string'],
            ]);
        }

        return $data;
    }

    protected function splitName(?string $name): array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return ['Client', ''];
        }
        $parts = preg_split('/\s+/', $name, 2);
        return [$parts[0] ?? 'Client', $parts[1] ?? ''];
    }

    protected function abortIfWrongTenant(Tenant $tenant, Lead $lead): void
    {
        if ($lead->tenant_id !== $tenant->id) {
            abort(404);
        }
    }

    protected function applyStatusTimestamps(array $data, ?Lead $lead): array
    {
        if (!array_key_exists('status', $data)) {
            return $data;
        }

        $status = $data['status'];
        if ($lead && $lead->status === $status) {
            return $data;
        }
        $data['status_changed_at'] = now();

        if ($status === 'contacted' && !($lead?->first_contacted_at)) {
            $data['first_contacted_at'] = now();
        }
        if ($status === 'scheduled' && !($lead?->scheduled_at)) {
            $data['scheduled_at'] = now();
        }
        if ($status === 'won' && !($lead?->won_at)) {
            $data['won_at'] = now();
        }
        if ($status === 'lost' && !($lead?->lost_at)) {
            $data['lost_at'] = now();
        }

        return $data;
    }

    protected function logEvent(Tenant $tenant, Lead $lead, string $type, array $payload): void
    {
        TradeLeadEvent::create([
            'tenant_id' => $tenant->id,
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'payload_json' => $payload,
        ]);
    }
}
