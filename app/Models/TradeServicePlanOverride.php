<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeServicePlanOverride extends Model
{
    protected $fillable = [
        'trade_service_plan_id',
        'created_by',
        'override_date',
        'note',
    ];

    protected $casts = [
        'override_date' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(TradeServicePlan::class, 'trade_service_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
