<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMailSetting extends Model
{
  protected $fillable = [
    'tenant_id',
    'provider',
    'smtp_host',
    'smtp_port',
    'smtp_tls',
    'smtp_user',
    'smtp_password',
    'from_name',
    'from_email',
    'gmail_email',
    'gmail_access_token',
    'gmail_refresh_token',
    'gmail_expires_at',
    'inbound_domain',
    'inbound_localpart',
    'inbound_token',
    'auto_bcc_outbound',
  ];

  protected $casts = [
    'smtp_tls'           => 'boolean',
    'smtp_password'      => 'encrypted',
    'gmail_access_token' => 'encrypted',
    'gmail_refresh_token' => 'encrypted',
    'gmail_expires_at'   => 'datetime',
    'auto_bcc_outbound'  => 'boolean',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
}
