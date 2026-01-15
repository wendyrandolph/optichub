<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLog extends Model
{
    protected $table = 'company_service_logs';

    protected $fillable = [
        'tenant_id',
        'company_service_id',
        'log_type',
        'occurred_at',
        'hours',
        'amount',
        'description',
        'related_project_id',
        'related_task_id',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'amount' => 'decimal:2',
        'hours' => 'decimal:2',
        'meta' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'company_service_id');
    }
}
