<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantEmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'log_only_matched_contacts',
        'unmatched_behavior',
    ];

    protected $casts = [
        'log_only_matched_contacts' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
