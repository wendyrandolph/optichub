<?php

namespace Tests\Feature;

use App\Models\AutomationAction;
use App\Models\AutomationRule;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutomationRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_sent_triggers_automation_run(): void
    {
        $tenant = Tenant::create(['name' => 'Creative Studio', 'workspace_type' => 'creative']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id]);
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'project_name' => 'Site Project',
            'status' => 'open',
        ]);
        $proposal = Proposal::create([
            'tenant_id' => $tenant->id,
            'title' => 'Proposal A',
            'project_id' => $project->id,
            'client_id' => $contact->id,
            'contact_id' => $contact->id,
            'recipient_type' => 'existing_contact',
            'status' => 'draft',
            'unique_share_token' => 'token123',
        ]);

        $rule = AutomationRule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Follow up after send',
            'trigger_key' => 'proposal_sent',
            'enabled' => true,
            'scope' => 'creative',
        ]);
        AutomationAction::create([
            'rule_id' => $rule->id,
            'action_key' => 'create_followup_task',
            'config_json' => ['days_from_now' => 1, 'title' => 'Follow up'],
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('tenant.proposals.send', ['tenant' => $tenant->id, 'proposal' => $proposal->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('automation_runs', [
            'tenant_id' => $tenant->id,
            'trigger_key' => 'proposal_sent',
            'context_id' => $proposal->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'tenant_id' => $tenant->id,
            'title' => 'Follow up',
        ]);
    }

    public function test_convert_lead_to_contact_action(): void
    {
        $tenant = Tenant::create(['name' => 'Creative Studio', 'workspace_type' => 'creative']);
        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Person',
            'email' => 'lead@example.com',
        ]);
        $proposal = Proposal::create([
            'tenant_id' => $tenant->id,
            'title' => 'Proposal B',
            'recipient_type' => 'new_lead',
            'lead_id' => $lead->id,
            'status' => 'sent',
            'unique_share_token' => 'token456',
        ]);

        $rule = AutomationRule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Convert lead',
            'trigger_key' => 'proposal_approved',
            'enabled' => true,
            'scope' => 'creative',
        ]);
        AutomationAction::create([
            'rule_id' => $rule->id,
            'action_key' => 'convert_lead_to_contact',
            'sort_order' => 0,
        ]);

        app(AutomationRunner::class)->run($tenant, 'proposal_approved', Proposal::class, $proposal->id);

        $proposal->refresh();
        $this->assertNotNull($proposal->contact_id);
        $this->assertDatabaseHas('contacts', [
            'tenant_id' => $tenant->id,
            'email' => 'lead@example.com',
        ]);
    }

    public function test_create_project_from_proposal_action(): void
    {
        $tenant = Tenant::create(['name' => 'Creative Studio', 'workspace_type' => 'creative']);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id]);
        $proposal = Proposal::create([
            'tenant_id' => $tenant->id,
            'title' => 'Proposal C',
            'recipient_type' => 'existing_contact',
            'contact_id' => $contact->id,
            'client_id' => $contact->id,
            'status' => 'sent',
            'unique_share_token' => 'token789',
        ]);

        $rule = AutomationRule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Create project',
            'trigger_key' => 'proposal_approved',
            'enabled' => true,
            'scope' => 'creative',
        ]);
        AutomationAction::create([
            'rule_id' => $rule->id,
            'action_key' => 'create_project_from_proposal',
            'config_json' => [],
            'sort_order' => 0,
        ]);

        app(AutomationRunner::class)->run($tenant, 'proposal_approved', Proposal::class, $proposal->id);

        $this->assertDatabaseHas('projects', [
            'tenant_id' => $tenant->id,
            'proposal_id' => $proposal->id,
            'project_name' => 'Proposal C',
        ]);
    }
}
