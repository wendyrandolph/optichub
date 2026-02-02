<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalSignature extends Model
{
    protected $fillable = [
        'proposal_id',
        'signed_at',
        'signer_name',
        'signer_email',
        'signer_ip',
        'signer_user_agent',
        'signature_text',
        'snapshot_hash',
        'agreed',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'agreed' => 'boolean',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
