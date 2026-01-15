<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'trigger',
        'conditions',
        'actions',
        'active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class, 'rule_id');
    }
}
