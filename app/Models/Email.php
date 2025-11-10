<?php
// app/Models/Email.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
  use HasFactory; // add HasTenantScope if you use one globally

  protected $table = 'emails';

  protected $fillable = [
    'tenant_id',
    'subject',
    'recipient_email',
    'related_type',
    'related_id',
    'body',
    'date_sent',
  ];

  protected $casts = [
    'date_sent' => 'datetime',
  ];

  public function tenant()
  {
    return $this->belongsTo(Tenant::class);
  }
}
