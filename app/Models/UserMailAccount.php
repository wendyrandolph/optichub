<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider',
        'email_address',
        'google_sub',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
        'last_synced_at',
        'last_error',
        'sync_in_progress',
        'last_sync_stats',
        'last_sync_started_at',
        'last_sync_finished_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_sync_started_at' => 'datetime',
        'last_sync_finished_at' => 'datetime',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'sync_in_progress' => 'boolean',
        'last_sync_stats' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
