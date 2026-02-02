<?php

namespace Tests\Feature;

use App\Jobs\SendTrackedEmail;
use App\Mail\ProposalSentMailable;
use App\Models\Contact;
use App\Models\OutboundEmail;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutboundEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_email_record_created_on_proposal_send(): void
    {
        Mail::fake();
        Bus::fake();

        $tenant = Tenant::create(['name' => 'Creative Studio', 'workspace_type' => 'creative']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id, 'email' => 'client@example.com']);
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

        $this->actingAs($user)
            ->post(route('tenant.proposals.send', ['tenant' => $tenant->id, 'proposal' => $proposal->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('outbound_emails', [
            'tenant_id' => $tenant->id,
            'to_email' => 'client@example.com',
            'type' => 'proposal_sent',
            'status' => 'queued',
        ]);
    }

    public function test_send_tracked_email_updates_status(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'Studio', 'workspace_type' => 'creative']);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id, 'email' => 'client@example.com']);
        $proposal = Proposal::create([
            'tenant_id' => $tenant->id,
            'title' => 'Proposal B',
            'recipient_type' => 'existing_contact',
            'contact_id' => $contact->id,
            'client_id' => $contact->id,
            'status' => 'draft',
            'unique_share_token' => 'token456',
        ]);

        $outbound = OutboundEmail::create([
            'tenant_id' => $tenant->id,
            'to_email' => 'client@example.com',
            'subject' => 'New Proposal',
            'type' => 'proposal_sent',
            'related_type' => Proposal::class,
            'related_id' => $proposal->id,
            'status' => 'queued',
            'queued_at' => now(),
            'meta' => ['proposal_id' => $proposal->id],
        ]);

        SendTrackedEmail::dispatchSync($outbound->id);

        $this->assertDatabaseHas('outbound_emails', [
            'id' => $outbound->id,
            'status' => 'sent',
        ]);
    }

    public function test_reply_to_uses_tenant_reply_to_email(): void
    {
        Mail::fake();

        $tenant = Tenant::create([
            'name' => 'Studio',
            'workspace_type' => 'creative',
            'reply_to_email' => 'reply@studio.test',
        ]);
        $contact = Contact::factory()->create(['tenant_id' => $tenant->id, 'email' => 'client@example.com']);
        $proposal = Proposal::create([
            'tenant_id' => $tenant->id,
            'title' => 'Proposal C',
            'recipient_type' => 'existing_contact',
            'contact_id' => $contact->id,
            'client_id' => $contact->id,
            'status' => 'draft',
            'unique_share_token' => 'token789',
        ]);

        Mail::to('client@example.com')->send(new ProposalSentMailable($proposal));

        Mail::assertSent(ProposalSentMailable::class, function ($mail) {
            return $mail->hasReplyTo('reply@studio.test');
        });
    }
}
