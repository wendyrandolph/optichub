<?php
// app/Models/TaxRate.php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'rate',
        'applies_by_default',
        'active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'applies_by_default' => 'bool',
        'active' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
