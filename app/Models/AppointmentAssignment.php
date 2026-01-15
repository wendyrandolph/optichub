<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class AppointmentAssignment extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'user_id',
        'role',
        'presence_status',
        'on_site_at',
        'done_at',
    ];

    protected $casts = [
        'on_site_at' => 'datetime',
        'done_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointment()
    {
        return $this->belongsTo(\App\Models\TradeAppointment::class, 'appointment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
