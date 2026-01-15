<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'rule_id',
        'opportunity_id',
        'status',
        'error',
        'duration_ms',
        'payload',
        'run_key',
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
}
