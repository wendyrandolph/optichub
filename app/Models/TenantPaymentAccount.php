<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenantScope;

class TenantPaymentAccount extends Model
{
    use HasTenantScope;
    protected $fillable = [
        'tenant_id',
        'provider',
        'status',
        'public_data',
        'secret_data',
    ];

    protected $casts = [
        'public_data' => 'array',
        'secret_data' => 'encrypted:array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
