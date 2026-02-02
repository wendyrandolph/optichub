<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\ProposalTemplateItem;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProposalTemplateController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $templates = ProposalTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('proposals.view', [
            'tenant' => $tenant,
            'templates' => $templates,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        return view('proposals.create_manual', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $tenant) {
            $template = ProposalTemplate::create(array_merge($data, [
                'tenant_id' => $tenant->id,
            ]));
            $this->syncItems($template, $data);
        });

        return redirect()
            ->route('tenant.proposal-templates.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Template created.');
    }

    public function edit(Tenant $tenant, ProposalTemplate $proposal_template): View
    {
        $this->abortIfWrongTenant($tenant, $proposal_template);

        return view('proposals.create_manual', [
            'tenant' => $tenant,
            'template' => $proposal_template->load('items'),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Tenant $tenant, ProposalTemplate $proposal_template): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $proposal_template);

        $data = $this->validated($request);

        DB::transaction(function () use ($proposal_template, $data) {
            $proposal_template->update($data);
            $proposal_template->items()->delete();
            $this->syncItems($proposal_template, $data);
        });

        return redirect()
            ->route('tenant.proposal-templates.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Template updated.');
    }

    public function destroy(Tenant $tenant, ProposalTemplate $proposal_template): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $proposal_template);
        $proposal_template->delete();

        return redirect()
            ->route('tenant.proposal-templates.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Template deleted.');
    }

    public function fromProposal(Request $request, Tenant $tenant, Proposal $proposal): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $proposal);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $proposal->load('items', 'paymentScheduleItems');

        DB::transaction(function () use ($request, $tenant, $proposal) {
            $template = ProposalTemplate::create([
                'tenant_id' => $tenant->id,
                'name' => $request->input('name'),
                'title' => $proposal->title,
                'intro_text' => $proposal->summary,
                'timeline_text' => null,
                'next_steps_text' => $proposal->next_steps,
                'payment_policy_text' => $proposal->payment_policy,
                'default_total' => $proposal->total_investment,
                'payment_schedule' => $proposal->paymentScheduleItems->map(function ($item) {
                    return [
                        'label' => $item->label,
                        'amount' => $item->amount,
                        'due_trigger' => $item->due_trigger,
                        'note' => $item->note,
                    ];
                })->toArray(),
                'timeline' => $proposal->timeline,
                'created_from_proposal_id' => $proposal->id,
            ]);

            foreach ($proposal->items as $item) {
                $template->items()->create([
                    'type' => $item->type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'sort_order' => $item->sort_order,
                ]);
            }
        });

        return redirect()
            ->route('tenant.proposal-templates.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Template saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string'],
            'timeline_text' => ['nullable', 'string'],
            'next_steps_text' => ['nullable', 'string'],
            'payment_policy_text' => ['nullable', 'string'],
            'default_total' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule' => ['nullable', 'array'],
            'payment_schedule.*.label' => ['nullable', 'string'],
            'payment_schedule.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule.*.due_trigger' => ['nullable', 'string'],
            'payment_schedule.*.note' => ['nullable', 'string'],
            'timeline' => ['nullable', 'array'],
            'timeline.*.phase' => ['nullable', 'string'],
            'timeline.*.duration' => ['nullable', 'string'],
            'timeline.*.description' => ['nullable', 'string'],
            'goals' => ['nullable', 'array'],
            'goals.*.title' => ['nullable', 'string', 'max:255'],
            'goals.*.description' => ['nullable', 'string'],
            'goals.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*.title' => ['nullable', 'string', 'max:255'],
            'deliverables.*.description' => ['nullable', 'string'],
            'deliverables.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function syncItems(ProposalTemplate $template, array $validated): void
    {
        foreach (($validated['goals'] ?? []) as $idx => $row) {
            if (empty($row['title'])) {
                continue;
            }
            $template->items()->create([
                'type' => 'goal',
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? $idx),
            ]);
        }

        foreach (($validated['deliverables'] ?? []) as $idx => $row) {
            if (empty($row['title'])) {
                continue;
            }
            $template->items()->create([
                'type' => 'deliverable',
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? $idx),
            ]);
        }
    }

    private function abortIfWrongTenant(Tenant $tenant, Proposal|ProposalTemplate $model): void
    {
        if ((int) $model->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
