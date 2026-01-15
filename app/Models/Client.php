<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Upload;
use App\Models\Project;
use App\Models\ContactFile;
use App\Models\ContactNote;
use App\Models\ContactActivity;

/**
 * Backwards-compatible alias for the renamed Contact model.
 *
 * Legacy code still references App\Models\Client, so we expose
 * this thin wrapper that extends the new Contact model and
 * keeps the table mapping pointed at `contacts`.
 */
class Client extends Model
{
    use \App\Traits\FormatsPhone;
    /**
     * Allow mass assignment for the fields we create/update through the
     * contact management forms.
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
        'role',
        'portal_client_message_color',
        'portal_team_message_color',
    ];

    // Display helper
    public function getPhoneFormattedAttribute(): ?string
    {
        return $this->formatPhone($this->attributes['phone'] ?? null);
    }

    /**
     * Explicitly set the table so older code that relied on
     * the `clients` table continues to work with the renamed
     * `contacts` table.
     *
     * @var string
     */
    protected $table = 'contacts';

    public function uploads()
    {
        // assumes invoice-style files are stored in an uploads table
        // with a contact_id column
        return $this->hasMany(Upload::class, 'contact_id');
    }
    public function contactFiles()
    {
        return $this->hasMany(ContactFile::class, 'contact_id');
    }
    public function notes()
    {
        return $this->hasMany(ContactNote::class, 'contact_id');
    }
    public function contactActivities()
    {
        return $this->hasMany(ContactActivity::class, 'contact_id');
    }
    public function projects()
    {
        // assumes invoice-style files are stored in an uploads table
        // with a contact_id column
        return $this->hasMany(Project::class, 'contact_id');
    }

    public function serviceLocations()
    {
        return $this->hasMany(ServiceLocation::class, 'client_id');
    }

    public function tradeJobs()
    {
        return $this->hasMany(TradeJob::class, 'client_id');
    }
    public function tradeServicePlans()
    {
        return $this->hasMany(TradeServicePlan::class, 'client_id');
    }
    public function projectMessages()
    {
        return $this->hasMany(\App\Models\ProjectMessage::class, 'contact_id');
    }

    public function tenant()
    {
        // 👇 explicitly tell Eloquent: foreign key is tenant_id, owner key is tenants.id
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Portal user account for this contact/client (role = client).
     */
    public function userAccount()
    {
        return $this->hasOne(\App\Models\User::class, 'contact_id', 'id')
            ->where('role', 'client');
    }
    public function company()
    {
        return $this->belongsTo(\App\Models\ClientCompany::class, 'client_company_id');
    }
    public function getFriendlyCustomerIdAttribute()
    {
        $tenant = $this->tenant;

        // Tenant initials (optional)
        $initials = collect(explode(' ', $tenant->name ?? ''))
            ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        // Start customer numbers at 1001
        $num = 1000 + $this->id;

        return $initials . '-' . $num;
    }
}
