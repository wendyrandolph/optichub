<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\AutomationAction;
use App\Models\ProjectTemplate;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AutomationSettingsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->abortIfNotCreative($tenant);

        $rules = AutomationRule::query()
            ->with('actionItems')
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->whereNull('scope')->orWhere('scope', 'creative');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('settings.automations-index', compact('tenant', 'rules'));
    }

    public function create(Tenant $tenant): View
    {
        $this->abortIfNotCreative($tenant);
        $templates = ProjectTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('settings.automations-create', compact('tenant', 'templates'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->abortIfNotCreative($tenant);

        $validated = $this->validateRule($request);

        $rule = AutomationRule::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'trigger_key' => $validated['trigger_key'],
            'enabled' => (bool) ($validated['enabled'] ?? true),
            'scope' => 'creative',
            'created_by_user_id' => $request->user()?->id,
        ]);

        $this->syncActions($rule, $validated['actions'] ?? []);

        return redirect()
            ->route('tenant.settings.automations.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Automation rule created.');
    }

    public function edit(Tenant $tenant, AutomationRule $rule): View
    {
        $this->abortIfWrongTenant($tenant, $rule);
        $this->abortIfNotCreative($tenant);

        $rule->load('actionItems');
        $templates = ProjectTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('settings.automations-edit', compact('tenant', 'rule', 'templates'));
    }

    public function update(Request $request, Tenant $tenant, AutomationRule $rule): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $rule);
        $this->abortIfNotCreative($tenant);

        $validated = $this->validateRule($request);

        $rule->update([
            'name' => $validated['name'],
            'trigger_key' => $validated['trigger_key'],
            'enabled' => (bool) ($validated['enabled'] ?? true),
            'scope' => 'creative',
        ]);

        $rule->actionItems()->delete();
        $this->syncActions($rule, $validated['actions'] ?? []);

        return redirect()
            ->route('tenant.settings.automations.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Automation rule updated.');
    }

    public function destroy(Tenant $tenant, AutomationRule $rule): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $rule);
        $this->abortIfNotCreative($tenant);

        $rule->delete();

        return redirect()
            ->route('tenant.settings.automations.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Automation rule deleted.');
    }

    protected function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_key' => ['required', 'string', 'in:proposal_sent,proposal_approved'],
            'enabled' => ['nullable', 'boolean'],
            'actions' => ['nullable', 'array'],
            'actions.*.action_key' => ['required', 'string'],
            'actions.*.config' => ['nullable', 'array'],
        ]);
    }

    protected function syncActions(AutomationRule $rule, array $actions): void
    {
        foreach (array_values($actions) as $idx => $action) {
            AutomationAction::create([
                'rule_id' => $rule->id,
                'action_key' => $action['action_key'],
                'config_json' => $action['config'] ?? null,
                'sort_order' => $idx,
            ]);
        }
    }

    protected function abortIfWrongTenant(Tenant $tenant, AutomationRule $rule): void
    {
        if ((int) $rule->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    protected function abortIfNotCreative(Tenant $tenant): void
    {
        if ($tenant->workspace_type !== 'creative') {
            abort(404);
        }
    }
}
