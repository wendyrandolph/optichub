<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantRole extends Model
{
    use HasFactory;

    protected $table = 'tenant_roles';

    protected $fillable = [
        'tenant_id',
        'name',
    ];
}
