<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeServicePlanItem extends Model
{
    protected $fillable = [
        'trade_service_plan_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'int',
    ];

    public function plan()
    {
        return $this->belongsTo(TradeServicePlan::class, 'trade_service_plan_id');
    }
}
