<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalTemplate extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'title',
        'intro_text',
        'timeline_text',
        'next_steps_text',
        'payment_policy_text',
        'default_total',
        'payment_schedule',
        'timeline',
        'created_from_proposal_id',
    ];

    protected $casts = [
        'payment_schedule' => 'array',
        'timeline' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function sourceProposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'created_from_proposal_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalTemplateItem::class)->orderBy('sort_order');
    }

    public function goalItems(): HasMany
    {
        return $this->items()->where('type', 'goal');
    }

    public function deliverableItems(): HasMany
    {
        return $this->items()->where('type', 'deliverable');
    }
}
