<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeAppointmentNote extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'user_id',
        'note',
    ];

    public function appointment()
    {
        return $this->belongsTo(TradeAppointment::class, 'appointment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
