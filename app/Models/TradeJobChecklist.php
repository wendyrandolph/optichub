<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobChecklist extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_job_id',
        'label',
        'is_required',
        'is_completed',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'bool',
        'is_completed' => 'bool',
        'completed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }
}
