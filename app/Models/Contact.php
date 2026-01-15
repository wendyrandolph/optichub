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
    use \App\Traits\FormatsPhone;

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

    public function getPhoneFormattedAttribute(): ?string
    {
        return $this->formatPhone($this->attributes['phone'] ?? null);
    }

    // --- RELATIONSHIPS ---

    /**
     * A Client belongs to an Organization (Tenant).
     */

    /** The client’s portal login user (role = client). */
    public function userAccount(): HasOne
    {
        return $this->hasOne(User::class, 'contact_id', 'id')
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
    public function projects()
    {
        // assumes invoice-style files are stored in an uploads table
        // with a contact_id column
        return $this->hasMany(Project::class, 'contact_id');
    }
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    /**
     * A Client belongs to a Client Company.
     * Assuming the model name for the company is ClientCompany.
     */
    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'client_company_id');
    }

    // Optional helper
    public function getCompanyNameAttribute(): ?string
    {
        return $this->company?->company_name;
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function uploads()
    {
        return $this->hasMany(Upload::class, 'contact_id');
    }

    public function serviceLocations()
    {
        return $this->hasMany(ServiceLocation::class, 'client_id');
    }

    public function tradeJobs()
    {
        return $this->hasMany(TradeJob::class, 'client_id');
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
