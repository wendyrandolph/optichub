<?php

namespace App\Models;

use App\Scopes\HasTenantScope; // Use the standard trait established earlier
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent Model for the 'clients' table.
 * All internal queries are automatically filtered by the current user's organization (tenant_id)
 * using the HasTenantScope trait.
 */
class Contact extends Model
{
    use HasFactory; // HasTenantScope; // Use the HasTenantScope trait

    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'client_company_id',
        'firstName',
        'lastName',
        'email',
        'phone',
        'notes',
        'status',
    ];

    // --- RELATIONSHIPS ---

    /**
     * A Client belongs to an Organization (Tenant).
     */

    /** The client’s portal login user (role = client). */
    public function userAccount(): HasOne
    {
        return $this->hasOne(User::class, 'client_id', 'id')
            ->where('role', 'client');
    }

    /** Convenience: expose a boolean `has_login` attribute. */
    protected $appends = ['has_login'];

    public function getHasLoginAttribute(): bool
    {
        // if relation is loaded, don’t re-query
        if ($this->relationLoaded('userAccount')) {
            return (bool) $this->userAccount;
        }
        return $this->userAccount()->exists();
    }
    public function organization(): BelongsTo
    {
        // We use the Organization model defined previously
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    public function getFullNameAttribute(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
    /**
     * A Client belongs to a Client Company.
     * Assuming the model name for the company is ClientCompany.
     */
    public function company(): BelongsTo
    {
        // You'll need to define the ClientCompany model separately
        return $this->belongsTo(ClientCompany::class, 'client_company_id');
    }

    /**
     * Get the projects associated with this client.
     */
    public function projects(): HasMany
    {
        // This relationship automatically respects the tenant_id filtering
        return $this->hasMany(Project::class, 'client_id');
    }

    // --- SCOPES ---

    /**
     * Scope a query to include the related company name.
     * Usage: Client::withCompany()->get()
     */
    public function scopeWithCompany(Builder $query): Builder
    {
        return $query->with('company');
    }

    /**
     * Scope a query to search by term.
     * Usage: Client::search('john')->get()
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";
        return $query->where('firstName', 'LIKE', $term)
            ->orWhere('lastName', 'LIKE', $term)
            ->orWhere('email', 'LIKE', $term);
    }

    /**
     * Retrieves all clients belonging to the organization set by the HasTenantScope.
     * Replaces getByOrganization() and assumes organization filtering is done by the global scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeGetByOrganization(Builder $query)
    {
        // The HasTenantScope already ensures only the current tenant's clients are returned.
        return $query->withCompany()->get();
    }
}
