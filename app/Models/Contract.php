<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'tenant_id',
        'proposal_id',
        'project_id',
        'contract_template_id',
        'title',
        'source_type',
        'snapshot_json',
        'snapshot_html',
        'pdf_path',
        'status',
        'sent_at',
        'signed_at',
        'signer_name',
        'signer_email',
        'signer_ip',
        'signer_user_agent',
        'snapshot_hash',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
