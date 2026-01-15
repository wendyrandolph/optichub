<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeAppointment extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'trade_job_id',
        'start_at',
        'end_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }

    public function assignments()
    {
        return $this->hasMany(AppointmentAssignment::class, 'appointment_id');
    }

    public function timers()
    {
        return $this->hasMany(TradeJobTimer::class, 'appointment_id');
    }

    public function reminders()
    {
        return $this->hasMany(TradeAppointmentReminder::class, 'appointment_id');
    }

    public function fieldNotes()
    {
        return $this->hasMany(TradeAppointmentNote::class, 'appointment_id');
    }

    public function fieldPhotos()
    {
        return $this->hasMany(TradeAppointmentPhoto::class, 'appointment_id');
    }
}
