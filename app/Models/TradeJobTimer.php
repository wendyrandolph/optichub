<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeJobTimer extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'trade_job_id',
        'user_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointment()
    {
        return $this->belongsTo(TradeAppointment::class, 'appointment_id');
    }

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
