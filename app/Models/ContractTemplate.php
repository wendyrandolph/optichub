<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'scope',
        'title',
        'source_type',
        'file_path',
        'builder_json',
        'disclaimer_mode',
        'version',
        'is_active',
    ];

    protected $casts = [
        'builder_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
