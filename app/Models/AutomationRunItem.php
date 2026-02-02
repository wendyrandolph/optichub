<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRunItem extends Model
{
    protected $fillable = [
        'run_id',
        'action_key',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(AutomationRun::class, 'run_id');
    }
}
