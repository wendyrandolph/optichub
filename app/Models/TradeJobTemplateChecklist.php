<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobTemplateChecklist extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_job_template_id',
        'label',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'bool',
    ];

    public function template()
    {
        return $this->belongsTo(TradeJobTemplate::class, 'trade_job_template_id');
    }
}
