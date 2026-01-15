<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobItem extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_job_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'warranty_starts_on',
        'warranty_ends_on',
        'warranty_terms',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'warranty_starts_on' => 'date',
        'warranty_ends_on' => 'date',
    ];

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }
}
