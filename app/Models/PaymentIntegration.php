<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIntegration extends Model
{
  protected $fillable = ['tenant_id', 'provider', 'credentials', 'active'];
  protected $casts = [
    'credentials' => 'array',
    'active'      => 'boolean',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
}
