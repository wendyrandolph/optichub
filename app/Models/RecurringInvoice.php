<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoice extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'project_id',
        'title',
        'status',
        'frequency',
        'interval',
        'day_of_week',
        'day_of_month',
        'due_days',
        'auto_send',
        'next_run_at',
        'last_run_at',
        'line_items',
        'notes',
    ];

    protected $casts = [
        'auto_send' => 'boolean',
        'interval' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'due_days' => 'integer',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'line_items' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
