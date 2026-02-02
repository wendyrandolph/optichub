<?php
namespace App\Http\Requests\Proposal;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Rely on the controller's policy check for 'create' permission
        return true; 
    }

    /**
     * Get the validation rules that apply to the POST request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'use_existing_project' => ['nullable', 'in:0,1'],
            'client_id' => ['nullable', 'exists:contacts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'recipient_type' => ['nullable', 'in:new_lead,existing_lead,existing_contact'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'lead_name' => ['nullable', 'string', 'max:255'],
            'lead_email' => ['nullable', 'email', 'max:255'],
            'lead_phone' => ['nullable', 'string', 'max:50'],
            'lead_company' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:draft,sent,accepted,declined'],
            'summary' => ['nullable', 'string'],
            'goals' => ['nullable', 'array'],
            'goals.*.title' => ['nullable', 'string', 'max:255'],
            'goals.*.description' => ['nullable', 'string'],
            'goals.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*.title' => ['nullable', 'string', 'max:255'],
            'deliverables.*.description' => ['nullable', 'string'],
            'deliverables.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'total_investment' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule' => ['nullable', 'array'],
            'payment_schedule.*.label' => ['nullable', 'string'],
            'payment_schedule.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule.*.due_trigger' => ['nullable', 'string'],
            'payment_schedule.*.note' => ['nullable', 'string'],
            'maintenance_plan' => ['nullable', 'array'],
            'maintenance_plan.enabled' => ['nullable'],
            'maintenance_plan.monthly_amount' => ['nullable', 'numeric', 'min:0'],
            'maintenance_plan.cancellation_terms' => ['nullable', 'string'],
            'maintenance_plan.includes' => ['nullable', 'array'],
            'maintenance_plan.includes.*' => ['nullable', 'string'],
            'payment_policy' => ['nullable', 'string'],
            'timeline' => ['nullable', 'array'],
            'timeline.*.phase' => ['nullable', 'string'],
            'timeline.*.duration' => ['nullable', 'string'],
            'timeline.*.description' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'project_id.required' => 'The Project field is required.',
            'project_name.required' => 'The Project name field is required.',
            'client_id.required' => 'The Client field is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $schedule = $this->input('payment_schedule', []);
            $hasSchedule = collect($schedule)->contains(function ($row) {
                return !empty($row['label']) || !empty($row['amount']);
            });
            if ($hasSchedule && !$this->filled('total_investment')) {
                $validator->errors()->add('total_investment', 'Total investment is required when a payment schedule is provided.');
            }

            $tenant = $this->route('tenant');
            $isCreative = $tenant && $tenant->workspace_type === 'creative';

            $recipientType = $this->input('recipient_type', 'existing_contact');
            if ($isCreative) {
                if ($recipientType === 'existing_contact') {
                    if (!$this->filled('contact_id') && !$this->filled('client_id')) {
                        $validator->errors()->add('contact_id', 'Please select a contact.');
                    }
                } elseif ($recipientType === 'existing_lead') {
                    if (!$this->filled('lead_id')) {
                        $validator->errors()->add('lead_id', 'Please select a lead.');
                    }
                } else {
                    $hasName = (bool) $this->input('lead_name');
                    $hasEmail = (bool) $this->input('lead_email');
                    $hasPhone = (bool) $this->input('lead_phone');
                    if (!$hasName || (!$hasEmail && !$hasPhone)) {
                        $validator->errors()->add('lead_name', 'Provide a name and either an email or phone.');
                    }
                }
            } else {
                if (!$this->filled('client_id')) {
                    $validator->errors()->add('client_id', 'The Client field is required.');
                }
            }

            $useExisting = $this->input('use_existing_project', '0') === '1';
            if ($useExisting) {
                if (!$this->filled('project_id')) {
                    $validator->errors()->add('project_id', 'Please select a project.');
                }
            } else {
                if (!$this->filled('project_name')) {
                    $validator->errors()->add('project_name', 'Please enter a project name.');
                }
            }
        });
    }
}
