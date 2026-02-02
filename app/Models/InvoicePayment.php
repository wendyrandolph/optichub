<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'amount',
        'method',
        'reference',
        'provider',
        'status',
        'currency',
        'provider_payment_id',
        'provider_checkout_id',
        'raw',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'raw' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
