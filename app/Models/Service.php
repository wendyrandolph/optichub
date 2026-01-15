<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ServiceLog;

class Service extends Model
{
    protected $table = 'company_services';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'type',
        'status',
        'handling',
        'provider',
        'billing_cycle',
        'renewal_date',
        'cost_amount',
        'cost_currency',
        'start_date',
        'reminder_days',
        'meta',
        'notes',
    ];

    protected $casts = [
        'meta' => 'array',
        'renewal_date' => 'date',
        'start_date' => 'date',
        'cost_amount' => 'decimal:2',
        'reminder_days' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }

    public function logs()
    {
        return $this->hasMany(ServiceLog::class, 'company_service_id');
    }
}
