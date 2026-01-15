<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeServicePlanOccurrence extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_service_plan_id',
        'scheduled_for',
        'trade_job_id',
        'generated_at',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'generated_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(TradeServicePlan::class, 'trade_service_plan_id');
    }

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }
}
