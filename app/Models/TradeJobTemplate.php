<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobTemplate extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'default_status',
        'default_duration_minutes',
        'suggested_tech_count',
        'summary',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'default_duration_minutes' => 'int',
        'suggested_tech_count' => 'int',
    ];

    public function items()
    {
        return $this->hasMany(TradeJobTemplateItem::class, 'trade_job_template_id');
    }

    public function checklistItems()
    {
        return $this->hasMany(TradeJobTemplateChecklist::class, 'trade_job_template_id');
    }
}
