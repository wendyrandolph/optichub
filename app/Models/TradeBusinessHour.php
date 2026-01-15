<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeBusinessHour extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_closed',
    ];

    protected $casts = [
        'day_of_week' => 'int',
        'is_closed' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
