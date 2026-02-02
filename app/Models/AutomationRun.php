<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AutomationRunItem;

class AutomationRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'rule_id',
        'opportunity_id',
        'trigger_key',
        'context_type',
        'context_id',
        'status',
        'error',
        'duration_ms',
        'payload',
        'run_key',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function items()
    {
        return $this->hasMany(AutomationRunItem::class, 'run_id');
    }
}
