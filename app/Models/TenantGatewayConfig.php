<?php
// app/Models/TenantGatewayConfig.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantGatewayConfig extends Model
{
  protected $fillable = ['tenant_id', 'gateway', 'credentials', 'test_mode', 'status'];

  // If you’re on Laravel 10/11, you can encrypt transparently:
  protected $casts = [
    'credentials' => 'encrypted:array',
    'test_mode' => 'boolean',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
}

// app/Models/Payment.php
class Payment extends Model
{
  protected $fillable = [
    'tenant_id',
    'invoice_id',
    'provider',
    'provider_ref',
    'amount_cents',
    'currency',
    'status',
    'customer_ref',
    'metadata'
  ];

  protected $casts = [
    'metadata' => 'array',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }

  // Convenience accessor
  public function getAmountAttribute(): float
  {
    return ($this->amount_cents ?? 0) / 100;
  }
}
