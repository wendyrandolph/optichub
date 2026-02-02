<?php

namespace App\Services;

use App\Models\AutomationAction;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\AutomationRunItem;
use App\Models\Tenant;
use App\Models\Proposal;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateApplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AutomationRunner
{
    public function run(Tenant $tenant, string $triggerKey, string $contextType, int $contextId): void
    {
        $rules = AutomationRule::query()
            ->with('actionItems')
            ->where('tenant_id', $tenant->id)
            ->where('trigger_key', $triggerKey)
            ->where(function ($q) {
                $q->whereNull('scope')->orWhere('scope', 'creative');
            })
            ->where(function ($q) {
                $q->whereNull('enabled')->orWhere('enabled', true);
            })
            ->get();

        foreach ($rules as $rule) {
            $run = AutomationRun::create([
                'tenant_id' => $tenant->id,
                'rule_id' => $rule->id,
                'trigger_key' => $triggerKey,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'status' => 'running',
                'started_at' => now(),
            ]);

            $hasFailures = false;

            foreach ($rule->actionItems as $action) {
                $item = AutomationRunItem::create([
                    'run_id' => $run->id,
                    'action_key' => $action->action_key,
                    'status' => 'running',
                    'started_at' => now(),
                ]);

                try {
                    $message = $this->executeAction($action, $tenant, $contextType, $contextId, $rule);
                    $item->update([
                        'status' => 'success',
                        'message' => $message,
                        'finished_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    $hasFailures = true;
                    $item->update([
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                        'finished_at' => now(),
                    ]);
                }
            }

            $run->update([
                'status' => $hasFailures ? 'failed' : 'success',
                'finished_at' => now(),
            ]);
        }
    }

    protected function executeAction(AutomationAction $action, Tenant $tenant, string $contextType, int $contextId, AutomationRule $rule): string
    {
        if ($contextType !== Proposal::class) {
            return 'Skipped (unsupported context)';
        }

        $proposal = Proposal::query()
            ->where('tenant_id', $tenant->id)
            ->with(['lead', 'contact', 'client', 'project'])
            ->findOrFail($contextId);

        return match ($action->action_key) {
            'convert_lead_to_contact' => $this->convertLeadToContact($proposal, $tenant),
            'create_project_from_proposal' => $this->createProjectFromProposal($proposal, $tenant, $action->config_json ?? [], $rule),
            'seed_tasks_from_template' => $this->seedTasksFromTemplate($proposal, $tenant, $action->config_json ?? []),
            'create_invoice_schedule' => $this->createInvoiceSchedule($proposal, $tenant, $action->config_json ?? []),
            'create_followup_task' => $this->createFollowupTask($proposal, $tenant, $action->config_json ?? [], $rule),
            default => 'Unknown action',
        };
    }

    protected function convertLeadToContact(Proposal $proposal, Tenant $tenant): string
    {
        if ($proposal->contact_id || $proposal->client_id) {
            return 'Contact already linked';
        }
        $lead = $proposal->lead;
        if (!$lead) {
            return 'No lead to convert';
        }

        $contact = null;
        if (!empty($lead->email)) {
            $contact = Contact::query()
                ->where('tenant_id', $tenant->id)
                ->where('email', $lead->email)
                ->first();
        }

        if (!$contact) {
            [$firstName, $lastName] = $this->splitName($lead->name ?? '');
            $contact = Contact::create([
                'tenant_id' => $tenant->id,
                'firstName' => $firstName ?: $lead->name,
                'lastName' => $lastName,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'client_company_id' => $lead->company_id,
                'status' => 'active',
            ]);
        }

        $proposal->update([
            'contact_id' => $contact->id,
            'client_id' => $contact->id,
        ]);

        return 'Contact linked';
    }

    protected function createProjectFromProposal(Proposal $proposal, Tenant $tenant, array $config, AutomationRule $rule): string
    {
        $contactId = $proposal->contact_id ?? $proposal->client_id;
        if (!$contactId && $proposal->lead_id) {
            $this->convertLeadToContact($proposal, $tenant);
            $proposal->refresh();
            $contactId = $proposal->contact_id ?? $proposal->client_id;
        }

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contactId,
            'proposal_id' => $proposal->id,
            'project_name' => $proposal->title,
            'description' => $proposal->summary,
            'status' => 'open',
            'uses_phases' => true,
        ]);

        $templateId = Arr::get($config, 'project_template_id');
        if ($templateId) {
            $template = ProjectTemplate::query()
                ->where('tenant_id', $tenant->id)
                ->find($templateId);
            if ($template) {
                app(ProjectTemplateApplier::class)->apply($template, $project, $rule->created_by_user_id);
            }
        }

        return 'Project created';
    }

    protected function seedTasksFromTemplate(Proposal $proposal, Tenant $tenant, array $config): string
    {
        $templateId = Arr::get($config, 'project_template_id');
        if (!$templateId) {
            return 'No template selected';
        }

        $project = Project::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($q) use ($proposal) {
                $q->where('proposal_id', $proposal->id)
                  ->orWhere('id', $proposal->project_id);
            })
            ->first();

        if (!$project) {
            return 'No project to seed';
        }

        $template = ProjectTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->find($templateId);
        if (!$template) {
            return 'Template not found';
        }

        app(ProjectTemplateApplier::class)->apply($template, $project, null);

        return 'Tasks seeded';
    }

    protected function createInvoiceSchedule(Proposal $proposal, Tenant $tenant, array $config): string
    {
        $contactId = $proposal->contact_id ?? $proposal->client_id;
        if (!$contactId) {
            return 'No contact for invoice';
        }

        $projectId = $proposal->project_id ?: Project::query()
            ->where('tenant_id', $tenant->id)
            ->where('proposal_id', $proposal->id)
            ->value('id');

        $mode = Arr::get($config, 'mode', 'single');
        $installments = Arr::get($config, 'installments', []);
        $total = (float) ($proposal->total_investment ?? 0);

        $createInvoice = function (float $amount, int $offsetDays) use ($tenant, $contactId, $projectId) {
            $issueDate = now()->toDateString();
            $dueDate = now()->addDays($offsetDays)->toDateString();
            Invoice::create([
                'tenant_id' => $tenant->id,
                'contact_id' => $contactId,
                'project_id' => $projectId,
                'status' => 'draft',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'total_amount' => $amount,
                'balance_due' => $amount,
            ]);
        };

        if ($mode === 'installments' && !empty($installments)) {
            foreach ($installments as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                if (!$amount && !empty($row['percent']) && $total > 0) {
                    $amount = round($total * ((float) $row['percent'] / 100), 2);
                }
                if ($amount <= 0) {
                    continue;
                }
                $offset = (int) ($row['due_offset_days'] ?? 0);
                $createInvoice($amount, $offset);
            }
            return 'Invoice schedule created';
        }

        if ($total > 0) {
            $createInvoice($total, (int) Arr::get($config, 'due_offset_days', 0));
            return 'Invoice created';
        }

        return 'No invoice created';
    }

    protected function createFollowupTask(Proposal $proposal, Tenant $tenant, array $config, AutomationRule $rule): string
    {
        $title = Arr::get($config, 'title', 'Proposal follow-up');
        $days = (int) Arr::get($config, 'days_from_now', 3);
        $dueDate = now()->addDays($days)->toDateString();

        Task::create([
            'tenant_id' => $tenant->id,
            'project_id' => $proposal->project_id,
            'contact_id' => $proposal->contact_id ?? $proposal->client_id,
            'user_id' => $rule->created_by_user_id,
            'title' => $title,
            'due_date' => $dueDate,
            'status' => 'todo',
        ]);

        return 'Follow-up task created';
    }

    protected function splitName(string $name): array
    {
        $parts = preg_split('/\\s+/', trim($name), 2);
        if (!$parts || $parts[0] === '') {
            return ['', ''];
        }
        return [$parts[0], $parts[1] ?? ''];
    }
}
