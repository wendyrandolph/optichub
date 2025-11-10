<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Exception;

/**
 * Eloquent Model for the 'project_payments' table.
 * All queries are automatically filtered by the current user's organization (tenant_id)
 * using the HasTenantScope trait.
 */
class ProjectPayment extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'project_payments';

    protected $fillable = [
        'project_id',
        'tenant_id', // Included for the HasTenantScope trait
        'payment_number',
        'amount',
        'received_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'received_date' => 'date',
    ];

    // --- Relationships ---

    /**
     * A payment belongs to a Project.
     * Note: This relationship automatically ensures that the associated project
     * also belongs to the current tenant due to the Project model's HasTenantScope.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * A payment belongs to an Organization (Tenant).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    // --- Static Methods to Replace Procedural Logic ---

    /**
     * Replaces ProjectPayment::addPayment().
     * Adds a new payment after validating the project belongs to the tenant.
     *
     * @param array $data Payment data.
     * @return self The created ProjectPayment model instance.
     * @throws Exception If the project is not found or not accessible (via a findOrFail check).
     */
    public static function createPayment(array $data): self
    {
        // 1. Validate the project access (tenant_id filter is automatic on Project::findOrFail)
        $project = Project::findOrFail($data['project_id']);

        // 2. Add tenant_id from the verified project to the data
        $data['tenant_id'] = $project->tenant_id;

        // 3. Create the payment record
        return static::create($data);
    }

    /**
     * Replaces ProjectPayment::getByProject().
     * Retrieves all payments for a specific project.
     * The HasTenantScope ensures only current tenant's payments are returned.
     *
     * @param int $projectId The ID of the project.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPaymentsByProject(int $projectId)
    {
        // We use the Project relationship to perform a secure query.
        // First, ensure the project exists and is accessible to the tenant.
        Project::findOrFail($projectId);

        return static::query()
            ->where('project_id', $projectId)
            ->orderBy('payment_number')
            ->get();
    }
}
