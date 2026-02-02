<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AutomationAction;

class AutomationRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'trigger',
        'trigger_key',
        'conditions',
        'actions',
        'active',
        'enabled',
        'scope',
        'created_by_user_id',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'active' => 'boolean',
        'enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationRule $rule) {
            if (empty($rule->trigger) && !empty($rule->trigger_key)) {
                $rule->trigger = $rule->trigger_key;
            }
            if (empty($rule->actions)) {
                $rule->actions = [];
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class, 'rule_id');
    }

    public function actionItems()
    {
        return $this->hasMany(AutomationAction::class, 'rule_id')->orderBy('sort_order');
    }
}
