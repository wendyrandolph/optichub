<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Lead;
use App\Models\ClientCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Opportunity extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'title',
        'stage',
        'stage_changed_at',
        'estimated_value',
        'expected_close_date',
        'probability',
        'owner_id',
        'lead_id',
        'company_id',
        'notes',
        'next_step',
        'next_followup_at',
        'lost_reason',
        'flagged_overdue_at',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'stage_changed_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'estimated_value' => 'decimal:2',
        'flagged_overdue_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }

    public function activities()
    {
        return $this->hasMany(OpportunityActivity::class);
    }

    public function isOverdueFollowUp(): bool
    {
        if (!$this->next_followup_at) {
            return false;
        }
        $stage = strtolower((string) $this->stage);
        if (in_array($stage, ['won', 'lost'], true)) {
            return false;
        }
        return $this->next_followup_at->isPast();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdueFollowUp();
    }

    public function getDaysInStageAttribute(): ?int
    {
        // Approximate using updated_at as last stage change until dedicated timestamps exist
        return $this->updated_at ? $this->updated_at->diffInDays(now()) : null;
    }
}
