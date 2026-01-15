<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradePtoType extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'accrual_rate_hours',
        'accrual_period',
        'tenure_months',
        'tenure_accrual_rate_hours',
        'carryover_enabled',
        'carryover_cap_hours',
        'is_active',
    ];

    protected $casts = [
        'tenure_months' => 'int',
        'tenure_accrual_rate_hours' => 'float',
        'carryover_enabled' => 'bool',
        'is_active' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
