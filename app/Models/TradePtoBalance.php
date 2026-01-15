<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradePtoBalance extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'trade_pto_type_id',
        'balance_hours',
        'last_accrued_at',
    ];

    protected $casts = [
        'balance_hours' => 'decimal:2',
        'last_accrued_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(TradePtoType::class, 'trade_pto_type_id');
    }
}
