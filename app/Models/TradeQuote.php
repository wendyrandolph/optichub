<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeQuote extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'company_id',
        'trade_job_id',
        'title',
        'notes',
        'status',
        'subtotal',
        'tax_rate',
        'tax_total',
        'discount_type',
        'discount_value',
        'discount_total',
        'total',
        'token_hash',
        'expires_at',
        'sent_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Contact::class, 'client_id');
    }

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }

    public function job()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }

    public function items()
    {
        return $this->hasMany(TradeQuoteItem::class, 'trade_quote_id');
    }

    public function acceptance()
    {
        return $this->hasOne(TradeQuoteAcceptance::class, 'trade_quote_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['sent', 'accepted', 'archived'], true);
    }
}
