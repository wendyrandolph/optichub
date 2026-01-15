<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;
use App\Models\User;
use App\Models\Service;

class ClientCompany extends Model
{
  use HasTenantScope;
  use \App\Traits\FormatsPhone;

  protected $table = 'client_companies';

  protected $fillable = [
    'tenant_id',
    'company_name',
    'industry',
    'client_status',
    'website',
    'phone',
    'address',
    'notes',
    'account_owner_id',
    'primary_contact_id',
    'billing_type',
    'maintenance_plan',
    'renewal_date',
    'preferred_communication',
    'timezone',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }

  public function contacts()
  {
    return $this->hasMany(Contact::class, 'client_company_id');
  }

  public function primaryContact()
  {
    return $this->belongsTo(Contact::class, 'primary_contact_id');
  }

  public function accountOwner()
  {
    return $this->belongsTo(User::class, 'account_owner_id');
  }

  public function projects()
  {
    return $this->hasMany(Project::class, 'client_company_id');
  }

  public function services()
  {
    return $this->hasMany(Service::class, 'company_id');
  }

  public function serviceLocations()
  {
    return $this->hasMany(ServiceLocation::class, 'client_company_id');
  }

  public function tradeJobs()
  {
    return $this->hasMany(TradeJob::class, 'company_id');
  }

  // Convenience so you can call $company->name
  public function getNameAttribute(): string
  {
    return $this->company_name;
  }

  public function getPhoneFormattedAttribute(): ?string
  {
    return $this->formatPhone($this->attributes['phone'] ?? null);
  }
}
