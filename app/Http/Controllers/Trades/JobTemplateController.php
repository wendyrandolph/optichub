<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TradeJobTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobTemplateController extends Controller
{
    public function index(Tenant $tenant)
    {
        $this->authorize('viewAny', TradeJobTemplate::class);

        $templates = TradeJobTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('trades/job-templates/index', [
            'tenant' => $tenant,
            'templates' => $templates,
        ]);
    }

    public function create(Tenant $tenant)
    {
        $this->authorize('create', TradeJobTemplate::class);

        return view('trades/job-templates/create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', TradeJobTemplate::class);

        $data = $this->validateData($request);
        $data['tenant_id'] = $tenant->id;
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $template = TradeJobTemplate::create($data);

        $template->items()->delete();
        $items = $this->normalizeItems($request->input('items', []));
        if (!empty($items)) {
            $template->items()->createMany($items);
        }

        $template->checklistItems()->delete();
        $checklist = $this->normalizeChecklist($request->input('checklist', []));
        if (!empty($checklist)) {
            $template->checklistItems()->createMany($checklist);
        }

        return redirect()
            ->route('tenant.trades.job-templates.show', ['tenant' => $tenant->id, 'job_template' => $template->id])
            ->with('success_message', 'Template created.');
    }

    public function show(Tenant $tenant, TradeJobTemplate $job_template)
    {
        $this->authorize('view', $job_template);
        $this->abortIfWrongTenant($tenant, $job_template);

        $job_template->load(['items', 'checklistItems']);

        return view('trades/job-templates/show', [
            'tenant' => $tenant,
            'template' => $job_template,
        ]);
    }

    public function edit(Tenant $tenant, TradeJobTemplate $job_template)
    {
        $this->authorize('update', $job_template);
        $this->abortIfWrongTenant($tenant, $job_template);

        return view('trades/job-templates/edit', [
            'tenant' => $tenant,
            'template' => $job_template->load(['items', 'checklistItems']),
        ]);
    }

    public function update(Request $request, Tenant $tenant, TradeJobTemplate $job_template): RedirectResponse
    {
        $this->authorize('update', $job_template);
        $this->abortIfWrongTenant($tenant, $job_template);

        $data = $this->validateData($request);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $job_template->update($data);

        $job_template->items()->delete();
        $items = $this->normalizeItems($request->input('items', []));
        if (!empty($items)) {
            $job_template->items()->createMany($items);
        }

        $job_template->checklistItems()->delete();
        $checklist = $this->normalizeChecklist($request->input('checklist', []));
        if (!empty($checklist)) {
            $job_template->checklistItems()->createMany($checklist);
        }

        return redirect()
            ->route('tenant.trades.job-templates.show', ['tenant' => $tenant->id, 'job_template' => $job_template->id])
            ->with('success_message', 'Template updated.');
    }

    public function destroy(Tenant $tenant, TradeJobTemplate $job_template): RedirectResponse
    {
        $this->authorize('delete', $job_template);
        $this->abortIfWrongTenant($tenant, $job_template);

        $job_template->delete();

        return redirect()
            ->route('tenant.trades.job-templates.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Template deleted.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['service', 'project'])],
            'default_status' => ['required', 'string', 'max:40'],
            'default_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'suggested_tech_count' => ['nullable', 'integer', 'min:0'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'checklist' => ['nullable', 'array'],
            'checklist.*.label' => ['nullable', 'string', 'max:255'],
            'checklist.*.is_required' => ['nullable', 'boolean'],
        ]);
    }

    protected function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $normalized[] = [
                'description' => $description,
                'quantity' => (float) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'sort_order' => (int) $index,
            ];
        }

        return $normalized;
    }

    protected function normalizeChecklist(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'is_required' => !empty($item['is_required']),
                'sort_order' => (int) $index,
            ];
        }

        return $normalized;
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeJobTemplate $template): void
    {
        if ((int) $template->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
