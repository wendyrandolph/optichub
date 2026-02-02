<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ProviderEmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'reply_to',
    ];

    public function getHostAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setHostAttribute($value): void
    {
        $this->attributes['host'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getUsernameAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setUsernameAttribute($value): void
    {
        $this->attributes['username'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPasswordAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function toMailConfig(): array
    {
        return [
            'mailer' => $this->mailer ?? 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ],
            'reply_to' => $this->reply_to,
        ];
    }
}
