<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradePtoRequest extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'trade_pto_type_id',
        'start_date',
        'end_date',
        'hours_requested',
        'status',
        'notes',
        'requested_at',
        'approved_by',
        'approved_at',
        'denied_by',
        'denied_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
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
