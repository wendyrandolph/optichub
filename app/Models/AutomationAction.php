<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationAction extends Model
{
    protected $fillable = [
        'rule_id',
        'action_key',
        'config_json',
        'sort_order',
    ];

    protected $casts = [
        'config_json' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}
