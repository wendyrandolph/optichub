<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalPaymentSchedule extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'proposal_payment_schedules';

    protected $fillable = [
        'tenant_id',
        'proposal_id',
        'label',
        'amount',
        'due_trigger',
        'note',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
