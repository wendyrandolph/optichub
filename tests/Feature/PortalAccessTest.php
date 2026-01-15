<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_view_other_tenant_project(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $contactA = Contact::factory()->create(['tenant_id' => $tenantA->id]);
        $contactB = Contact::factory()->create(['tenant_id' => $tenantB->id]);

        $clientUser = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenantA->id,
            'contact_id' => $contactA->id,
        ]);

        $projectB = Project::create([
            'tenant_id' => $tenantB->id,
            'contact_id' => $contactB->id,
            'project_name' => 'Other Tenant Project',
            'status' => 'open',
        ]);

        $this->actingAs($clientUser, 'client')
            ->get(route('portal.projects.show', $projectB->id))
            ->assertStatus(403);
    }

    public function test_client_cannot_view_other_tenant_invoice(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $contactA = Contact::factory()->create(['tenant_id' => $tenantA->id]);
        $contactB = Contact::factory()->create(['tenant_id' => $tenantB->id]);

        $clientUser = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenantA->id,
            'contact_id' => $contactA->id,
        ]);

        $invoiceB = Invoice::create([
            'tenant_id' => $tenantB->id,
            'contact_id' => $contactB->id,
            'invoice_number' => 'INV-TENANT-B',
            'status' => 'sent',
            'balance_due' => 100,
            'total_amount' => 100,
        ]);

        $this->actingAs($clientUser, 'client')
            ->get(route('portal.invoices.show', $invoiceB->id))
            ->assertStatus(403);
    }

    public function test_client_cannot_download_other_tenant_file(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $contactA = Contact::factory()->create(['tenant_id' => $tenantA->id]);
        $contactB = Contact::factory()->create(['tenant_id' => $tenantB->id]);

        $clientUser = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenantA->id,
            'contact_id' => $contactA->id,
        ]);

        $fileB = Upload::create([
            'tenant_id' => $tenantB->id,
            'contact_id' => $contactB->id,
            'original_name' => 'secret.pdf',
            'stored_name' => 'secret.pdf',
            'path' => 'uploads/secret.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1234,
        ]);

        $this->actingAs($clientUser, 'client')
            ->get(route('portal.files.download', $fileB->id))
            ->assertStatus(403);
    }

    public function test_client_cannot_view_other_tenant_messages(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $contactA = Contact::factory()->create(['tenant_id' => $tenantA->id]);
        $contactB = Contact::factory()->create(['tenant_id' => $tenantB->id]);

        $clientUser = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenantA->id,
            'contact_id' => $contactA->id,
        ]);

        $projectB = Project::create([
            'tenant_id' => $tenantB->id,
            'contact_id' => $contactB->id,
            'project_name' => 'Other Tenant Project',
            'status' => 'open',
        ]);

        $this->actingAs($clientUser, 'client')
            ->get(route('portal.projects.messages.index', $projectB->id))
            ->assertStatus(403);
    }
}
