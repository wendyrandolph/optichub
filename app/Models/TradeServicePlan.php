<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeServicePlan extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'company_id',
        'service_location_id',
        'title',
        'status',
        'cadence_unit',
        'cadence_interval',
        'cadence_weekday',
        'cadence_month_day',
        'starts_on',
        'next_occurrence',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'next_occurrence' => 'date',
        'cadence_interval' => 'int',
        'cadence_weekday' => 'int',
        'cadence_month_day' => 'int',
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
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }

    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    public function items()
    {
        return $this->hasMany(TradeServicePlanItem::class, 'trade_service_plan_id');
    }

    public function overrides()
    {
        return $this->hasMany(TradeServicePlanOverride::class, 'trade_service_plan_id');
    }

    public function occurrences()
    {
        return $this->hasMany(TradeServicePlanOccurrence::class, 'trade_service_plan_id');
    }
}
