<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIntegration extends Model
{
  protected $fillable = [
    'tenant_id',
    'provider',
    'label',
    'external_url',
    'instructions',
    'is_enabled',
    'credentials',
    'active',
  ];
  protected $casts = [
    'credentials' => 'array',
    'active' => 'boolean',
    'is_enabled' => 'boolean',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
}
