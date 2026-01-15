<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;
use App\Models\TradePtoRequest;

class TechShift extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'shift_type',
        'trade_pto_request_id',
        'clock_in_at',
        'clock_out_at',
        'notes',
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function ptoRequest()
    {
        return $this->belongsTo(TradePtoRequest::class, 'trade_pto_request_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
