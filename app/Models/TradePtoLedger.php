<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradePtoLedger extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'trade_pto_type_id',
        'hours',
        'reason',
        'trade_pto_request_id',
        'note',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
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

    public function request()
    {
        return $this->belongsTo(TradePtoRequest::class, 'trade_pto_request_id');
    }
}
