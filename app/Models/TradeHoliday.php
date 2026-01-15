<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenantScope;

class TradeHoliday extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'holiday_date',
        'is_paid',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_paid' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
