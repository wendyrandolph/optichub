<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TradeJob extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'company_id',
        'service_location_id',
        'job_template_id',
        'type',
        'property_type',
        'status',
        'summary',
        'description',
        'warranty_starts_on',
        'warranty_ends_on',
        'warranty_terms',
        'project_id',
    ];

    protected $casts = [
        'warranty_starts_on' => 'date',
        'warranty_ends_on' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }

    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    public function jobTemplate()
    {
        return $this->belongsTo(TradeJobTemplate::class, 'job_template_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function appointments()
    {
        return $this->hasMany(TradeAppointment::class, 'trade_job_id');
    }

    public function timers()
    {
        return $this->hasMany(TradeJobTimer::class, 'trade_job_id');
    }
    // App\Models\TradeJob.php
    public function tradeAppointments()
    {
        return $this->hasMany(\App\Models\TradeAppointment::class, 'trade_job_id');
    }
    public function nextAppointment(): HasOne
    {
        // Next upcoming appointment for this job
        return $this->hasOne(\App\Models\TradeAppointment::class, 'trade_job_id')
            ->where('start_at', '>=', now())
            ->orderBy('start_at');
    }

    public function items()
    {
        return $this->hasMany(TradeJobItem::class, 'trade_job_id')->orderBy('sort_order');
    }

    public function checklistItems()
    {
        return $this->hasMany(TradeJobChecklist::class, 'trade_job_id')->orderBy('sort_order');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'trade_job_id');
    }
    public function chatChannel()
    {
        return $this->hasOne(\App\Models\ChatChannel::class, 'trade_job_id');
    }

    public function ensureChatChannel(): \App\Models\ChatChannel
    {
        return \App\Models\ChatChannel::firstOrCreate(
            [
                'tenant_id' => $this->tenant_id,
                'trade_job_id' => $this->id,
            ],
            [
                'name' => 'Job: ' . ($this->summary ?? ('#' . $this->id)),
                'type' => 'trade_job',
                'project_id' => null,
            ]
        );
    }
}
