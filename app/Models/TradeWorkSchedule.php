<?php

namespace App\Models;

use App\Models\Tenant;
use App\Models\TradeWorkScheduleBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeWorkSchedule extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'cadence',
        'timezone',
        'starts_on',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'int',
        'user_id' => 'int',
        'is_active' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function blocks()
    {
        return $this->hasMany(TradeWorkScheduleBlock::class, 'schedule_id');
    }
}
