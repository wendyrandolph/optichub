<?php

namespace App\Models;

use App\Models\TradeWorkSchedule;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Model;

class TradeWorkScheduleBlock extends Model
{
    use HasTenantScope;
    protected $fillable = [
        'tenant_id',
        'schedule_id',
        'week_index',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'tenant_id' => 'int',
        'week_index' => 'int',
        'day_of_week' => 'int',
    ];

    public function schedule()
    {
        return $this->belongsTo(TradeWorkSchedule::class, 'schedule_id');
    }
}
