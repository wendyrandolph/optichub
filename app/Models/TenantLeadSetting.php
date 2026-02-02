<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantLeadSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'inbound_secret',
        'allowlist_domains',
        'notify_email',
        'auto_reply_enabled',
        'auto_reply_subject',
        'auto_reply_body',
    ];

    protected $casts = [
        'allowlist_domains' => 'array',
        'auto_reply_enabled' => 'bool',
        'inbound_secret' => 'encrypted',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function booted(): void
    {
        static::creating(function (TenantLeadSetting $setting) {
            if (empty($setting->inbound_secret)) {
                $setting->inbound_secret = static::generateSecret();
            }
        });
    }

    public static function generateSecret(): string
    {
        return Str::random(40);
    }

    public function rotateSecret(): void
    {
        $this->inbound_secret = static::generateSecret();
        $this->save();
    }
}
