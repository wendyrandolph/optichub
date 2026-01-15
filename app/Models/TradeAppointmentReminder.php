<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeAppointmentReminder extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'offset_minutes',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(TradeAppointment::class, 'appointment_id');
    }
}
