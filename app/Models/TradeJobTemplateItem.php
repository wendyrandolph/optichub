<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobTemplateItem extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_job_template_id',
        'description',
        'quantity',
        'unit_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(TradeJobTemplate::class, 'trade_job_template_id');
    }
}
