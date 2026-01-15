<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLocation extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_company_id',
        'label',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'access_notes',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'client_company_id');
    }

    public function appointments()
    {
        return $this->hasMany(TradeAppointment::class, 'service_location_id');
    }
}
