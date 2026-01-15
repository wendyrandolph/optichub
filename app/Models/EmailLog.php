<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'mail_account_id',
        'provider',
        'provider_message_id',
        'provider_thread_id',
        'direction',
        'from_email',
        'to_emails',
        'cc_emails',
        'subject',
        'snippet',
        'sent_at',
        'received_at',
        'contact_id',
        'company_id',
        'status',
        'needs_review',
    ];

    protected $casts = [
        'to_emails' => 'array',
        'cc_emails' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'needs_review' => 'boolean',
    ];

    public function mailAccount()
    {
        return $this->belongsTo(UserMailAccount::class, 'mail_account_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function company()
    {
        return $this->belongsTo(ClientCompany::class, 'company_id');
    }
}
