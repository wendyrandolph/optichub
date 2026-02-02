<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalAcceptance extends Model
{
    protected $fillable = [
        'tenant_id',
        'proposal_id',
        'name',
        'email',
        'signed_name',
        'signature_path',
        'accepted_at',
        'ip_address',
        'user_agent',
        'proposal_hash',
        'pdf_path',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
