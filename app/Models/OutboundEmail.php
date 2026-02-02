<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OutboundEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'to_email',
        'to_name',
        'subject',
        'type',
        'mailable_type',
        'related_type',
        'related_id',
        'status',
        'message_id',
        'queued_at',
        'sent_at',
        'failed_at',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'failed' => 'Failed',
            default => 'Queued',
        };
    }

    public function relatedLabel(): string
    {
        return match ($this->type) {
            'invoice_sent' => 'Invoice',
            'proposal_sent' => 'Proposal',
            default => 'Email',
        };
    }

    public function relatedUrl(?Tenant $tenant = null): ?string
    {
        if (!$this->related_type || !$this->related_id || !$tenant) {
            return null;
        }

        if ($this->related_type === \App\Models\Invoice::class && \Illuminate\Support\Facades\Route::has('tenant.invoices.show')) {
            return route('tenant.invoices.show', ['tenant' => $tenant->id, 'invoice' => $this->related_id]);
        }

        if ($this->related_type === \App\Models\Proposal::class && \Illuminate\Support\Facades\Route::has('tenant.proposals.show')) {
            return route('tenant.proposals.show', ['tenant' => $tenant->id, 'proposal' => $this->related_id]);
        }

        return null;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }
}
